✅ CHANGELOG.md (Ready for GitHub)

# Changelog
All notable changes to this project will be documented here.

---

## [1.4.1] - 2026-05-06
### Added
- Plugin Update Checker 5.6 vendored under `plugin-update-checker/`.
- GitHub branch-based automatic update checks for the `main` branch.
- Optional `PLUGIN_UPDATE_GITHUB_TOKEN` support for private GitHub access.
- WordPress `readme.txt` metadata for update and compatibility checks.

### Changed
- Updated plugin compatibility metadata to `Tested up to: 6.9.4`.

---

## [1.4.0] - 2026-04-08
### Added
- Static QR Redirect module with fixed routes for `/qr`, `/qr2`, `/qr3`, and `/qr4`.
- Tabbed WordPress admin screen combining URL short links and static QR redirects.
- QR scan tracking for each static QR route.

### Changed
- Plugin package now includes both `URLShortner.php` and `StaticQRRedirect.php`.
- README updated to document the merged URL Shortener and Static QR Redirect plugin.

---

## [1.3.2] – 2025-12-04
### Added
- Full visual redesign of the Expiration column.
- CSS class `ius-expire-input` for consistent input width.
- CSS class `ius-expire-cell` for column alignment.

### Changed
- Moved `$rows` query below update handlers so expiration changes refresh immediately.
- Improved QR column layout and alignment.
- Added “Leave blank for Never.” helper text.

### Fixed
- Fix: legacy rows missing `base_path` now fall back to primary base path.
- Fix: short URLs now render correctly for older rows.
- Fix: expiration input no longer overflows or overlaps other columns.

---

## [1.3.1] – 2025-12-03
### Removed
- Removed all custom domain support from:
  - Creation form
  - Table display
  - URL builder logic
  - Redirect handler
- Removed database upgrade path for `custom_domain`.

### Changed
- Cleanup of admin UI to simplify link creation.
- Full rewrite of slug/URL generation flow.

---

## [1.3.0] – 2025-12-03
### Added
- Base path fan-out (multiple base paths supported).
- Admin setting for base path list.
- Rewrite rule generation per path.

### Changed
- Short URL builder rewritten to include base path.
- Improved reserved slug handling.

---

## [1.2.0] – 2025-12-01
### Added
- Expiration date support.
- Active/inactive toggle.
- Hit counter.
- QR code preview.

---

## [1.0.0] – Initial Release
- Basic short URL creation.
- Redirect handler.
- Admin table UI.
