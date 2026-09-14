<?php
// Integration regression against the disposable WordPress installation.
require $argv[1];
use Dadsoo\Aparat\Poster;
function assert_poster($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$id = wp_insert_attachment(array('post_title' => 'Poster regression', 'post_mime_type' => 'image/webp', 'post_status' => 'inherit'));
update_post_meta($id, '_wp_attached_file', '2026/09/aparat-regression.webp');
wp_update_attachment_metadata($id, array('file' => '2026/09/aparat-regression.webp', 'width' => 900, 'height' => 506,
    'sizes' => array('medium' => array('file' => 'aparat-regression-300x169.webp', 'width' => 300, 'height' => 169, 'mime-type' => 'image/webp'))));
add_filter('pre_http_request', function () { throw new RuntimeException('Poster render made a network request'); });
$upload_url = trailingslashit(wp_get_upload_dir()['baseurl']) . '2026/09/aparat-regression.webp';
function poster_attributes($html) {
    $tags = new WP_HTML_Tag_Processor($html);
    assert_poster($tags->next_tag('IMG'), 'Missing image');
    return $tags;
}
try {
    $normal = poster_attributes(Poster::render($id, false));
    assert_poster($normal->get_attribute('src') === $upload_url, 'Normal URL changed');
    assert_poster(strpos($normal->get_attribute('srcset'), '300w') !== false, 'Responsive candidates lost');
    assert_poster($normal->get_attribute('loading') === 'lazy', 'Below-fold poster no longer lazy');
    $broken = function ($attrs) use ($upload_url) {
        $attrs['src'] = '';
        $attrs['srcset'] = ' 900w, ' . str_replace('.webp', '-300x169.webp', $upload_url) . ' 300w';
        return $attrs;
    };
    add_filter('wp_get_attachment_image_attributes', $broken);
    $repaired = poster_attributes(Poster::render($id, true));
    assert_poster($repaired->get_attribute('src') === $upload_url, 'Blank src not repaired');
    assert_poster(strpos($repaired->get_attribute('srcset'), '900w') === false, 'Bare descriptor survives');
    assert_poster(strpos($repaired->get_attribute('srcset'), '300w') !== false, 'Valid responsive candidate removed');
    assert_poster($repaired->get_attribute('loading') === 'eager' && $repaired->get_attribute('fetchpriority') === 'high', 'Above-fold priority lost');
    remove_filter('wp_get_attachment_image_attributes', $broken);

    $empty_src = function () { return array('', 900, 506, false); };
    $empty_url = function () { return ''; };
    add_filter('wp_get_attachment_image_src', $empty_src);
    add_filter('wp_get_attachment_url', $empty_url);
    assert_poster(Poster::url($id) === $upload_url, 'Upload metadata fallback failed');
    $fallback = poster_attributes(Poster::render($id, false));
    assert_poster($fallback->get_attribute('src') === $upload_url, 'Fallback not rendered');
    remove_filter('wp_get_attachment_image_src', $empty_src);
    remove_filter('wp_get_attachment_url', $empty_url);

    $lazy = function ($attrs) use ($upload_url) {
        $attrs['src'] = 'data:image/svg+xml,%3Csvg%3E%3C/svg%3E';
        $attrs['data-src'] = $upload_url;
        $attrs['data-srcset'] = '900w, ' . $upload_url . ' 900w';
        return $attrs;
    };
    add_filter('wp_get_attachment_image_attributes', $lazy);
    $result = poster_attributes(Poster::render($id, false));
    assert_poster(strpos($result->get_attribute('src'), 'data:image/') === 0, 'Lazy placeholder changed');
    assert_poster($result->get_attribute('data-src') === $upload_url, 'Lazy source changed');
    assert_poster($result->get_attribute('data-srcset') === $upload_url . ' 900w', 'Lazy srcset not repaired');
    remove_filter('wp_get_attachment_image_attributes', $lazy);

    $empty_html = function () { return ''; };
    add_filter('wp_get_attachment_image', $empty_html);
    assert_poster(poster_attributes(Poster::render($id, false))->get_attribute('src') === $upload_url, 'Empty HTML fallback failed');
    remove_filter('wp_get_attachment_image', $empty_html);
    // Recreate a later content filter deleting the URL after Renderer has returned.
    add_filter('pre_option_dso_ap_video_stt8106', function () use ($id) { return array('poster_id' => $id); });
    $late_damage = function ($html) use ($upload_url) { return str_replace($upload_url, '', $html); };
    add_filter('the_content', $late_damage, 999999);
    $fresh = '<div data-dso-aparat="stt8106">' . Poster::render($id, false) . '</div>';
    $late_result = poster_attributes(apply_filters('the_content', $fresh));
    assert_poster($late_result->get_attribute('src') === $upload_url, 'Late content filter damage not repaired');
    assert_poster(strpos($late_result->get_attribute('srcset'), $upload_url . ' 900w') !== false, 'Late missing srcset URL not restored');
    remove_filter('the_content', $late_damage, 999999);
    $legacy = '<div data-dso-aparat="stt8106"><img class="dso-ap__poster" src="" srcset=" 900w, ' . str_replace('.webp', '-300x169.webp', $upload_url) . ' 300w"></div>';
    $repaired_legacy = poster_attributes(apply_filters('the_content', $legacy));
    assert_poster($repaired_legacy->get_attribute('src') === $upload_url, 'Legacy injected poster source not recovered');
    assert_poster(strpos($repaired_legacy->get_attribute('srcset'), $upload_url . ' 900w') !== false, 'Legacy srcset not restored');
    $unrelated = '<img src="" srcset="900w" class="theme-image">';
    assert_poster(Poster::repair_content($unrelated) === $unrelated, 'Unrelated image modified');
    $elementor = poster_attributes(apply_filters('elementor/widget/render_content', $legacy));
    assert_poster($elementor->get_attribute('src') === $upload_url, 'Elementor final output not repaired');
    echo "PASS: normal responsive poster, exact blank-src/900w regression, metadata fallback, loading priorities, lazy-plugin compatibility, empty HTML, zero HTTP.\n";
    echo "PASS: late content damage, legacy injected markup, Elementor output, unrelated images unchanged.\n";
} finally {
    // Only remove the attachment record created by this test; no media files were created.
    wp_delete_post($id, true);
}
