<?php
// Read-only integration against an existing WordPress + optional Elementor install.
$root = $argv[1] ?? '';
if (!$root) throw new RuntimeException('Pass the WordPress root directory.');
$_SERVER['HTTP_HOST'] = 'chatgpt.test';
$_SERVER['REQUEST_URI'] = '/';
require is_file($root) ? $root : rtrim($root, '/\\') . '/wp-load.php';
if (!defined('LWPA_VERSION')) require dirname(__DIR__) . '/lightweight-player-for-aparat.php';
use LightweightPlayer\Aparat\Parser;
use LightweightPlayer\Aparat\Plugin;
use LightweightPlayer\Aparat\Renderer;
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
if (!WP_Block_Type_Registry::get_instance()->is_registered('lightweight-player/aparat')) Plugin::register();
rest_get_server();
$block = WP_Block_Type_Registry::get_instance()->get_registered('lightweight-player/aparat');
check($block && is_callable($block->render_callback), 'Dynamic block not registered');
check($block->view_script_handles === array('lwpa-player'), 'Block view script is not conditional');
check(!wp_script_is('lwpa-player', 'enqueued'), 'Player is enqueued on pages without video');
check(wp_scripts()->registered['lwpa-player']->deps === array(), 'Unexpected frontend dependency');
$cases = array(
    array('https://www.aparat.com/v/ytf50k5', 'ytf50k5'),
    array('https://aparat.com/v/ytf50k5?foo=1', 'ytf50k5'),
    array('ytf50k5', 'ytf50k5'),
    array('<div><script src="https://www.aparat.com/embed/ytf50k5?data[rnddiv]=1&amp;data[responsive]=yes"></script></div>', 'ytf50k5'),
    array('<style>.ratio{}</style><iframe src="https://www.aparat.com/video/video/embed/videohash/ytf50k5/vt/frame"></iframe>', 'ytf50k5'),
    array("<iframe src='//www.aparat.com/video/video/embed/videohash/abc123/vt/frame'></iframe>", 'abc123'),
    array('https://www.aparat.com.evil.test/v/ytf50k5', ''),
    array('https://evil.test/v/ytf50k5', ''),
    array('https://user:pass@www.aparat.com/v/ytf50k5', ''),
    array('https://www.aparat.com:8443/v/ytf50k5', ''),
    array('https://www.aparat.com/embed/live/user', ''),
    array('javascript:alert(1)', ''),
);
foreach ($cases as [$input, $expected]) check(Parser::hash($input) === $expected, 'Parser case failed');
check(Renderer::render(array('url' => 'https://evil.test/v/test')) === '', 'Invalid URL is rendered');

// Seed cached records with filters only: no options, cron or uploads are written.
foreach (array('ytf50k5', 'abc123') as $hash) {
    add_filter('pre_option_lwpa_video_' . $hash, function () { return array('title' => 'عنوان " <آزمایش>', 'poster_id' => 0); });
}
add_filter('pre_schedule_event', function () { return false; });
$requests = 0;
add_filter('pre_http_request', function () use (&$requests) { $requests++; return new WP_Error('network_forbidden', 'No network in render'); });
$first = do_shortcode('[lwpa_aparat url="https://www.aparat.com/v/ytf50k5"]');
$second = render_block(array('blockName' => 'lightweight-player/aparat', 'attrs' => array('url' => 'https://www.aparat.com/v/abc123', 'ratio' => '9/16'),
    'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array()));
check($requests === 0, 'Public render made an HTTP request');
check(strpos($first . $second, '<iframe') === false && strpos($first . $second, '<script') === false, 'Initial player markup exists');
check(strpos($first . $second, '<video') === false, 'Initial native video exists');
check(strpos($first, 'data-player-type="native"') !== false, 'Default player is not native');
check(strpos($first, '<span>پخش ویدئو</span>') === false && strpos($first, 'aria-label="پخش ') !== false, 'Icon button/accessibility incorrect');
check(substr_count($first . $second, 'id="lightweight-player-for-aparat-css"') === 1, 'CSS duplicated');
check(strpos($first, '&quot;') !== false && strpos($first, '<آزمایش>') === false, 'Title was not sanitized/escaped');
check(strpos($second, 'aspect-ratio:9/16') !== false, 'Ratio ignored');
function player_options($html) {
    $processor = new WP_HTML_Tag_Processor($html);
    while ($processor->next_tag(array('tag_name' => 'DIV'))) {
        if ($processor->get_attribute('data-lwpa-aparat')) {
            return json_decode($processor->get_attribute('data-player-options'), true);
        }
    }
    throw new RuntimeException('Player options absent');
}
$script_example = '<div id="38589603679"><script type="text/JavaScript" src="https://www.aparat.com/embed/ytf50k5?data[rnddiv]=38589603679&data[responsive]=yes&muted=true&titleShow=true&startTime=65&recom=self"></script></div>';
$iframe_example = '<style>.h_iframe-aparat_embed_frame{position:relative;}</style><div><span style="display:block;padding-top:57%"></span><iframe src="https://www.aparat.com/video/video/embed/videohash/ytf50k5/vt/frame?titleShow=true&amp;startTime=65&amp;muted=true&amp;recom=self"></iframe></div>';
$expected = array('muted' => true, 'titleShow' => true, 'startTime' => 65, 'recom' => 'self');
foreach (array($script_example, $iframe_example) as $embed) {
    $parsed = Parser::video($embed);
    check($parsed['hash'] === 'ytf50k5' && $parsed['options'] === $expected, 'Imported player settings lost');
    $rendered = lightweight_player_for_aparat_render($embed);
    check(player_options($rendered) === $expected && strpos($rendered, '<iframe') === false, 'Import broke facade');
}
$overridden = lightweight_player_for_aparat_render($iframe_example, array('muted' => false, 'titleShow' => false, 'startTime' => 0, 'recom' => 'default'));
check(player_options($overridden) === array('muted' => false, 'titleShow' => false, 'startTime' => 0), 'False/zero/default controls did not override imported settings');
$configured = do_shortcode('[lwpa_aparat url="https://www.aparat.com/v/ytf50k5" muted="true" title_show="true" start_time="65" recom="self"]');
check(player_options($configured) === $expected, 'Shortcode player settings not applied');
$block_settings = render_block(array('blockName' => 'lightweight-player/aparat', 'attrs' => array('url' => 'https://www.aparat.com/v/ytf50k5',
    'muted' => true, 'titleShow' => true, 'startTime' => 65, 'recom' => 'self'), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array()));
check(player_options($block_settings) === $expected, 'Block player settings not applied');
$inherited = render_block(array('blockName' => 'lightweight-player/aparat', 'attrs' => array('url' => 'https://www.aparat.com/v/ytf50k5?muted=true&titleShow=true&startTime=65&recom=self'),
    'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array()));
check(player_options($inherited) === $expected, 'Block defaults overwrote link settings');
$invalid_options = Parser::video('https://www.aparat.com/v/ytf50k5?muted[]=true&titleShow=invalid&startTime=-1&recom=evil&autoplay=true');
check($invalid_options['options'] === array(), 'Unapproved/malformed player parameter forwarded');
check($requests === 0, 'Player settings triggered a public HTTP request');
check(wp_script_is('lwpa-player', 'enqueued'), 'Renderer failed to enqueue runtime');
check(in_array('/lightweight-player-for-aparat/assets/player.js', Plugin::delay_exclusions(array()), true), 'Perfmatters exclusion missing');
check(strpos(Plugin::script_tag('<script src="test"></script>', 'lwpa-player'), 'nowprocket') !== false, 'Rocket exclusion missing');
$routes = rest_get_server()->get_routes();
check(isset($routes['/lightweight-player/v1/resolve']), 'REST route missing');
check(call_user_func($routes['/lightweight-player/v1/resolve'][0]['permission_callback']) === false, 'Anonymous metadata access allowed');
$elementor = 'not installed';
if (did_action('elementor/loaded')) {
    if (!\Elementor\Plugin::$instance->widgets_manager->get_widget_types('lightweight-player-for-aparat')) Plugin::widget(\Elementor\Plugin::$instance->widgets_manager);
    $widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types('lightweight-player-for-aparat');
    check($widget !== null, 'Elementor widget missing');
    $controls = $widget->get_controls();
    check(isset($controls['aparat_url'], $controls['poster'], $controls['above_fold'], $controls['ratio'], $controls['start_time'], $controls['muted'], $controls['title_show'], $controls['recommendations']), 'Elementor controls missing');
    check($widget->get_script_depends() === array('lwpa-player'), 'Widget dependency incorrect');
    $elementor = 'registered with controls and shared dependency';
}
echo 'PASS WordPress ' . get_bloginfo('version') . ': block, shortcode, assets, parser, settings, render HTTP, escaping, REST permissions. Elementor: ' . $elementor . ".\n";
