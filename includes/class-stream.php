<?php
namespace LightweightPlayer\Aparat;
defined('ABSPATH') || exit;

/** Public MP4 redirect, requested by the video element only after a play click. */
final class Stream {
    public static function resolve($hash, $backup = false) {
        if (!preg_match('/^[a-zA-Z0-9]{1,40}$/D', $hash)) {
            return new \WP_Error('invalid_video', 'شناسه ویدئو معتبر نیست.', array('status' => 400));
        }
        $cached = get_transient('lwpa_stream_' . $hash . ($backup ? '_backup' : ''));
        if (is_string($cached) && self::valid_url($cached)) return $cached;
        if (get_transient('lwpa_stream_error_' . $hash)) {
            return new \WP_Error('stream_unavailable', 'دریافت فایل ویدئو موقتاً ممکن نیست.', array('status' => 503));
        }
        $response = wp_safe_remote_get('https://www.aparat.com/etc/api/video/videohash/' . rawurlencode($hash),
            array('timeout' => 10, 'redirection' => 2, 'limit_response_size' => 1048576));
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $json = json_decode(wp_remote_retrieve_body($response), true);
            $video = is_array($json) ? ($json['video'] ?? array()) : array();
            if (is_array($video) && ($video['uid'] ?? '') === $hash) {
                $url = self::select_source($video['file_link_all'] ?? array());
                if ($url) {
                    // Signed CDN links expire; never store them in cached article HTML.
                    set_transient('lwpa_stream_' . $hash, $url, MINUTE_IN_SECONDS);
                    $host = wp_parse_url($url, PHP_URL_HOST);
                    $alternatives = array();
                    foreach ($video['file_link_all'] as $source) {
                        if (!is_array($source) || !is_array($source['urls'] ?? null)) continue;
                        $source['urls'] = array_values(array_filter($source['urls'], function ($candidate) use ($host) {
                            return self::valid_url($candidate) && wp_parse_url($candidate, PHP_URL_HOST) !== $host;
                        }));
                        $alternatives[] = $source;
                    }
                    $alternative = self::select_source($alternatives) ?: $url;
                    set_transient('lwpa_stream_' . $hash . '_backup', $alternative, MINUTE_IN_SECONDS);
                    return $backup ? $alternative : $url;
                }
            }
        }
        set_transient('lwpa_stream_error_' . $hash, 1, 30);
        if (is_wp_error($response)) {
            return new \WP_Error('stream_unavailable', 'ارتباط هاست با API آپارات ناموفق بود (' . sanitize_key($response->get_error_code()) . ').', array('status' => 503));
        }
        $status = wp_remote_retrieve_response_code($response);
        $message = $status === 200 ? 'آپارات فایل MP4 قابل پخش ارائه نکرد.' : 'API آپارات پاسخ HTTP ' . (int) $status . ' داد.';
        return new \WP_Error('stream_unavailable', $message, array('status' => 503));
    }

    public static function select_source($sources) {
        if (!is_array($sources)) return '';
        // Moderate resolution first; no player library or HLS implementation needed.
        foreach (array('480p', '360p', '720p', '240p', '144p', '1080p', '') as $profile) {
            foreach ($sources as $source) {
                if (!is_array($source) || ($profile && ($source['profile'] ?? '') !== $profile)) continue;
                $urls = $source['urls'] ?? array();
                if (!is_array($urls)) continue;
                foreach ($urls as $url) {
                    if (self::valid_url($url)) return $url;
                }
            }
        }
        return '';
    }

    public static function valid_url($url) {
        if (!is_string($url)) return false;
        $parts = wp_parse_url($url);
        return is_array($parts) && ($parts['scheme'] ?? '') === 'https' && empty($parts['user']) && empty($parts['pass']) &&
            (empty($parts['port']) || (int) $parts['port'] === 443) &&
            preg_match('/(^|\.)aparat\.(com|cloud|ir)$/D', strtolower($parts['host'] ?? '')) &&
            preg_match('/\.mp4$/iD', $parts['path'] ?? '');
    }

    public static function redirect($request) {
        $url = self::resolve($request->get_param('hash'), $request->get_param('backup') === '1');
        if (is_wp_error($url)) return $url;
        return new \WP_REST_Response(null, 302, array('Location' => $url,
            'Cache-Control' => 'no-store, private, max-age=0', 'X-Robots-Tag' => 'noindex'));
    }

    /** Media redirect outside REST authentication filters; no nonce needed for public videos. */
    public static function ajax() {
        nocache_headers();
        header('Cache-Control: no-store, private, max-age=0');
        $hash = isset($_GET['hash']) && is_string($_GET['hash']) ? wp_unslash($_GET['hash']) : '';
        $url = self::resolve($hash);
        if (is_wp_error($url)) {
            $data = $url->get_error_data();
            wp_send_json(array('error' => $url->get_error_message()), $data['status'] ?? 503);
        }
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            wp_send_json(array('url' => $url));
        }
        // Only the trusted CDN URLs validated by resolve() can reach this redirect.
        wp_redirect($url, 302, 'Lightweight Player for Aparat');
        exit;
    }
}
