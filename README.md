# URL Shortener

WordPress plugin that combines two tools into one admin screen:

- `URL Shortener`: create branded short links such as `/go/example`
- `Static QR Redirect`: manage fixed QR routes such as `/qr`, `/qr2`, `/qr3`, and `/qr4`

Both features are managed from the same plugin page with tabs.

## Requirements

- WordPress: `5.8+`
- Tested up to: `6.9.4`
- PHP: `7.4+`

## Files

- `URLShortener.php`: main plugin bootstrap, admin screen, URL shortener logic
- `URLShortner.php`: compatibility loader for installs activated under the previous filename
- `StaticQRRedirect.php`: internal module for the Static QR Redirect tab

## Features

- Create short URLs for any valid `http` or `https` destination
- Support multiple base paths such as `go`, `img`, or `pdf`
- Enable or disable short links
- Optional expiration dates for short links
- Track hits for shortened URLs
- Show QR previews for generated short URLs
- Manage four fixed QR routes from a separate tab
- Track scans for each static QR route

## Admin Tabs

After activation, open `URL Shortener` in the WordPress admin menu.

Tabs:

- `URL Shortener`: create and manage short links
- `Static QR Redirect`: manage fixed QR destinations and QR downloads

## Short URL Behavior

Short URLs are built like this:

```text
https://your-site.com/{base-path}/{slug}
```

Example:

```text
https://your-site.com/go/summer-sale
```

## Static QR Routes

The plugin reserves these fixed routes:

- `/qr`
- `/qr2`
- `/qr3`
- `/qr4`

Each route can be pointed at a different destination URL from the `Static QR Redirect` tab.

This is useful when you want to print a QR code once and change its destination later without reprinting it.

## Installation

1. Place the plugin folder in your WordPress plugins directory.
2. Make sure both files stay together in the same folder:
   `URLShortener.php`, `URLShortner.php`, and `StaticQRRedirect.php`
3. Activate `URL Shortener` from the WordPress Plugins screen.
4. Visit `Settings > Permalinks` and click `Save` once after activation.

## Notes

- Pretty permalinks are recommended.
- If pretty permalinks are disabled, the static QR feature falls back to query-string URLs.
- QR images in the shortener list are proxied through WordPress admin AJAX.

## Version

Current merged plugin version: `1.4.2`
