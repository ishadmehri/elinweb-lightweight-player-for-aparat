<?php
namespace Dadsoo\Aparat;
defined('ABSPATH') || exit;

/** Repair attachment markup without network requests or changing media records. */
final class Poster {
    private static function valid_url($url) {
        if (!is_string($url) || preg_match('/\s/', $url)) return false;
        $parts = wp_parse_url($url);
        return is_array($parts) && in_array(strtolower($parts['scheme'] ?? ''), array('http', 'https'), true)
            && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass'])
            && !preg_match('~/(?:[0-9]+w|[0-9.]+x)/?$~', $parts['path'] ?? '');
    }

    public static function url($id) {
        $image = wp_get_attachment_image_src($id, 'large');
        if ($image && self::valid_url($image[0])) return $image[0];
        $url = wp_get_attachment_url($id);
        if (self::valid_url($url)) return $url;

        // Read the upload-relative file, never an attachment/page permalink or GUID.
        $file = get_post_meta($id, '_wp_attached_file', true);
        $uploads = wp_get_upload_dir();
        if (!is_string($file) || !empty($uploads['error'])) return '';
        $file = wp_normalize_path($file);
        $base = trailingslashit(wp_normalize_path($uploads['basedir']));
        if (strpos($file, $base) === 0) $file = substr($file, strlen($base));
        if ($file === '' || preg_match('~(^|/)\.\.?(/|$)|^[a-zA-Z]:|^/|[?#]~', $file)) return '';
        if (!preg_match('/\.(?:jpe?g|png|webp|gif|avif)$/i', $file)) return '';
        $url = trailingslashit($uploads['baseurl']) . implode('/', array_map('rawurlencode', explode('/', $file)));
        return self::valid_url($url) ? $url : '';
    }

    public static function render($id, $above_fold) {
        $url = self::url($id);
        if ($url === '') return '';
        $attributes = array('class' => 'dso-ap__poster', 'alt' => '', 'decoding' => 'async',
            'data-dso-poster-src' => $url,
            'data-dso-poster-id' => $id,
            'loading' => $above_fold ? 'eager' : 'lazy', 'fetchpriority' => $above_fold ? 'high' : 'auto',
            'sizes' => '(max-width: 640px) 100vw, (max-width: 1024px) 90vw, 960px');
        $html = wp_get_attachment_image($id, 'large', false, $attributes);
        $tags = new \WP_HTML_Tag_Processor($html);
        if (!$tags->next_tag('IMG')) {
            $meta = wp_get_attachment_metadata($id);
            $html = '<img src="' . esc_url($url) . '" ' . image_hwstring(absint($meta['width'] ?? 0), absint($meta['height'] ?? 0));
            foreach ($attributes as $key => $value) $html .= $key . '="' . esc_attr($value) . '" ';
            return $html . '/>';
        }
        // Keep valid lazy-load placeholders when another plugin supplies data-src.
        $has_lazy_url = self::valid_url($tags->get_attribute('data-src')) || self::valid_url($tags->get_attribute('data-lazy-src'));
        if (!self::valid_url($tags->get_attribute('src')) && !$has_lazy_url) {
            $tags->set_attribute('src', $url);
        }
        foreach (array('data-src', 'data-lazy-src') as $attribute) {
            $value = $tags->get_attribute($attribute);
            if (is_string($value) && !self::valid_url($value)) $tags->set_attribute($attribute, $url);
        }
        foreach (array('srcset', 'data-srcset', 'data-lazy-srcset') as $attribute) {
            $value = $tags->get_attribute($attribute);
            if (!is_string($value)) continue;
            $candidates = array();
            foreach (explode(',', $value) as $candidate) {
                if (preg_match('/^(\S+)\s+([1-9][0-9]*w|(?:[0-9]*\.)?[0-9]+x)$/', trim($candidate), $match)
                    && self::valid_url($match[1])) $candidates[] = trim($candidate);
            }
            if ($candidates) $tags->set_attribute($attribute, implode(', ', $candidates));
            else $tags->remove_attribute($attribute);
        }
        return $tags->get_updated_html();
    }

    /** Content filters/injectors can change URLs after the player has rendered. */
    public static function repair_content($html) {
        if (!is_string($html) || strpos($html, 'dso-ap__poster') === false) return $html;
        $tags = new \WP_HTML_Tag_Processor($html);
        $id = 0;
        while ($tags->next_tag()) {
            if ($tags->get_tag() === 'DIV' && $tags->get_attribute('data-dso-aparat')) {
                $hash = $tags->get_attribute('data-dso-aparat');
                $id = 0;
                if (preg_match('/^[a-zA-Z0-9]{1,40}$/D', $hash)) {
                    $data = Metadata::cached($hash);
                    $id = absint($data['poster_id'] ?? 0);
                }
            }
            if ($tags->get_tag() !== 'IMG' || !$tags->has_class('dso-ap__poster')) continue;
            $url = $tags->get_attribute('data-dso-poster-src');
            $image_id = absint($tags->get_attribute('data-dso-poster-id')) ?: $id;
            if (!self::valid_url($url)) $url = $image_id ? self::url($image_id) : '';
            if (!self::valid_url($url)) continue;
            if (!self::valid_url($tags->get_attribute('src'))
                && !self::valid_url($tags->get_attribute('data-src'))
                && !self::valid_url($tags->get_attribute('data-lazy-src'))) $tags->set_attribute('src', $url);
            foreach (array('srcset', 'data-srcset', 'data-lazy-srcset') as $attribute) {
                $value = $tags->get_attribute($attribute);
                if (!is_string($value)) continue;
                $candidates = array();
                foreach (explode(',', $value) as $candidate) {
                    $candidate = trim($candidate);
                    // A URL removed from the first candidate leaves only its descriptor.
                    if (preg_match('/^[1-9][0-9]*w$/D', $candidate)) $candidate = $url . ' ' . $candidate;
                    if (preg_match('/^(\S+)\s+([1-9][0-9]*w|(?:[0-9]*\.)?[0-9]+x)$/', $candidate, $match)
                        && self::valid_url($match[1])) $candidates[] = $candidate;
                }
                if ($candidates) $tags->set_attribute($attribute, implode(', ', $candidates));
                else $tags->remove_attribute($attribute);
            }
        }
        return $tags->get_updated_html();
    }
}
