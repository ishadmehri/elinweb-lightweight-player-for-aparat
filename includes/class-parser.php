<?php
namespace Dadsoo\Aparat;
defined('ABSPATH') || exit;

final class Parser {
    public static function hash($input) {
        return self::video($input)['hash'];
    }

    public static function video($input) {
        $input = trim((string) $input);
        // Also accept hashes and existing embed HTML for injector integration.
        if (preg_match('/^[a-zA-Z0-9]{1,40}$/D', $input)) {
            return array('hash' => $input, 'options' => array());
        }
        $urls = array($input);
        if (preg_match_all('~<(?:iframe|script)\b[^>]*\bsrc\s*=\s*([\x22\x27])(.*?)\1~is', $input, $matches)) {
            $urls = $matches[2];
        }
        foreach ($urls as $url) {
            $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (strpos($url, '//') === 0) {
                $url = 'https:' . $url;
            }
            $parts = wp_parse_url($url);
            if (!is_array($parts) || !empty($parts['user']) || !empty($parts['pass']) ||
                (!empty($parts['port']) && (int) $parts['port'] !== 443) ||
                !in_array(strtolower($parts['host'] ?? ''), array('aparat.com', 'www.aparat.com'), true) ||
                !in_array(strtolower($parts['scheme'] ?? ''), array('http', 'https'), true)) {
                continue;
            }
            $path = $parts['path'] ?? '';
            if (preg_match('~^/(?:v|embed)/([a-zA-Z0-9]{1,40})/?$~D', $path, $m) ||
                preg_match('~^/video/video/embed/videohash/([a-zA-Z0-9]{1,40})/vt/frame/?$~D', $path, $m)) {
                $params = array();
                parse_str($parts['query'] ?? '', $params);
                return array('hash' => $m[1], 'options' => self::options($params));
            }
        }
        return array('hash' => '', 'options' => array());
    }

    /** Only documented parameters present in the user's official embed examples. */
    public static function options($params) {
        $options = array();
        foreach (array('muted', 'titleShow') as $key) {
            if (isset($params[$key]) && (is_bool($params[$key]) || is_scalar($params[$key]))) {
                $value = filter_var($params[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($value !== null) $options[$key] = $value;
            }
        }
        if (isset($params['startTime']) && is_scalar($params['startTime']) && !is_bool($params['startTime'])) {
            $seconds = filter_var($params['startTime'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => 2147483647)));
            if ($seconds !== false) $options['startTime'] = $seconds;
        }
        if (isset($params['recom']) && in_array($params['recom'], array('self', 'default'), true)) {
            $options['recom'] = $params['recom'];
        }
        return $options;
    }
}
