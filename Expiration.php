<?php
/** Expiration helpers for URL Shortener. Stored dates are UTC. */

if (!defined('ABSPATH')) {
    exit;
}

function ius_row_is_expired($row) {
    if (empty($row->expires_at)) {
        return false;
    }

    $timestamp = strtotime($row->expires_at . ' UTC');
    if ($timestamp === false) {
        // A damaged expiration must not make a restricted link active again.
        return true;
    }

    return time() >= $timestamp;
}

function ius_format_datetime_local($datetime) {
    if (!$datetime) {
        return '';
    }

    return get_date_from_gmt($datetime, 'Y-m-d\TH:i');
}

/** Parse a browser's local date in the WordPress site timezone. */
function ius_parse_expiration_input($raw) {
    if (!is_string($raw)) {
        return false;
    }

    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $raw)) {
        return false;
    }

    $datetime = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $raw, wp_timezone());
    if (!$datetime || $datetime->format('Y-m-d\TH:i') !== $raw) {
        return false;
    }

    return $datetime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}
