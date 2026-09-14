<?php
/**
 * Plugin Name: Dadsoo Aparat Performance
 * Plugin URI: https://elinweb.ir
 * Description: ویدئوی آپارات با پوستر محلی و بارگذاری پلیر پس از کلیک؛ بلوک وردپرس، ویجت المنتور و شورت‌کد.
 * Version: 1.2.4
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Author: ishadmehri
 * Author URI: https://elinweb.ir
 * License: GPL-2.0-or-later
 * Text Domain: dadsoo-aparat-performance
 */
defined('ABSPATH') || exit;
define('DSO_AP_VERSION', '1.2.4');
define('DSO_AP_FILE', __FILE__);
define('DSO_AP_DIR', plugin_dir_path(__FILE__));
require_once DSO_AP_DIR . 'includes/class-parser.php';
require_once DSO_AP_DIR . 'includes/class-metadata.php';
require_once DSO_AP_DIR . 'includes/class-stream.php';
require_once DSO_AP_DIR . 'includes/class-poster.php';
require_once DSO_AP_DIR . 'includes/class-renderer.php';
require_once DSO_AP_DIR . 'includes/class-plugin.php';
\Dadsoo\Aparat\Plugin::boot();

/** Public integration API for existing video injectors. */
function dadsoo_aparat_performance_render($url, $args = array()) {
    $args['url'] = $url;
    return \Dadsoo\Aparat\Renderer::render($args);
}
