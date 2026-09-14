<?php
// Run separately for fa_IR and en_US against a disposable WordPress installation.
$locale = $argv[2] ?? 'fa_IR';
if (!in_array($locale, array('fa_IR', 'en_US'), true)) throw new RuntimeException('Unsupported test locale');
$GLOBALS['wp_filter']['locale'][0][] = array('function' => function () use ($locale) { return $locale; }, 'accepted_args' => 1);
require $argv[1];
require_once ABSPATH . 'wp-admin/includes/plugin.php';
function assert_i18n($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$domain = 'lightweight-player-for-aparat';
$expected = $locale === 'fa_IR' ? 'آپارات بهینه شده' : 'Lightweight Player for Aparat';
$data = get_plugin_data(LWPA_FILE, false, true);
assert_i18n($data['Name'] === $expected, 'Plugin metadata title not localized');
assert_i18n($data['Author'] === 'Iman Shadmehri' && $data['PluginURI'] === 'https://elinweb.ir', 'Author or website changed');
assert_i18n(get_plugin_data(LWPA_FILE, false, false)['Name'] === 'Lightweight Player for Aparat', 'Canonical plugin name changed');
$block = WP_Block_Type_Registry::get_instance()->get_registered('lightweight-player/aparat');
assert_i18n($block && $block->title === $expected, 'Block title not localized');
assert_i18n(WP_Block_Type_Registry::get_instance()->get_registered('dadsoo/aparat-performance')->title === $expected,
    'Compatibility block title not localized');
if ($locale === 'fa_IR') {
    assert_i18n(strpos($data['Description'], 'ویدئوی آپارات') === 0, 'Plugin description not Persian');
    assert_i18n(strpos($block->description, 'نمایش پوستر محلی') === 0, 'Block description not Persian');
}
if (did_action('elementor/loaded')) {
    $manager = \Elementor\Plugin::$instance->widgets_manager;
    $widget = $manager->get_widget_types('lightweight-player-for-aparat');
    assert_i18n($widget && $widget->get_title() === $expected, 'Elementor title not localized');
    assert_i18n($manager->get_widget_types('dadsoo-aparat-performance')->get_title() === $expected,
        'Compatibility widget title not localized');
}
echo "PASS $locale: translated plugin metadata, block and Elementor titles/descriptions; canonical name and author preserved.\n";
