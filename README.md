# Dadsoo Aparat Performance

A WordPress plugin for performance-focused Aparat embeds, with a Gutenberg block, an optional Elementor widget, a shortcode, and a PHP API for article injectors.

[Download the installable ZIP](https://github.com/ishadmehri/dadsoo-aparat-performance/releases/latest) · [راهنمای فارسی](README-FA.md)

## Features

- Accepts Aparat links such as `https://www.aparat.com/v/ytf50k5`, video hashes, and individual legacy script/iframe embed codes.
- Shows a local poster with a circular, icon-only Play control and an accessible name.
- Creates the player only after a click. No video element, Aparat iframe, or Aparat player script is loaded by this plugin before the click.
- Uses the native browser MP4 player by default and starts playback from the first click when media is available. Browser restrictions can cause a muted retry.
- Automatically retries an alternative route/CDN on initial media failure or timeout. A final failure shows an in-page retry control instead of sending the visitor to Aparat.
- Imports posters into the WordPress media library once per video, with WebP conversion when supported by the server.
- Supports start time, muted playback, poster override, aspect ratio, and above-fold poster priority. Optional official Aparat mode also supports title/icon visibility and same-channel recommendations.
- Loads one dependency-free frontend script only on pages containing its embeds, with Delay JS exclusions for WP Rocket and Perfmatters.

## Installation

Requires WordPress 6.3+ and PHP 7.4+. Tested with WordPress 7.1. Elementor is optional.

1. Download `dadsoo-aparat-performance-1.2.1.zip` from [Releases](https://github.com/ishadmehri/dadsoo-aparat-performance/releases).
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, upload the ZIP, and activate it.
3. Add the **آپارات بهینه دادسو** block or Elementor widget and paste the Aparat link.
4. Clear page/CDN caches after replacing an older plugin version or embed.

The release ZIP contains the `dadsoo-aparat-performance` plugin directory. Use that asset for installation.

## Shortcode

```text
[dadsoo_aparat url="https://www.aparat.com/v/ytf50k5"]
[dadsoo_aparat url="https://www.aparat.com/v/ytf50k5" start_time="65" muted="true"]
```

To use the official Aparat iframe player:

```text
[dadsoo_aparat url="https://www.aparat.com/v/ytf50k5" player="aparat" title_show="true" recom="self"]
```

The current official player may require a second internal Play click. `title_show` and `recom` apply only to official-player mode.

## Existing article injectors

Replace your injector's individual embed output with:

```php
if (function_exists('dadsoo_aparat_performance_render')) {
    $video_html = dadsoo_aparat_performance_render($aparat_url, array(
        'startTime' => 65,
        'muted' => true,
    ));
}
```

The function also accepts one stored legacy embed snippet. Installing the plugin does not rewrite existing article content automatically. Preserve any accurate `VideoObject` schema already produced by your injector.

## Performance and delivery

Posters are served from your own site. Missing poster metadata is prepared in the editor/save flow or a background WP-Cron job; public rendering does not make a synchronous Aparat API call.

After a click, a public WordPress endpoint resolves a temporary MP4 URL from Aparat and redirects the browser to its CDN. PHP does not proxy video bytes. Signed source URLs are cached briefly on the server and never stored in cached article HTML. Live playback depends on host access to the Aparat API and visitor access to its CDN; no PageSpeed score is guaranteed.

Exclude the `dadsoo_aparat_stream` action on `/wp-admin/admin-ajax.php` and `/wp-json/dadsoo-aparat/v1/stream/*` from independent CDN/REST caching. The plugin sends `Cache-Control: no-store` for those media responses.

Enable above-fold poster priority only for posters visible without scrolling. WP-Cron must run for background poster imports. See the [Persian guide](README-FA.md) for the full settings and cache integration notes.

## Development checks

Run the dependency-free player regression tests with Node.js:

```sh
node --test tests/player.test.cjs
```

The nine tests cover click-to-play, settings, dynamic embeds, route fallback, loading timeout, and in-page retry after failure. PHP syntax was checked with PHP 8.3; WordPress/Elementor integration and real Aparat MP4 playback were also checked in an isolated development environment.

## License

GPL-2.0-or-later. See [LICENSE.txt](LICENSE.txt).
