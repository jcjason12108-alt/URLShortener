=== URL Shortener ===
Contributors: jcjason12108-alt
Tags: url shortener, qr code, redirects
Requires at least: 5.8
Tested up to: 6.9.4
Requires PHP: 7.4
Stable tag: 1.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create branded short URLs and static QR redirects from one tabbed WordPress admin screen.

== Description ==

URL Shortener creates branded short links with configurable base paths, expiration dates, active status, hit tracking, and QR previews.

The Static QR Redirect tab manages fixed QR routes for `/qr`, `/qr2`, `/qr3`, and `/qr4`, letting printed QR codes point to destinations that can be changed later.

== Installation ==

1. Upload the `URLShortener` folder to `/wp-content/plugins/`.
2. Activate `URL Shortener` from the WordPress Plugins screen.
3. Visit `Settings > Permalinks` and click `Save` once after activation.

== Changelog ==

= 1.4.1 =
* Added Plugin Update Checker for GitHub branch-based automatic updates.
* Added optional `PLUGIN_UPDATE_GITHUB_TOKEN` support for private GitHub access.
* Updated WordPress compatibility metadata to `Tested up to: 6.9.4`.

= 1.4.0 =
* Added Static QR Redirect module with fixed routes for `/qr`, `/qr2`, `/qr3`, and `/qr4`.
* Added a tabbed admin screen combining URL short links and static QR redirects.
* Added QR scan tracking for each static QR route.

= 1.3.2 =
* Improved Expiration column layout.
* Fixed legacy row fallback behavior for missing `base_path` values.
* Fixed expiration input overflow in admin tables.
