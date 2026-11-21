=== URL Shortener ===
Contributors: jcjason12108-alt
Tags: url shortener, qr code, redirects
Requires at least: 5.8
Tested up to: 7.1.5
Requires PHP: 7.4
Stable tag: 1.4.8
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

= 1.4.8 =
* Reject invalid expiration dates instead of clearing them, and interpret valid dates in the site timezone.
* Fail closed when a stored expiration is damaged and prevent browsers from caching short-link redirects.
* Reject malformed admin form values before passing them to string sanitizers.

= 1.4.7 =
* Updated WordPress compatibility metadata to 7.1.5.

= 1.4.6 =
* Rejected malformed public short-link and static QR query values before processing them.
* Ran the legacy base-path migration only once instead of on every request.
* Updated Plugin Update Checker to 6.0 with a newer bundled Markdown parser.
* Generated QR previews and downloads locally in the admin browser.

= 1.4.5 =
* Expired, inactive, missing, and invalid short links now return a proper 404 response with the site's normal 404 styling.
* Prevented WordPress from guessing a different destination for unavailable short links.

= 1.4.4 =
* Updated the bundled Plugin Update Checker library from 5.6 to 5.7.
* Removed a tracked backup source file and tightened an admin permission error message.

= 1.4.3 =
* Updated WordPress compatibility metadata to `Tested up to: 7.0`.
* Confirmed PHP 7.4 minimum compatibility for WordPress 7.0.
* Hardened admin request handling and output escaping.

= 1.4.2 =
* Added nonce verification to the admin QR proxy endpoint.
* Added insert failure handling when creating short URLs.
* Improved Static QR scan counting to avoid lost updates under concurrent traffic.
* Escaped remaining computed admin output.

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
