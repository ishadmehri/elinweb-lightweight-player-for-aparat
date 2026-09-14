<?php
namespace Dadsoo\Aparat;
defined('ABSPATH') || exit;

final class Plugin {
    public static function boot() {
        add_action('init', array(__CLASS__, 'register'));
        add_action('rest_api_init', array(__CLASS__, 'rest'));
        add_action('wp_ajax_dadsoo_aparat_stream', array('Dadsoo\\Aparat\\Stream', 'ajax'));
        add_action('wp_ajax_nopriv_dadsoo_aparat_stream', array('Dadsoo\\Aparat\\Stream', 'ajax'));
        add_action('elementor/widgets/register', array(__CLASS__, 'widget'));
        add_action('dso_ap_warm_video', array('Dadsoo\\Aparat\\Metadata', 'warm'), 10, 2);
        add_action('save_post', array(__CLASS__, 'saved'), 5, 2);
        add_action('added_post_meta', array(__CLASS__, 'elementor_saved'), 10, 4);
        add_action('updated_post_meta', array(__CLASS__, 'elementor_saved'), 10, 4);
        add_filter('script_loader_tag', array(__CLASS__, 'script_tag'), 10, 2);
        add_filter('perfmatters_delay_js_exclusions', array(__CLASS__, 'delay_exclusions'));
        register_deactivation_hook(DSO_AP_FILE, function () { wp_unschedule_hook('dso_ap_warm_video'); });
    }

    public static function register() {
        wp_register_script('dadsoo-aparat-player', plugins_url('assets/player.js', DSO_AP_FILE), array(), DSO_AP_VERSION,
            array('strategy' => 'defer', 'in_footer' => true));
        wp_register_script('dadsoo-aparat-editor', plugins_url('block/editor.js', DSO_AP_FILE),
            array('wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-api-fetch'), DSO_AP_VERSION, true);
        register_block_type(DSO_AP_DIR . 'block', array('render_callback' => array('Dadsoo\\Aparat\\Renderer', 'render')));
        add_shortcode('dadsoo_aparat', array(__CLASS__, 'shortcode'));
    }

    public static function shortcode($atts) {
        $atts = shortcode_atts(array('url' => '', 'title' => '', 'poster' => 0, 'above_fold' => 'false', 'ratio' => '16/9',
            'muted' => '', 'title_show' => '', 'start_time' => '', 'recom' => '', 'player' => 'native'), $atts, 'dadsoo_aparat');
        $args = array('url' => $atts['url'], 'title' => $atts['title'], 'posterId' => absint($atts['poster']),
            'aboveFold' => filter_var($atts['above_fold'], FILTER_VALIDATE_BOOLEAN), 'ratio' => $atts['ratio'], 'playerType' => $atts['player']);
        foreach (array('muted' => 'muted', 'title_show' => 'titleShow', 'start_time' => 'startTime', 'recom' => 'recom') as $attribute => $key) {
            if ($atts[$attribute] !== '') $args[$key] = $atts[$attribute];
        }
        return Renderer::render($args);
    }

    public static function widget($manager) {
        require_once DSO_AP_DIR . 'includes/class-elementor-widget.php';
        $manager->register(new Elementor_Widget());
    }

    public static function rest() {
        register_rest_route('dadsoo-aparat/v1', '/stream/(?P<hash>[a-zA-Z0-9]{1,40})', array(
            'methods' => array('GET', 'HEAD'), 'permission_callback' => '__return_true',
            'callback' => array('Dadsoo\\Aparat\\Stream', 'redirect'),
        ));
        register_rest_route('dadsoo-aparat/v1', '/resolve', array(
            'methods' => 'POST',
            'permission_callback' => function () { return current_user_can('edit_posts') && current_user_can('upload_files'); },
            'args' => array('url' => array('required' => true, 'type' => 'string')),
            'callback' => function ($request) {
                $hash = Parser::hash($request->get_param('url'));
                if ($hash === '') {
                    return new \WP_Error('invalid_url', 'لینک آپارات معتبر نیست.', array('status' => 400));
                }
                $result = Metadata::resolve($hash);
                if (is_wp_error($result)) {
                    $result->add_data(array('status' => 503));
                    return $result;
                }
                return rest_ensure_response(array('hash' => $hash, 'title' => $result['title'],
                    'posterId' => $result['poster_id'], 'posterUrl' => wp_get_attachment_image_url($result['poster_id'], 'large')));
            },
        ));
    }

    public static function script_tag($tag, $handle) {
        if ($handle === 'dadsoo-aparat-player') {
            return str_replace('<script ', '<script nowprocket ', $tag);
        }
        return $tag;
    }

    public static function delay_exclusions($exclusions) {
        $exclusions[] = '/dadsoo-aparat-performance/assets/player.js';
        return $exclusions;
    }

    public static function saved($post_id, $post) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || $post->post_type === 'attachment' ||
            !current_user_can('edit_post', $post_id)) {
            return;
        }
        $hashes = array();
        self::block_hashes(parse_blocks($post->post_content), $hashes);
        if (has_shortcode($post->post_content, 'dadsoo_aparat') &&
            preg_match_all('/' . get_shortcode_regex(array('dadsoo_aparat')) . '/s', $post->post_content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if ($match[1] === '[' && $match[6] === ']') continue;
                $atts = shortcode_parse_atts($match[3]);
                $hashes[] = Parser::hash(is_array($atts) ? ($atts['url'] ?? '') : '');
            }
        }
        self::prepare($hashes, $post_id);
    }

    private static function block_hashes($blocks, &$hashes) {
        foreach ($blocks as $block) {
            if (($block['blockName'] ?? '') === 'dadsoo/aparat-performance') {
                $hashes[] = Parser::hash($block['attrs']['url'] ?? '');
            }
            self::block_hashes($block['innerBlocks'] ?? array(), $hashes);
        }
    }

    public static function elementor_saved($meta_id, $post_id, $key, $value) {
        if ($key !== '_elementor_data' || !current_user_can('edit_post', $post_id) || wp_is_post_revision($post_id)) return;
        $tree = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($tree)) return;
        $hashes = array();
        self::elementor_hashes($tree, $hashes);
        self::prepare($hashes, $post_id);
    }

    private static function elementor_hashes($elements, &$hashes) {
        foreach ($elements as $element) {
            if (!is_array($element)) continue;
            if (($element['widgetType'] ?? '') === 'dadsoo-aparat-performance') {
                $hashes[] = Parser::hash($element['settings']['aparat_url'] ?? '');
            }
            self::elementor_hashes($element['elements'] ?? array(), $hashes);
        }
    }

    private static function prepare($hashes, $post_id) {
        $hashes = array_values(array_unique(array_filter($hashes)));
        foreach ($hashes as $index => $hash) {
            if ($index < 3) {
                Metadata::warm($hash, $post_id);
            } else {
                Metadata::schedule($hash, $post_id);
            }
        }
    }
}
