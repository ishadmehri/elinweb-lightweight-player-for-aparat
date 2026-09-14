=== Dadsoo Aparat Performance ===
Contributors: dadsoo
Tags: aparat, video, performance, elementor, block
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Optimized Aparat embeds for Gutenberg, Elementor, shortcodes and PHP injectors.
The initial public HTML contains a local poster and an accessible circular Play icon, never a video element or player iframe.
The default native browser player calls play() inside the original click and starts when media is ready.
An optional official Aparat iframe player retains Aparat-specific controls and recommendations.
One dependency-free frontend script is enqueued only when a video is rendered.
Tiny CSS is printed inline once per request. No custom fonts, icon libraries, jQuery or frontend metadata API.
Automatic posters are imported once into the WordPress media library and converted to WebP at quality 75 when the server image editor supports it.

== Installation ==
Upload dadsoo-aparat-performance.zip through Plugins > Add New > Upload Plugin and activate.
Gutenberg: insert "آپارات بهینه دادسو", paste https://www.aparat.com/v/ytf50k5.
Elementor: drag "آپارات بهینه دادسو" and paste the same URL.
Optional: custom title, replacement media-library poster, aspect ratio, above-fold poster priority and playback settings.
Playback controls: start time in seconds, muted, title/icon visibility, same-channel recommendations or Aparat defaults.
Only startTime, muted, titleShow and recom=self are forwarded; unknown query parameters are discarded.
When the input is old embed HTML or a URL with parameters, these settings are inherited unless explicitly overridden.
Keep above-fold priority off for videos below the initial viewport.

== Shortcode ==
[dadsoo_aparat url="https://www.aparat.com/v/ytf50k5"]
[dadsoo_aparat url="https://www.aparat.com/v/ytf50k5" title="عنوان" poster="123" ratio="9/16" above_fold="false"]
[dadsoo_aparat url="https://www.aparat.com/v/ytf50k5" start_time="65" muted="true" title_show="true" recom="self"]

PHP playback settings:
dadsoo_aparat_performance_render($url, array('startTime' => 65, 'muted' => true, 'titleShow' => true, 'recom' => 'self'));
Explicit false and zero override imported settings. recom=default omits the channel restriction; it does not guarantee recommendations are disabled.

For an existing injector:
if (function_exists('dadsoo_aparat_performance_render')) {
    $video_html = dadsoo_aparat_performance_render($aparat_url);
}
The PHP API also accepts the individual old iframe/script embed HTML, not an entire article.
Output is replaced only when your injector calls the API; installing this plugin does not rewrite existing embeds automatically.

== External services ==
Aparat public video metadata endpoint: https://www.aparat.com/etc/api/video/videohash/HASH
While editing, or in a background WP-Cron job, the server sends the public video hash to Aparat to get its title and poster.
The poster is downloaded from an Aparat CDN and stored locally. No account token is required.
When a visitor clicks Play in native mode, the server requests the public MP4 URL from that API and redirects the browser to the Aparat CDN. Video bytes do not pass through PHP.
In optional official-player mode, the visitor loads the Aparat iframe after clicking.
Aparat terms: https://www.aparat.com/terms
Aparat site: https://www.aparat.com/
The endpoint can change or be blocked by the host. Missing posters do not prevent playback; unavailable MP4 sources show a retry icon and error status without navigating away.

== Performance and compatibility ==
No Aparat request from the visitor before clicking, provided other unoptimized embeds are absent.
No synchronous external network call during public rendering. Missing posters are scheduled in WP-Cron.
Metadata is prepared in the editor/save flow and reused per hash. More than three uncached videos in one save are processed in the background.
WP-Cron needs to run for background posters. WP Rocket post cache is invalidated when a background poster becomes available.
Other page-cache/CDN products may need their page cache cleared after a background import.
WordPress media attachment IDs are used for local responsive posters; duplicate hashes reuse the imported attachment.
The player script is automatically excluded from WP Rocket and Perfmatters Delay JS.
If unused-CSS removal changes appearance, safelist .dso-ap and its child classes.
Frontend assets must exist on the original page for purely client-side later injections.
The preview in Gutenberg does not play videos; test playback on the published page.
Native mode applies startTime and muted; titleShow and recom are only meaningful in official Aparat mode (player="aparat" in shortcode or playerType => 'aparat' in PHP).
The current official Aparat player may require a second internal Play click despite autoplay=true. Native mode avoids that overlay.
If browser policy rejects playback with sound, native mode retries muted; sound can be enabled with the video controls.
The default public media redirect uses wp-admin/admin-ajax.php?action=dadsoo_aparat_stream&hash=HASH, avoiding REST authentication filters. Exclude this action and /wp-json/dadsoo-aparat/v1/stream/* from independent CDN/REST caching. Responses send Cache-Control: no-store. Signed source URLs are cached server-side for only 60 seconds and never printed in article HTML.
Initial loading times out after 15 seconds. A media error or timeout automatically retries the REST route with a different Aparat CDN when the API supplies one. A second failure restores the Play icon with a status message. Normal clicks retry in place; only explicit modified clicks follow the Aparat page link.
Modified clicks open the Aparat page normally.
Video schema is not generated. Preserve existing accurate VideoObject schema in your injector.
Uninstalling/deactivating preserves imported posters and metadata; deactivation removes pending plugin cron events.

== Changelog ==
= 1.2.1 =
Fix error fallback navigating to Aparat on the next click. Use public admin-ajax media redirect, automatically retry an alternative CDN through REST, bound initial loading wait, and show inline retry/error status. No additional request before click.
= 1.2.0 =
Circular icon-only Play control. Default click-to-play native MP4 player with immediate play() invocation, optional official Aparat iframe, on-demand no-store stream redirect and unavailable-source link fallback. No video or Aparat player request before click.
= 1.1.0 =
Playback settings in Gutenberg, Elementor, shortcode and PHP API. Preserve whitelisted options from official script/iframe input. No additional frontend dependency or request before click.
= 1.0.0 =
Initial version: Gutenberg block, optional Elementor widget, shortcode, injector API, cached local posters and click-to-load player.
