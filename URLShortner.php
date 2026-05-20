<?php
/**
 * Compatibility loader for installations activated under the previous filename.
 *
 * The plugin's main file is URLShortener.php. This file intentionally has no
 * WordPress plugin header so it does not appear as a duplicate plugin.
 *
 * Version: 1.4.3
 */

if (!defined('ABSPATH')) {
    exit;
}

$ius_old_plugin_basename = plugin_basename(__FILE__);
$ius_new_plugin_basename = plugin_basename(__DIR__ . '/URLShortener.php');

if ($ius_old_plugin_basename !== $ius_new_plugin_basename) {
    $ius_active_plugins = (array) get_option('active_plugins', []);
    $ius_old_index = array_search($ius_old_plugin_basename, $ius_active_plugins, true);

    if ($ius_old_index !== false) {
        unset($ius_active_plugins[$ius_old_index]);
        $ius_active_plugins[] = $ius_new_plugin_basename;
        $ius_active_plugins = array_values(array_unique($ius_active_plugins));
        update_option('active_plugins', array_values($ius_active_plugins));
    }

    if (is_multisite()) {
        $ius_sitewide_plugins = (array) get_site_option('active_sitewide_plugins', []);

        if (isset($ius_sitewide_plugins[$ius_old_plugin_basename])) {
            $ius_activation_time = $ius_sitewide_plugins[$ius_old_plugin_basename];
            unset($ius_sitewide_plugins[$ius_old_plugin_basename]);
            $ius_sitewide_plugins[$ius_new_plugin_basename] = $ius_activation_time;
            update_site_option('active_sitewide_plugins', $ius_sitewide_plugins);
        }
    }
}

require_once __DIR__ . '/URLShortener.php';
