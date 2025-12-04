✅ README.md (Ready for GitHub)

# URL Shortener – WordPress Plugin
Creates short, branded URLs for images, files, or any external link. Designed for fast redirect handling, clean admin management, and optional expiration dates. Perfect for QR codes, flyers, social links, and internal reference URLs.

**Author:** Jason Cox  
**Version:** 1.3.2  
**License:** GPL-2.0+

---

## Features

### 🔗 Short URL Generator
- Create fully branded short URLs like:

https://example.com/go/slug

- Automatic or custom slugs.
- Multiple base paths (e.g., `/go/`, `/pdf/`, `/promo/`).

### 🧭 Redirect Handling
- Fast server-side redirect using `template_redirect`.
- Hit counter increments on every visit.
- Proper 404 handling for missing or expired links.

### ⏱ Expiration Options
- Optional expiration date/time.
- Auto-disables expired links.
- “Leave blank for Never” behavior.

### 🧹 Clean Admin Interface
- Complete management table for all short URLs.
- Enable/disable toggle.
- Edit expiration date.
- Delete link instantly.
- QR code preview generated automatically.

### 📐 Visual Enhancements
- Fixed-width expiration input field.
- Clean layout across all admin columns.
- QR images aligned and non-overlapping.

### 🧱 Database Design
Table: `{prefix}_image_shortener`

Columns:
- `id`
- `slug`
- `original_url`
- `base_path`
- `is_active`
- `expires_at`
- `hits`
- `created_at`

## Installation

1. Upload the plugin folder to  
 `/wp-content/plugins/url-shortener/`

2. Activate via **Plugins → Installed Plugins**.

3. Go to **URL Shortener** in the WordPress admin menu.

---

## How to Use

### **Create a Short URL**
1. Go to **URL Shortener → Create Short URL**.
2. Enter the original URL.
3. Choose a custom slug (optional).
4. Select a base path (e.g., `/go/`).
5. Choose Active/Inactive.
6. Optionally add an expiration date.
7. Save.

### **Manage Existing Short URLs**
You can:
- Copy/visit the short link
- Disable/enable
- Edit expiration
- Clear expiration
- View hits
- Delete the URL

---

## Rewrite Rules
Base paths automatically generate rewrite rules, such as:

^go/([^/]+)/?$

These map to a query var `ius_slug`, which triggers redirect handling.

Rewrite rules flush on:
- Plugin activation
- Base path updates

---

## QR Code Generation
QR codes are generated using:

https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={short_url}

Each row displays a compact QR preview.

---

## Security Notes
- All admin actions require valid WordPress nonces.
- Only admins with `manage_options` can create/delete/modify URLs.
- Redirects use `esc_url_raw` for safe forwarding.
- Reserved slug list protects system paths.

---

## Minimum Requirements
- WordPress 5.8+
- PHP 7.4+
- Rewrite rules enabled

---

## License
This plugin is licensed under the GNU GPL v2.0 or later.  
See: https://www.gnu.org/licenses/gpl-2.0.html

---

## Contributing
Pull requests and suggestions are welcome.  
Please include detailed information about feature requests.

---

## Maintainer
**Jason Cox**  
GitHub: https://github.com/jcjason12108-alt
