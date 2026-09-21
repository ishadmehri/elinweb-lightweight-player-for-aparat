=== Elinweb Lightweight Player for Aparat ===
Contributors: imansh
Tags: aparat, video, performance, elementor, block
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add Aparat videos with local posters and click-to-play playback. Includes a Gutenberg block and an Elementor widget.

== Description ==

Paste an Aparat link, and the plugin prepares a local poster with a circular Play icon. The player loads after your visitor clicks Play, keeping video requests out of the initial page load.

Use either of these link formats:

* https://www.aparat.com/embed/ytf50k5 (recommended newer input format)
* https://www.aparat.com/v/ytf50k5

Replace ytf50k5 with your video's identifier.

Includes a Gutenberg block, an optional Elementor widget, and the lwpa_aparat shortcode. You can set a custom poster, aspect ratio, start time, or muted playback. Enable above-fold poster priority only for videos visible without scrolling.

The default browser player avoids Aparat's iframe and player JavaScript. An optional official Aparat player is also available. WP Rocket and Perfmatters Delay JavaScript exclusions are included.

Posters are stored in your media library and converted to WebP when supported. Preparation runs while editing, saving, or through WP-Cron, without synchronous Aparat requests during public page rendering. Playback depends on API/CDN availability and browser policies.

The plugin includes Persian interface translations. Find the short Persian guide in README.md or on GitHub: https://github.com/ishadmehri/elinweb-lightweight-player-for-aparat

Independently developed; not affiliated with or endorsed by Aparat or Elementor.
Author: Iman Shadmehri. Website: https://elinweb.ir

== Installation ==

1. Upload the installable ZIP through Plugins > Add New > Upload Plugin and activate it.
2. Add the Gutenberg block or Elementor widget and paste an /embed/ or /v/ Aparat link.
3. Save and allow poster preparation to finish. WP-Cron must run for background imports. Clear page/CDN caches after updating.

== Frequently Asked Questions ==

= How do I use the shortcode? =

[lwpa_aparat url="https://www.aparat.com/embed/ytf50k5" start_time="65" muted="true"]

= Does playback need one click? =

The browser player requests playback during the first click. It can retry muted playback if the browser rejects playback with sound. The optional official iframe may require an extra internal Play click.

= What cache exclusions are needed? =

For independent CDN/REST caching, exclude the lwpa_aparat_stream action on /wp-admin/admin-ajax.php and /wp-json/lightweight-player/v1/stream/* from caching. Playback responses send Cache-Control: no-store. Video bytes come directly from Aparat's CDN.

== Screenshots ==

1. Gutenberg block preview with an Aparat URL, automatically prepared poster, and circular Play button.
2. Elementor widget preview with controls for the Aparat URL, optional title and poster, aspect ratio, and above-the-fold priority.
3. Elementor playback settings for the native one-click player or official Aparat player, start time, muted playback, and recommendations.

== External services ==

Aparat (https://www.aparat.com/) supplies public video metadata, poster images, and video files.

During editing, saving, background poster preparation, or a native playback request, your server sends the video identifier to https://www.aparat.com/etc/api/video/videohash/{identifier}. Aparat receives the server IP and normal HTTP request information. Poster images are downloaded from validated Aparat CDN hosts and saved locally.

After Play, the visitor's browser requests the video from Aparat's CDN, which receives the visitor IP and normal browser request information. Video URLs are cached briefly on the server, not in article HTML. In optional official-player mode, the browser loads an Aparat iframe after Play; its requests, cookies, and analytics are controlled by Aparat.

No Aparat account or API key is needed. The plugin does not send WordPress credentials or private article content and does not add its own telemetry.
Rules: https://www.aparat.com/community-guideline
Support: https://support.aparat.com/

== Changelog ==

= 2.0.5 =
* Adopt a distinctive name and directory slug, retaining saved blocks, Elementor widgets, and shortcodes.
* Load the tiny player CSS through the WordPress styles API without an extra stylesheet request.

= 2.0.4 =
* Give the plugin and author headers distinct, relevant URLs for WordPress.org submission.

= 2.0.3 =
* Restore WordPress 6.3 compatibility in poster repair.
* Make the bundled Persian translation catalog readable by the WordPress 6.3 POMO parser.
* Verify WordPress 6.3, 6.6 and 6.8 across PHP 7.4 through 8.3, with and without Elementor.

= 2.0.2 =
* Shorten the guide and clarify /embed/ and /v/ input links, Gutenberg, and Elementor support.
* Improve playback input sanitization and scope safe redirects to a validated Aparat CDN host.
* Document intentional public playback and pre-escaped HTML for automated checks.
* Clean the production package and restore the English directory readme.
