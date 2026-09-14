<?php
namespace Dadsoo\Aparat;
defined('ABSPATH') || exit;

final class Renderer {
    private static $css_printed = false;

    public static function render($args = array()) {
        $args = wp_parse_args($args, array('url' => '', 'title' => '', 'posterId' => 0,
            'aboveFold' => false, 'ratio' => '16/9', 'playerType' => 'native'));
        $video = Parser::video($args['url']);
        $hash = $video['hash'];
        if ($hash === '') {
            return '';
        }
        $options = $video['options'];
        // Explicit controls override imported query parameters, including false and zero.
        foreach (array('muted', 'titleShow', 'startTime', 'recom') as $key) {
            if (array_key_exists($key, $args) && $args[$key] !== '' && $args[$key] !== null && $args[$key] !== 'inherit') {
                $options = array_merge($options, Parser::options(array($key => $args[$key])));
            }
        }
        if (($options['recom'] ?? '') === 'default') unset($options['recom']);
        $player_type = $args['playerType'] === 'aparat' ? 'aparat' : 'native';
        $stream_attribute = '';
        if ($player_type === 'native') {
            $stream_url = add_query_arg(array('action' => 'dadsoo_aparat_stream', 'hash' => $hash), admin_url('admin-ajax.php'));
            $stream_attribute = ' data-stream-url="' . esc_url($stream_url) . '" data-stream-fallback="' .
                esc_url(add_query_arg('backup', '1', rest_url('dadsoo-aparat/v1/stream/' . $hash))) . '"';
        }
        $data = Metadata::cached($hash);
        Metadata::schedule($hash, get_the_ID());
        $title = sanitize_text_field($args['title'] ?: ($data['title'] ?? 'ویدئوی آپارات'));
        $poster_id = absint($args['posterId'] ?: ($data['poster_id'] ?? 0));
        $ratio = in_array($args['ratio'], array('16/9', '9/16', '1/1', '4/3'), true) ? $args['ratio'] : '16/9';
        $above_fold = filter_var($args['aboveFold'], FILTER_VALIDATE_BOOLEAN);
        $poster = '';
        if ($poster_id && wp_attachment_is_image($poster_id)) {
            $poster = wp_get_attachment_image($poster_id, 'large', false, array(
                'class' => 'dso-ap__poster', 'alt' => '', 'decoding' => 'async',
                'loading' => $above_fold ? 'eager' : 'lazy',
                'fetchpriority' => $above_fold ? 'high' : 'auto',
                'sizes' => '(max-width: 640px) 100vw, (max-width: 1024px) 90vw, 960px',
            ));
        }
        wp_enqueue_script('dadsoo-aparat-player');
        $css = '';
        // Tiny inline CSS appears once, including shortcodes rendered after wp_head.
        if (!self::$css_printed) {
            self::$css_printed = true;
            $css = '<style id="dadsoo-aparat-performance-css">' . file_get_contents(DSO_AP_DIR . 'assets/player.css') . '</style>';
        }
        return $css . '<div class="dso-ap" data-dso-aparat="' . esc_attr($hash) . '" data-video-title="' . esc_attr($title) .
            '" data-player-options="' . esc_attr(wp_json_encode((object) $options)) . '" data-player-type="' . esc_attr($player_type) . '"' . $stream_attribute . ' style="aspect-ratio:' . esc_attr($ratio) . '">' .
            '<a class="dso-ap__trigger" href="' . esc_url('https://www.aparat.com/v/' . $hash) .
            '" aria-label="' . esc_attr('پخش ' . $title) . '">' . $poster .
            '<span class="dso-ap__label" aria-hidden="true"><svg width="32" height="32" viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M8 5v14l11-7z"/></svg></span></a></div>';
    }
}
