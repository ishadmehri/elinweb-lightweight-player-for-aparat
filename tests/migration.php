<?php
// Compatibility checks against a disposable WordPress + optional Elementor site.
require $argv[1];
if (!defined('LWPA_VERSION')) require dirname(__DIR__) . '/lightweight-player-for-aparat.php';
use LightweightPlayer\Aparat\Metadata;
use LightweightPlayer\Aparat\Plugin;
use LightweightPlayer\Aparat\Poster;
function assert_migration($condition, $message) { if (!$condition) throw new RuntimeException($message); }
if (!WP_Block_Type_Registry::get_instance()->is_registered('lightweight-player/aparat')) Plugin::register();
rest_get_server();
add_filter('pre_schedule_event', '__return_false');
add_filter('pre_http_request', function () { throw new RuntimeException('Migration triggered HTTP'); });
add_filter('pre_option_dso_ap_video_legacy123', function () { return array('title' => 'Old title', 'poster_id' => 0); });
assert_migration(Metadata::cached('legacy123')['title'] === 'Old title', 'Old metadata ignored');
add_filter('pre_option_lwpa_video_legacy123', function () { return array('title' => 'New title', 'poster_id' => 0); });
assert_migration(Metadata::cached('legacy123')['title'] === 'New title', 'New cache should have priority');
$old = do_shortcode('[dadsoo_aparat url="https://www.aparat.com/v/legacy123" start_time="65"]');
$new = do_shortcode('[lwpa_aparat url="https://www.aparat.com/v/legacy123" start_time="65"]');
assert_migration(strpos($old, 'data-lwpa-aparat="legacy123"') !== false, 'Legacy shortcode no longer renders');
assert_migration(strpos($new, 'data-lwpa-aparat="legacy123"') !== false, 'New shortcode does not render');
$block = array('blockName' => 'dadsoo/aparat-performance', 'attrs' => array('url' => 'legacy123', 'startTime' => 65),
    'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array());
assert_migration(strpos(render_block($block), '&quot;startTime&quot;:65') !== false, 'Legacy block settings lost');
assert_migration(strpos(dadsoo_aparat_performance_render('legacy123'), 'data-lwpa-aparat="legacy123"') !== false, 'Legacy PHP function broken');
assert_migration(has_action('wp_ajax_nopriv_dadsoo_aparat_stream') !== false, 'Legacy AJAX route missing');
assert_migration(has_action('dso_ap_warm_video') !== false, 'Legacy cron callback missing');
$routes = rest_get_server()->get_routes();
assert_migration(isset($routes['/dadsoo-aparat/v1/stream/(?P<hash>[a-zA-Z0-9]{1,40})']), 'Legacy REST route missing');
if (did_action('elementor/loaded')) {
    Plugin::widget(\Elementor\Plugin::$instance->widgets_manager);
    $widget = \Elementor\Plugin::$instance->widgets_manager->get_widget_types('dadsoo-aparat-performance');
    assert_migration($widget && !$widget->show_in_panel(), 'Legacy widget must remain available but hidden');
    assert_migration(isset($widget->get_controls()['aparat_url']), 'Legacy widget controls missing');
}
$id = wp_insert_attachment(array('post_title' => 'Migration fixture', 'post_mime_type' => 'image/webp', 'post_status' => 'inherit'));
try {
    update_post_meta($id, '_wp_attached_file', '2026/09/aparat-migration.webp');
    update_post_meta($id, '_dso_ap_hash', 'legacy123');
    $url = Poster::url($id);
    $markup = '<div data-dso-aparat="legacy123"><img class="dso-ap__poster" src="" srcset="900w" data-dso-poster-id="' . $id . '"></div>';
    $repaired = new WP_HTML_Tag_Processor(Poster::repair_content($markup));
    assert_migration($repaired->next_tag('IMG'), 'Legacy poster missing');
    assert_migration($repaired->get_attribute('src') === $url, 'Legacy poster source not repaired');
    assert_migration($repaired->get_attribute('srcset') === $url . ' 900w', 'Legacy responsive descriptor not repaired');
    $import = new ReflectionMethod(Metadata::class, 'import_poster');
    $import->setAccessible(true);
    assert_migration($import->invoke(null, 'legacy123', 'https://static.aparat.com/fixture.webp', 'Old title') === $id,
        'Previously imported media not reused');
} finally {
    wp_delete_attachment($id, true);
}
echo "PASS: legacy cache, shortcode, saved block, PHP API, AJAX/REST/cron, Elementor widget, poster repair and media reuse; zero HTTP.\n";
