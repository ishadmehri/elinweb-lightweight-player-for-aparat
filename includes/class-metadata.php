<?php
namespace LightweightPlayer\Aparat;
defined('ABSPATH') || exit;

final class Metadata {
    public static function cached($hash) {
        $data = get_option('lwpa_video_' . $hash, array());
        if (empty($data)) $data = get_option('dso_ap_video_' . $hash, array());
        return is_array($data) ? $data : array();
    }

    public static function schedule($hash, $post_id = 0) {
        $cached = self::cached($hash);
        if ($hash === '' || (!empty($cached['poster_id']) && wp_attachment_is_image($cached['poster_id'])) || get_transient('lwpa_error_' . $hash)) {
            return;
        }
        $args = array($hash, (int) $post_id);
        if (!wp_next_scheduled('lwpa_warm_video', $args)) {
            wp_schedule_single_event(time() + 5, 'lwpa_warm_video', $args);
        }
    }

    /** Never call this during public rendering: network work belongs to editing/cron. */
    public static function resolve($hash) {
        if (!preg_match('/^[a-zA-Z0-9]{1,40}$/D', $hash)) {
            return new \WP_Error('invalid_video', 'لینک آپارات معتبر نیست.');
        }
        $cached = self::cached($hash);
        if (!empty($cached['poster_id']) && wp_attachment_is_image($cached['poster_id'])) {
            return $cached;
        }
        if (get_transient('lwpa_error_' . $hash)) {
            return new \WP_Error('video_unavailable', 'دریافت پوستر موقتاً ناموفق بود؛ لینک و دکمه همچنان قابل استفاده‌اند. چند دقیقه بعد دوباره امتحان کنید.');
        }
        $lock = 'lwpa_lock_' . $hash;
        $locked_at = (int) get_option($lock, 0);
        if ($locked_at && $locked_at < time() - 120) {
            delete_option($lock);
        }
        if (!add_option($lock, time(), '', false)) {
            return new \WP_Error('video_busy', 'پوستر در حال آماده‌سازی است.');
        }
        try {
            $response = wp_safe_remote_get('https://www.aparat.com/etc/api/video/videohash/' . rawurlencode($hash), array(
                'timeout' => 10, 'redirection' => 2, 'limit_response_size' => 1048576,
            ));
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return self::failure($hash);
            }
            $json = json_decode(wp_remote_retrieve_body($response), true);
            $video = is_array($json) ? ($json['video'] ?? array()) : array();
            if (!is_array($video) || ($video['uid'] ?? '') !== $hash || empty($video['big_poster'])) {
                return self::failure($hash);
            }
            $title = sanitize_text_field(html_entity_decode($video['title'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $poster_id = self::import_poster($hash, $video['big_poster'], $title);
            if (is_wp_error($poster_id)) {
                return self::failure($hash);
            }
            $data = array('title' => $title, 'poster_id' => (int) $poster_id, 'resolved_at' => time());
            update_option('lwpa_video_' . $hash, $data, false);
            delete_transient('lwpa_error_' . $hash);
            return $data;
        } finally {
            delete_option($lock);
        }
    }

    private static function failure($hash) {
        set_transient('lwpa_error_' . $hash, 1, 5 * MINUTE_IN_SECONDS);
        return new \WP_Error('video_unavailable', 'عنوان یا پوستر از آپارات دریافت نشد. دکمه ویدئو بدون پوستر کار می‌کند؛ چند دقیقه بعد دوباره امتحان کنید.');
    }

    private static function import_poster($hash, $url, $title) {
        $parts = wp_parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        if (($parts['scheme'] ?? '') !== 'https' ||
            !preg_match('/(^|\.)aparat\.(com|cloud|ir)$/D', $host)) {
            return new \WP_Error('invalid_poster', 'آدرس پوستر نامعتبر است.');
        }
        // Reuse an existing imported attachment when the metadata cache was removed.
        // Recovery only during editor/cron import after an option cache miss, never public rendering. Limit to one attachment.
        $existing = get_posts(array('post_type' => 'attachment', 'post_status' => 'inherit', 'fields' => 'ids',
            'posts_per_page' => 1, 'no_found_rows' => true, 'update_post_meta_cache' => false,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded import recovery reuses media; not a frontend query.
            'update_post_term_cache' => false, 'meta_query' => array('relation' => 'OR',
                array('key' => '_lwpa_hash', 'value' => $hash), array('key' => '_dso_ap_hash', 'value' => $hash))));
        if ($existing && wp_attachment_is_image($existing[0])) {
            return (int) $existing[0];
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $tmp = wp_tempnam('aparat-' . $hash);
        if (!$tmp) {
            return new \WP_Error('poster_temp', 'ساخت فایل موقت ممکن نشد.');
        }
        try {
            $response = wp_safe_remote_get($url, array('timeout' => 15, 'redirection' => 2,
                'stream' => true, 'filename' => $tmp, 'limit_response_size' => 2 * 1024 * 1024));
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200 ||
                !file_exists($tmp) || filesize($tmp) >= 2 * 1024 * 1024) {
                return new \WP_Error('poster_download', 'دریافت تصویر ممکن نشد.');
            }
            $info = wp_getimagesize($tmp);
            $extensions = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
            if (!$info || !isset($extensions[$info['mime']]) || $info[0] * $info[1] > 16000000) {
                return new \WP_Error('poster_type', 'فرمت تصویر معتبر نیست.');
            }
            $extension = $extensions[$info['mime']];
            $editor = wp_get_image_editor($tmp);
            if (!is_wp_error($editor)) {
                $editor->resize(960, 960, false);
                $editor->set_quality(75);
                if ($editor->supports_mime_type('image/webp')) {
                    $optimized = $editor->save($tmp . '.webp', 'image/webp');
                    if (!is_wp_error($optimized)) {
                        wp_delete_file($tmp);
                        $tmp = $optimized['path'];
                        $extension = 'webp';
                    }
                }
            }
            $id = media_handle_sideload(array('name' => 'aparat-' . $hash . '.' . $extension, 'tmp_name' => $tmp), 0, $title);
            if (!is_wp_error($id)) {
                update_post_meta($id, '_lwpa_hash', $hash);
            }
            return $id;
        } finally {
            if (file_exists($tmp)) {
                wp_delete_file($tmp);
            }
        }
    }

    public static function warm($hash, $post_id = 0) {
        $result = self::resolve($hash);
        if (!is_wp_error($result) && $post_id) {
            clean_post_cache($post_id);
            if (function_exists('rocket_clean_post')) {
                rocket_clean_post($post_id);
            }
        }
    }
}
