=== Lightweight Player for Aparat ===
Contributors: imansh
Tags: aparat, video, performance, elementor, block
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed Aparat videos with local posters and click-to-play playback. Includes a WordPress block, Elementor widget, shortcode, and PHP API.

== Description ==

Lightweight Player for Aparat replaces the initial video player with a local poster and a circular Play icon. The player is created only when the visitor clicks Play.

The default mode uses the browser's native video player. This avoids loading Aparat's iframe and player JavaScript, and requests playback during the first click. An optional official Aparat iframe mode is also available.

Paste a link such as https://www.aparat.com/v/ytf50k5 into the block or widget. The plugin retrieves the public title and poster and stores the poster in your WordPress media library. Individual Aparat script and iframe snippets can also be used as input; their code is parsed and never executed.

Features:

* WordPress editor block and optional Elementor widget.
* Local, responsive posters with WebP conversion when supported by your server.
* Circular, icon-only Play control with an accessible name and keyboard activation.
* No Aparat iframe, video element, or player script before the click.
* Start time, muted playback, aspect ratio, custom title, and poster override.
* Above-fold poster priority for videos visible without scrolling.
* Optional official-player settings for title visibility and same-channel recommendations.
* Automatic fallback for initial media errors or timeouts and an in-page retry control on final failure.
* One dependency-free frontend script on pages containing the plugin's videos.
* WP Rocket and Perfmatters Delay JavaScript exclusions for the playback script.
* Shortcode and PHP integration for custom video injectors.

Poster preparation runs in the editor, on save, or through WP-Cron. Rendering a public page does not make a synchronous Aparat API request. Playback depends on video availability and access to Aparat's API and CDN. Results depend on the rest of your page; a particular PageSpeed score is not guaranteed.

This plugin is independently developed and is not affiliated with or endorsed by Aparat or Elementor.

Author: Iman Shadmehri
Website: https://elinweb.ir

== Installation ==

1. Upload the lightweight-player-for-aparat folder to /wp-content/plugins/, or upload the installable ZIP through Plugins > Add New > Upload Plugin.
2. Activate Lightweight Player for Aparat.
3. Add the video block in the WordPress editor or the video widget in Elementor and paste the Aparat link.
4. Save the article and allow poster preparation to finish. WP-Cron must run for background imports.
5. Clear page and CDN caches after changing embeds or upgrading.

Elementor is optional. The block, shortcode, and PHP integration work without it.

Migrating from Dadsoo Aparat Performance: deactivate the old plugin before activating this plugin. Do not keep both active. Old saved blocks, Elementor widgets, shortcodes, and imported posters remain supported. The plugin folder and entry filename have changed: install the new ZIP separately instead of expecting it to overwrite the old folder. Clear caches and update custom playback cache exclusions.

== Frequently Asked Questions ==

= How do I use a shortcode? =

[lwpa_aparat url="https://www.aparat.com/v/ytf50k5"]

[lwpa_aparat url="https://www.aparat.com/v/ytf50k5" start_time="65" muted="true" ratio="16/9"]

Use poster="123" for a WordPress image attachment ID, title="Your title" for a custom title, and above_fold="true" only when the poster is visible without scrolling.

For the official player:

[lwpa_aparat url="https://www.aparat.com/v/ytf50k5" player="aparat" title_show="true" recom="self"]

= Is this only iframe lazy loading? =

No. The default native mode replaces the iframe entirely with a local poster and creates a browser video player after a click. It does not download Aparat's player JavaScript.

= Does the video play with one click? =

Native mode requests playback inside the visitor's click handler. Media availability and browser policies still apply; the player can retry muted playback if the browser rejects it. Official iframe mode requests autoplay, but Aparat or the browser may require an additional internal Play click.

= Does the plugin contact Aparat before the visitor clicks? =

Your server contacts Aparat to prepare posters while editing, saving, or running a background job. The visitor receives the stored poster from your site. This plugin makes no browser request to Aparat before Play. Other embeds and plugins on the page may behave differently.

= What if poster preparation fails? =

The Play control remains available. Select an image from the media library or retry preparation later. WebP support is optional; a validated original image format is used when conversion is unavailable.

= How do I use a custom injector? =

Call lightweight_player_for_aparat_render($aparat_url, $args) and insert the returned HTML. Arguments use block attribute names, including startTime, muted, posterId, aboveFold, and playerType. The function accepts one legacy embed snippet. Arbitrary existing article HTML is not automatically rewritten.

= Are old embeds preserved after the rename? =

The dadsoo_aparat shortcode, dadsoo/aparat-performance block, dadsoo-aparat-performance Elementor widget, and dadsoo_aparat_performance_render() function remain supported as compatibility aliases. Saved content does not need to be recreated. Imported poster cache and attachment records are reused. New embeds use the new identifiers.

= What cache settings are needed? =

The plugin excludes its player script from WP Rocket and Perfmatters Delay JavaScript. Public playback responses send Cache-Control: no-store. With independent CDN or REST caching, exclude the lwpa_aparat_stream action on /wp-admin/admin-ajax.php and /wp-json/lightweight-player/v1/stream/* from caching. Preserve old endpoint exclusions while older cached markup remains in use. Video bytes come from Aparat's CDN; PHP does not proxy the file.

= Does this add video structured data? =

No. Preserve accurate VideoObject structured data supplied by your theme, SEO plugin, or existing injector.

== External services ==

This plugin requires Aparat for public video metadata, poster images, and playback.

Service: Aparat, https://www.aparat.com/

* During editor preview, saving, or background preparation, your server sends the video identifier to https://www.aparat.com/etc/api/video/videohash/{identifier} to retrieve its public title and poster URL. It downloads the poster from a validated Aparat CDN URL and stores it locally. Aparat receives the server's IP address and standard HTTP request information.
* After Play in native mode, a public endpoint on your site sends the video identifier to Aparat's API to resolve a temporary MP4 URL. The visitor's browser requests the video from Aparat's CDN, exposing its IP address and normal browser request information to that service. Source URLs are briefly cached on your server, not embedded in cached article HTML.
* In optional official-player mode, the browser loads https://www.aparat.com/video/video/embed/videohash/{identifier}/vt/frame after Play. Requests, cookies, and analytics within that iframe are controlled by Aparat.

No Aparat account or API key is required. This plugin does not send WordPress passwords, credentials, or private article content to Aparat and does not add its own analytics or telemetry.

Aparat's published rules: https://www.aparat.com/community-guideline
Aparat support: https://support.aparat.com/

== Changelog ==

= 2.0.0 =
* Rename the plugin to Lightweight Player for Aparat with the lightweight-player-for-aparat directory and text domain.
* Set the author to Iman Shadmehri, contributor to imansh, and plugin/author websites to https://elinweb.ir.
* Update code namespaces, block, widget, shortcode, asset, and API identifiers.
* Preserve old saved embeds, PHP integrations, playback endpoints, and imported posters through compatibility aliases.
* Rewrite installation, external-service disclosure, and migration documentation.

= 1.2.4 =
* Repair poster sources and malformed responsive candidates after WordPress and Elementor content filters.
* Preserve a canonical poster source without additional frontend requests.

= 1.2.3 =
* Update author and website metadata.

= 1.2.2 =
* Repair blank poster sources and malformed srcset descriptors using WordPress upload metadata.
* Preserve responsive candidates and valid lazy-loading attributes.

= 1.2.1 =
* Improve native playback delivery, fallback, timeout handling, and in-page retry.

= 1.2.0 =
* Add native browser playback for click-to-play delivery.

== Upgrade Notice ==

= 2.0.0 =
New identity and folder. Deactivate Dadsoo Aparat Performance before activating this version. Old saved embeds and imported posters remain supported. Clear page/CDN caches and update playback cache exclusions.
