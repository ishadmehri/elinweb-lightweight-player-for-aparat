<?php
/**
 * Plugin Name: Lightweight Player for Aparat
 * Plugin URI: https://elinweb.ir
 * Description: Play Aparat videos after a click, with local posters, a Gutenberg block and an Elementor widget. Supports /embed/ and /v/ links.
 * Version: 2.0.2
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Author: Iman Shadmehri
 * Author URI: https://elinweb.ir
 * License: GPL-2.0-or-later
 * Text Domain: lightweight-player-for-aparat
 * Domain Path: /languages
 */
defined('ABSPATH') || exit;
define('LWPA_VERSION', '2.0.2');
define('LWPA_FILE', __FILE__);
define('LWPA_DIR', plugin_dir_path(__FILE__));
require_once LWPA_DIR . 'includes/class-parser.php';
require_once LWPA_DIR . 'includes/class-metadata.php';
require_once LWPA_DIR . 'includes/class-stream.php';
require_once LWPA_DIR . 'includes/class-poster.php';
require_once LWPA_DIR . 'includes/class-renderer.php';
require_once LWPA_DIR . 'includes/class-plugin.php';
\LightweightPlayer\Aparat\Plugin::boot();

/** Public integration API for existing video injectors. */
function lwpa_render($url, $args = array()) {
    $args['url'] = $url;
    return \LightweightPlayer\Aparat\Renderer::render($args);
}

if (!function_exists('lightweight_player_for_aparat_render')) {
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Published API alias; lwpa_render is the canonical prefixed function.
    function lightweight_player_for_aparat_render($url, $args = array()) {
        return lwpa_render($url, $args);
    }
}

// Keep existing integrations working after the plugin rename.
if (!function_exists('dadsoo_aparat_performance_render')) {
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserves existing article injectors; new integrations use lwpa_render.
    function dadsoo_aparat_performance_render($url, $args = array()) {
        return lightweight_player_for_aparat_render($url, $args);
    }
}
