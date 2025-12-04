<?php
/**
 * Plugin Name: URL Shortener
 * Plugin URI: https://github.com/jcjason12108-alt
 * Description: Creates short, branded URLs for images or any file URL. GitHub: https://github.com/jcjason12108-alt
 * Version: 1.3.2
 * Author: Jason Cox
 */

if (!defined('ABSPATH')) exit;

global $ius_table;
$ius_table = $GLOBALS['wpdb']->prefix . "image_shortener";

/* ============================================================================
   BASE PATH HELPERS
============================================================================ */
function ius_get_all_base_paths() {
    $stored = get_option('ius_base_paths', null);

    if (is_string($stored)) {
        $stored = preg_split('/\r\n|\r|\n/', $stored);
    }
    if (!is_array($stored)) $stored = [];

    $paths = [];
    foreach ($stored as $path) {
        $path = trim((string) $path, "/\t\n\r\0\x0B");
        $path = sanitize_title($path);
        if ($path !== '') $paths[] = $path;
    }

    $paths = array_values(array_unique($paths));

    if (empty($paths)) {
        $legacy = get_option('ius_base_path', 'go');
        $legacy = trim($legacy, "/\t\n\r\0\x0B");
        $legacy = sanitize_title($legacy);
        if ($legacy === '') $legacy = 'go';
        $paths = [$legacy];
    }

    return $paths;
}

function ius_get_base_path() {
    $paths = ius_get_all_base_paths();
    return $paths[0];
}

/**
 * Build short URL — no custom domain support.
 */
function ius_build_short_url($base_path, $slug) {
    $base_path = trim((string) $base_path, "/");
    if ($base_path === '') {
        $base_path = ius_get_base_path();
    }

    $slug = trim($slug, "/");
    if ($slug === '') return '';

    $domain = rtrim(home_url(), '/');
    return $domain . '/' . $base_path . '/' . $slug;
}

function ius_row_is_expired($row) {
    if (empty($row->expires_at)) return false;

    $timestamp = strtotime($row->expires_at . ' UTC');
    if ($timestamp === false) return false;

    return time() >= $timestamp;
}

function ius_format_datetime_local($datetime) {
    if (!$datetime) return '';
    $timestamp = strtotime($datetime . ' UTC');
    if ($timestamp === false) return '';
    return gmdate('Y-m-d\TH:i', $timestamp);
}

function ius_is_reserved_slug($slug) {
    $slug = sanitize_title($slug);
    $reserved = [
        'wp-admin', 'wp-login', 'login', 'admin', 'feed', 'json',
        'api', 'page', 'attachment', 'go', 'img'
    ];
    $reserved = apply_filters('ius_reserved_slugs', $reserved);
    return in_array($slug, $reserved, true);
}

/* ============================================================================
   ACTIVATION + SCHEMA MAINTENANCE
============================================================================ */
register_activation_hook(__FILE__, function () {
    global $wpdb, $ius_table;

    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $ius_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) NOT NULL UNIQUE,
        original_url TEXT NOT NULL,
        base_path VARCHAR(50) NOT NULL DEFAULT '',
        is_active TINYINT(1) DEFAULT 1,
        expires_at DATETIME NULL DEFAULT NULL,
        hits INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    ius_add_rewrite_rule();
    flush_rewrite_rules();
});

/**
 * Normalize empty base_path values.
 */
function ius_maybe_upgrade_schema() {
    global $wpdb, $ius_table;

    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $ius_table));
    if (!$table_exists) return;

    $default_base = ius_get_base_path();
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE $ius_table SET base_path = %s WHERE base_path = '' OR base_path IS NULL",
            $default_base
        )
    );
}
add_action('plugins_loaded', 'ius_maybe_upgrade_schema');

/* ============================================================================
   ADMIN MENU
============================================================================ */
add_action('admin_menu', function () {
    add_menu_page(
        'URL Shortener',
        'URL Shortener',
        'manage_options',
        'image-url-shortener',
        'ius_admin_page',
        'dashicons-admin-links',
        22
    );
});

/* ============================================================================
   ADMIN PAGE UI
============================================================================ */
function ius_admin_page() {
    global $wpdb, $ius_table;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to access this page.'));
    }

    $base_paths      = ius_get_all_base_paths();
    $base_path       = $base_paths[0];
    $base_paths_text = implode("\n", $base_paths);
?>
    <div class="wrap">

    <!-- VISUAL FIXES -->
    <style>
        .ius-expire-input {
            width: 165px !important;
            max-width: 100%;
            padding: 2px 6px;
            box-sizing: border-box;
        }
        .ius-expire-cell {
            min-width: 220px;
            vertical-align: top;
        }
    </style>

    <h1>URL Shortener</h1>

<?php
    /* ------------------------------  
       SAVE SETTINGS
    ------------------------------ */
    if (isset($_POST['ius_save_settings']) && check_admin_referer('ius_settings_nonce')) {

        if (isset($_POST['base_paths'])) {
            $raw   = wp_unslash($_POST['base_paths']);
            $lines = preg_split('/\r\n|\r|\n/', $raw);

            $new_paths = [];
            foreach ($lines as $line) {
                $value = trim($line, "/\t\n\r\0\x0B");
                $value = sanitize_title($value);
                if ($value !== '') $new_paths[] = $value;
            }
            if (empty($new_paths)) $new_paths = ['go'];

            update_option('ius_base_paths', $new_paths);

            // Normalize existing rows
            $primary = $new_paths[0];
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $ius_table SET base_path = %s WHERE base_path = '' OR base_path IS NULL",
                    $primary
                )
            );

            ius_add_rewrite_rule();
            flush_rewrite_rules();

            echo '<div class="updated"><p>Settings saved.</p></div>';

            $base_paths      = $new_paths;
            $base_path       = $base_paths[0];
            $base_paths_text = implode("\n", $base_paths);
        }
    }

    /* ------------------------------  
       CREATE SHORT URL
    ------------------------------ */
    if (isset($_POST['ius_create']) && check_admin_referer('ius_create_nonce')) {

        $original = esc_url_raw($_POST['original_url']);
        $slug     = sanitize_title($_POST['slug']);

        $selected_base = isset($_POST['base_path_choice'])
            ? sanitize_title($_POST['base_path_choice'])
            : $base_path;

        if (!in_array($selected_base, $base_paths, true)) {
            $selected_base = $base_path;
        }

        $is_active  = isset($_POST['is_active']) ? 1 : 0;
        $expires_at = null;

        if (!empty($_POST['expires_at'])) {
            $timestamp = strtotime(sanitize_text_field($_POST['expires_at']));
            if ($timestamp) {
                $expires_at = gmdate('Y-m-d H:i:s', $timestamp);
            }
        }

        // Auto-generate slug
        if ($slug === '') {
            do {
                $slug   = strtolower(wp_generate_password(6, false, false));
                $exists = (int) $wpdb->get_var(
                    $wpdb->prepare("SELECT COUNT(*) FROM $ius_table WHERE slug = %s", $slug)
                );
            } while (ius_is_reserved_slug($slug) || $exists > 0);
        }

        if (ius_is_reserved_slug($slug)) {
            echo '<div class="error"><p>That slug is reserved.</p></div>';
        } else {
            $exists = (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $ius_table WHERE slug = %s", $slug)
            );

            if ($exists > 0) {
                echo '<div class="error"><p>Slug already exists.</p></div>';
            } else {
                $wpdb->insert($ius_table, [
                    'slug'         => $slug,
                    'original_url' => $original,
                    'base_path'    => $selected_base,
                    'is_active'    => $is_active,
                    'expires_at'   => $expires_at
                ]);

                $short_url = ius_build_short_url($selected_base, $slug);
                echo '<div class="updated"><p>Short URL created: <strong>' . esc_html($short_url) . '</strong></p></div>';
            }
        }
    }

    /* ------------------------------  
       TOGGLE ACTIVE
    ------------------------------ */
    if (isset($_POST['ius_toggle']) && check_admin_referer('ius_toggle_nonce')) {
        $toggle_id  = intval($_POST['toggle_id']);
        $new_status = intval($_POST['new_status']);

        $wpdb->update($ius_table, ['is_active' => $new_status ? 1 : 0], ['id' => $toggle_id]);
        echo '<div class="updated"><p>Status updated.</p></div>';
    }

    /* ------------------------------  
       UPDATE EXPIRATION
    ------------------------------ */
    if (isset($_POST['ius_update_expiration']) && check_admin_referer('ius_expire_nonce')) {
        $expire_id  = intval($_POST['expire_id']);
        $expires_at = null;

        if (!empty($_POST['expires_at'])) {
            $timestamp = strtotime(sanitize_text_field($_POST['expires_at']));
            if ($timestamp) {
                $expires_at = gmdate('Y-m-d H:i:s', $timestamp);
            }
        }

        $wpdb->update($ius_table, ['expires_at' => $expires_at], ['id' => $expire_id]);
        echo '<div class="updated"><p>Expiration updated.</p></div>';
    }

    /* ------------------------------  
       CLEAR EXPIRATION
    ------------------------------ */
    if (isset($_POST['ius_clear_expiration']) && check_admin_referer('ius_expire_nonce')) {
        $expire_id = intval($_POST['expire_id']);
        $wpdb->update($ius_table, ['expires_at' => null], ['id' => $expire_id]);
        echo '<div class="updated"><p>Expiration cleared.</p></div>';
    }

    /* ------------------------------  
       DELETE URL
    ------------------------------ */
    if (isset($_POST['ius_delete']) && check_admin_referer('ius_delete_nonce')) {
        $id = intval($_POST['delete_id']);
        $wpdb->delete($ius_table, ['id' => $id]);
        echo '<div class="updated"><p>Short URL deleted.</p></div>';
    }

    /* ------------------------------  
       FETCH LATEST DATA (IMPORTANT)
    ------------------------------ */
    $rows = $wpdb->get_results("SELECT * FROM $ius_table ORDER BY id DESC");
?>

    <!-- ================================
         SETTINGS UI
    ================================= -->
    <h2>Settings</h2>
    <form method="POST" style="margin-bottom:20px;">
        <?php wp_nonce_field('ius_settings_nonce'); ?>
        <input type="hidden" name="ius_save_settings" value="1">

        <table class="form-table">
            <tr>
                <th><label for="base_paths">Base Paths</label></th>
                <td>
                    <textarea id="base_paths" name="base_paths" rows="4" class="large-text code"><?php echo esc_textarea($base_paths_text); ?></textarea>
                    <p class="description">One base path per line. Final URLs appear as <code>/go/slug</code>, <code>/pdf/slug</code>, etc.</p>
                </td>
            </tr>
        </table>

        <p><button class="button">Save Settings</button></p>
    </form>

    <hr>

    <!-- ================================
         CREATE SHORT URL
    ================================= -->
    <h2>Create Short URL</h2>

    <form method="POST">
        <?php wp_nonce_field('ius_create_nonce'); ?>
        <input type="hidden" name="ius_create" value="1">

        <table class="form-table">

            <tr>
                <th>Original URL</th>
                <td><input type="url" name="original_url" class="regular-text" required></td>
            </tr>

            <tr>
                <th>Custom Slug</th>
                <td><input type="text" name="slug" class="regular-text" placeholder="optional"></td>
            </tr>

            <tr>
                <th>Base Path</th>
                <td>
                    <select name="base_path_choice">
                        <?php foreach ($base_paths as $path): ?>
                            <option value="<?php echo esc_attr($path); ?>"><?php echo esc_html('/' . $path . '/'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th>Active</th>
                <td><label><input type="checkbox" name="is_active" value="1" checked> Enabled</label></td>
            </tr>

            <tr>
                <th>Expiration</th>
                <td>
                    <input type="datetime-local" name="expires_at" class="ius-expire-input">
                    <p class="description">Leave blank for Never.</p>
                </td>
            </tr>

        </table>

        <p><button class="button button-primary">Create Short URL</button></p>
    </form>

    <hr>

    <!-- ================================
         EXISTING SHORT URLS
    ================================= -->
    <h2>Existing Short URLs</h2>

    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>Slug</th>
                <th>Base Path</th>
                <th>Short URL</th>
                <th>Status</th>
                <th>Expiration</th>
                <th style="width:110px; text-align:center;">QR</th>
                <th>Original URL</th>
                <th>Hits</th>
                <th>Created</th>
                <th>Delete</th>
            </tr>
        </thead>

        <tbody>

<?php foreach ($rows as $r): ?>

<?php
    $row_base = trim((string) $r->base_path) !== '' ? $r->base_path : $base_path;

    $short_url = ius_build_short_url($row_base, $r->slug);
    $expired   = ius_row_is_expired($r);

    if (!$r->is_active)    $status_label = 'Inactive';
    elseif ($expired)      $status_label = 'Expired';
    else                   $status_label = 'Active';

    $expires_text = $r->expires_at
        ? get_date_from_gmt($r->expires_at, 'M j, Y H:i')
        : 'Never';
?>

            <tr>
                <td><?php echo esc_html($r->slug); ?></td>

                <td><?php echo esc_html('/' . $row_base . '/'); ?></td>

                <td>
                    <?php if ($short_url): ?>
                        <a href="<?php echo esc_url($short_url); ?>" target="_blank">
                            <?php echo esc_html($short_url); ?>
                        </a>
                    <?php endif; ?>
                </td>

                <td>
                    <?php echo esc_html($status_label); ?>

                    <form method="POST" style="margin-top:6px;">
                        <?php wp_nonce_field('ius_toggle_nonce'); ?>
                        <input type="hidden" name="ius_toggle" value="1">
                        <input type="hidden" name="toggle_id" value="<?php echo esc_attr($r->id); ?>">
                        <input type="hidden" name="new_status" value="<?php echo $r->is_active ? 0 : 1; ?>">
                        <button class="button button-small"><?php echo $r->is_active ? 'Disable' : 'Enable'; ?></button>
                    </form>
                </td>

                <td class="ius-expire-cell">
                    <?php echo esc_html($expires_text); ?>

                    <form method="POST" style="margin-top:6px;">
                        <?php wp_nonce_field('ius_expire_nonce'); ?>
                        <input type="hidden" name="ius_update_expiration" value="1">
                        <input type="hidden" name="expire_id" value="<?php echo esc_attr($r->id); ?>">

                        <input type="datetime-local"
                               name="expires_at"
                               class="ius-expire-input"
                               value="<?php echo esc_attr(ius_format_datetime_local($r->expires_at)); ?>">

                        <button class="button button-small">Save</button>
                    </form>

                    <?php if ($r->expires_at): ?>
                        <form method="POST" style="margin-top:6px;">
                            <?php wp_nonce_field('ius_expire_nonce'); ?>
                            <input type="hidden" name="ius_clear_expiration" value="1">
                            <input type="hidden" name="expire_id" value="<?php echo esc_attr($r->id); ?>">
                            <button class="button button-small">Clear</button>
                        </form>
                    <?php endif; ?>

                    <p class="description">Leave blank for Never.</p>
                </td>

                <td style="text-align:center;width:110px;">
                    <?php
                    if ($short_url) {
                        $qr_src = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . rawurlencode($short_url);
                        echo '<img src="' . esc_url($qr_src) . '" style="max-width:80px;height:auto;display:block;margin:0 auto;" />';
                    }
                    ?>
                </td>

                <td style="word-break:break-all;"><?php echo esc_html($r->original_url); ?></td>

                <td><?php echo esc_html($r->hits); ?></td>

                <td><?php echo esc_html($r->created_at); ?></td>

                <td>
                    <form method="POST" style="display:inline;">
                        <?php wp_nonce_field('ius_delete_nonce'); ?>
                        <input type="hidden" name="delete_id" value="<?php echo esc_attr($r->id); ?>">
                        <button name="ius_delete" class="button button-danger"
                                onclick="return confirm('Delete this URL?');">Delete</button>
                    </form>
                </td>
            </tr>

<?php endforeach; ?>

        </tbody>
    </table>

    </div> <!-- wrap end -->
<?php
}

/* ============================================================================
   REWRITE RULES
============================================================================ */
function ius_add_rewrite_rule() {
    $bases = ius_get_all_base_paths();
    foreach ($bases as $base) {
        $pattern = '^' . preg_quote($base, '/') . '/([^/]+)/?$';
        add_rewrite_rule($pattern, 'index.php?ius_slug=$matches[1]', 'top');
    }
}
add_action('init', 'ius_add_rewrite_rule');

add_filter('query_vars', function ($vars) {
    $vars[] = 'ius_slug';
    return $vars;
});

/* ============================================================================
   REDIRECT HANDLER
============================================================================ */
add_action('template_redirect', function () {
    global $wpdb, $ius_table;

    $slug = get_query_var('ius_slug');
    if (!$slug) return;

    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $ius_table WHERE slug = %s", $slug)
    );

    if ($row) {
        $expired = ius_row_is_expired($row);

        if (!$row->is_active || $expired) {
            global $wp_query;
            $wp_query->set_404();
            return;
        }

        $wpdb->query(
            $wpdb->prepare("UPDATE $ius_table SET hits = hits + 1 WHERE id = %d", $row->id)
        );

        wp_redirect(esc_url_raw($row->original_url), 302);
        exit;
    }

    global $wp_query;
    $wp_query->set_404();
});
