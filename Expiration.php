<?php
/** Expiration helpers for URL Shortener. Stored dates are UTC. */

if (!defined('ABSPATH')) {
    exit;
}

function ius_parse_stored_expiration($raw) {
    if (!is_string($raw) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $raw)) {
        return false;
    }

    $datetime = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $raw, new DateTimeZone('UTC'));
    return $datetime && $datetime->format('Y-m-d H:i:s') === $raw ? $datetime : false;
}

function ius_row_is_expired($row) {
    if ($row->expires_at === null || $row->expires_at === '') {
        return false;
    }

    $datetime = ius_parse_stored_expiration($row->expires_at);
    if (!$datetime) {
        // A damaged expiration must not make a restricted link active again.
        return true;
    }

    return time() >= $datetime->getTimestamp();
}

function ius_format_datetime_local($datetime) {
    if ($datetime === null || $datetime === '') {
        return '';
    }

    $stored = ius_parse_stored_expiration($datetime);
    return $stored ? $stored->setTimezone(wp_timezone())->format('Y-m-d\TH:i') : '';
}

function ius_format_expiration_for_display($datetime) {
    if ($datetime === null || $datetime === '') {
        return 'Never';
    }

    $stored = ius_parse_stored_expiration($datetime);
    return $stored ? $stored->setTimezone(wp_timezone())->format('M j, Y H:i') : 'Invalid expiration';
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
