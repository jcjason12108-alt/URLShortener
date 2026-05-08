<?php
/**
 * Static QR Redirect module for the URL Shortener plugin.
 */

if (!defined('ABSPATH')) exit;

if (class_exists('IUS_Static_QR_Redirect', false)) {
    return;
}

class IUS_Static_QR_Redirect {
    const OPTION_KEY = 'static_qr_redirect';
    const NONCE_KEY  = 'static_qr_redirect_nonce';
    const SLUG       = 'static-qr-redirect';
    const BASE_PATH  = 'qr';
    const OPTION_CLICKS = 'static_qr_redirect_clicks';
    const OPTION_SLOTS = 'static_qr_redirect_slots';

    private function slots() {
        return ['qr', 'qr2', 'qr3', 'qr4'];
    }

    public function register_hooks() {
        add_action('admin_init', [$this, 'maybe_handle_post']);
        add_action('init', [$this, 'add_rewrite']);
        add_action('template_redirect', [$this, 'maybe_do_redirect']);
    }

    public function add_rewrite() {
        add_rewrite_rule('^qr/?$', 'index.php?static_qr_slot=qr', 'top');
        add_rewrite_rule('^qr2/?$', 'index.php?static_qr_slot=qr2', 'top');
        add_rewrite_rule('^qr3/?$', 'index.php?static_qr_slot=qr3', 'top');
        add_rewrite_rule('^qr4/?$', 'index.php?static_qr_slot=qr4', 'top');
        add_rewrite_tag('%static_qr_slot%', '([a-z0-9]+)');
        add_rewrite_tag('%static_qr_redirect%', '1');
    }

    public function maybe_do_redirect() {
        $slot = get_query_var('static_qr_slot');
        if (!$slot) {
            $is_static = get_query_var('static_qr_redirect');
            if ($is_static) $slot = 'qr';
        }
        if (!$slot) return;

        $slots = get_option(self::OPTION_SLOTS, []);
        if (empty($slots['qr'])) {
            $legacy = get_option(self::OPTION_KEY, []);
            if (!empty($legacy)) {
                $slots['qr'] = [
                    'url' => isset($legacy['url']) ? $legacy['url'] : '',
                    'active' => !empty($legacy['active']) ? 1 : 0,
                    'clicks' => (int) get_option(self::OPTION_CLICKS, 0),
                ];
                update_option(self::OPTION_SLOTS, $slots, false);
            }
        }

        if (!isset($slots[$slot])) {
            $slots[$slot] = ['url' => '', 'active' => 0, 'clicks' => 0];
        }

        $dest = ius_validate_destination_url(isset($slots[$slot]['url']) ? $slots[$slot]['url'] : '');
        $active = !empty($slots[$slot]['active']);
        if (!$dest) {
            status_header(404);
            nocache_headers();
            wp_die('<h1>QR link not configured</h1>', 'Not Found', ['response' => 404]);
        }
        if (!$active) {
            status_header(410);
            nocache_headers();
            wp_die('<h1>QR link is inactive</h1>', 'Gone', ['response' => 410]);
        }

        $slots[$slot]['clicks'] = (int) (isset($slots[$slot]['clicks']) ? $slots[$slot]['clicks'] : 0) + 1;
        update_option(self::OPTION_SLOTS, $slots, false);

        nocache_headers();
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Robots-Tag: noindex, nofollow', true);

        wp_redirect($dest, 302, 'URL Shortener Static QR Redirect');
        exit;
    }

    public function render_tab() {
        if (!current_user_can('manage_options')) return;

        $pretty = (string) get_option('permalink_structure') !== '';
        $slots = get_option(self::OPTION_SLOTS, []);
        if (empty($slots['qr'])) {
            $legacy = get_option(self::OPTION_KEY, []);
            if (!empty($legacy)) {
                $slots['qr'] = [
                    'url' => isset($legacy['url']) ? $legacy['url'] : '',
                    'active' => !empty($legacy['active']) ? 1 : 0,
                    'clicks' => (int) get_option(self::OPTION_CLICKS, 0),
                ];
                update_option(self::OPTION_SLOTS, $slots, false);
            }
        }

        $base = home_url('/');
        ?>
        <h2>Static QR Redirect</h2>
        <p><a href="https://github.com/jcjason12108-alt/Dynamic-QR-Redirects" target="_blank" rel="noopener noreferrer">GitHub Repository</a></p>
        <p>Manage up to four fixed QR routes. Print each once and change the destination anytime.</p>

        <?php if (!$pretty): ?>
            <div class="notice notice-warning" style="margin:12px 0;">
                <p><strong>Note:</strong> Pretty permalinks are disabled on this site. Your QR routes will use the fallback format with a query string (e.g., <code><?php echo esc_html(add_query_arg('static_qr_slot', 'qr', $base)); ?></code>). To use clean paths like <code><?php echo esc_html(trailingslashit($base . 'qr')); ?></code>, go to <em>Settings → Permalinks</em> and select a non-default structure.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($this->slots() as $slot):
            $data = isset($slots[$slot]) ? $slots[$slot] : ['url' => '', 'active' => 0, 'clicks' => 0];
            $url = esc_attr($data['url']);
            $active = !empty($data['active']);
            $clicks = (int) (isset($data['clicks']) ? $data['clicks'] : 0);
            $path = $slot;
            $qr_pretty = trailingslashit($base . $path);
            $qr_qs = add_query_arg('static_qr_slot', $slot, $base);
            $qr_link = $pretty ? $qr_pretty : $qr_qs;
        ?>
        <hr>
        <h3><?php echo esc_html(strtoupper($slot)); ?> - <code><?php echo esc_html($qr_link); ?></code></h3>
        <form method="post" action="" style="margin-bottom:6px;">
            <?php wp_nonce_field(self::NONCE_KEY, self::NONCE_KEY); ?>
            <input type="hidden" name="static_qr_action" value="save_slot">
            <input type="hidden" name="slot" value="<?php echo esc_attr($slot); ?>">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="url-<?php echo esc_attr($slot); ?>">Destination URL</label></th>
                    <td><input name="url" id="url-<?php echo esc_attr($slot); ?>" type="url" class="regular-text code" placeholder="https://example.com/page" value="<?php echo $url; ?>"></td>
                </tr>
                <tr>
                    <th scope="row">Active?</th>
                    <td><label><input type="checkbox" name="active" value="1" <?php checked($active); ?>> Yes</label></td>
                </tr>
            </table>
            <p class="submit"><button class="button button-primary">Save <?php echo esc_html(strtoupper($slot)); ?></button></p>
        </form>

        <div style="display:flex;gap:18px;align-items:flex-start;margin:0 0 10px;">
            <div style="width:220px;text-align:center">
                <strong>QR Preview</strong>
                <div style="margin-top:6px">
                    <img src="<?php echo esc_url('https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($qr_link)); ?>" alt="QR <?php echo esc_attr($slot); ?>" width="200" height="200" />
                </div>
                <p><a class="button button-small" href="<?php echo esc_url('https://api.qrserver.com/v1/create-qr-code/?size=800x800&data=' . rawurlencode($qr_link)); ?>" download="static-<?php echo esc_attr($slot); ?>.png">Download PNG</a></p>
            </div>
            <div style="flex:1;min-width:220px">
                <strong>Stats</strong>
                <p style="margin-top:6px;"><strong>Total scans:</strong> <?php echo number_format_i18n($clicks); ?></p>
                <form method="post" action="" style="margin-bottom:6px;">
                    <?php wp_nonce_field(self::NONCE_KEY, self::NONCE_KEY); ?>
                    <input type="hidden" name="static_qr_action" value="reset_clicks_slot">
                    <input type="hidden" name="slot" value="<?php echo esc_attr($slot); ?>">
                    <button class="button">Reset Click Count</button>
                    <a class="button" style="margin-left:6px" href="<?php echo esc_url($qr_link); ?>" target="_blank" rel="noopener">Open</a>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <p style="margin-top:16px;color:#666;">After first activation or when adding new routes, visit <em>Settings → Permalinks → Save</em> to refresh rewrite rules.</p>
        <?php
    }

    public function maybe_handle_post() {
        if (!is_admin() || !current_user_can('manage_options')) return;
        if (empty($_POST['static_qr_action'])) return;
        if (!isset($_POST[self::NONCE_KEY]) || !wp_verify_nonce($_POST[self::NONCE_KEY], self::NONCE_KEY)) {
            wp_die('Security check failed.');
        }

        $slots = get_option(self::OPTION_SLOTS, []);
        $action = sanitize_text_field(wp_unslash($_POST['static_qr_action']));
        $slot = sanitize_title(wp_unslash(isset($_POST['slot']) ? $_POST['slot'] : ''));

        if ($slot && !in_array($slot, $this->slots(), true)) {
            add_settings_error(self::SLUG, 'invalid_slot', 'Invalid slot.', 'error');
        } else {
            if ($action === 'save_slot') {
                $raw_url = wp_unslash(isset($_POST['url']) ? $_POST['url'] : '');
                $url = $raw_url === '' ? '' : ius_validate_destination_url($raw_url);
                $active = !empty($_POST['active']) ? 1 : 0;
                if ($raw_url !== '' && !$url) {
                    add_settings_error(self::SLUG, 'invalid_url', 'Destination URL must be a valid http or https URL.', 'error');
                } else {
                    if (!isset($slots[$slot])) $slots[$slot] = ['url' => '', 'active' => 0, 'clicks' => 0];
                    $slots[$slot]['url'] = $url;
                    $slots[$slot]['active'] = $active;
                    update_option(self::OPTION_SLOTS, $slots, false);
                    add_settings_error(self::SLUG, 'saved', strtoupper($slot) . ' saved.', 'updated');
                }
            } elseif ($action === 'reset_clicks_slot') {
                if (!isset($slots[$slot])) $slots[$slot] = ['url' => '', 'active' => 0, 'clicks' => 0];
                $slots[$slot]['clicks'] = 0;
                update_option(self::OPTION_SLOTS, $slots, false);
                add_settings_error(self::SLUG, 'reset', strtoupper($slot) . ' click count reset.', 'updated');
            }
        }

        add_action('admin_notices', function () {
            settings_errors(self::SLUG);
        });
    }

    public function on_activate() {
        $this->add_rewrite();
    }
}
