<?php
/** Smoke tests for expiration boundaries without a WordPress installation. */

define('ABSPATH', __DIR__ . '/');

function wp_timezone() {
    return new DateTimeZone('America/Los_Angeles');
}

require dirname(__DIR__) . '/Expiration.php';

function expect_same($expected, $actual, $label) {
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

expect_same('2026-11-20 20:30:00', ius_parse_expiration_input('2026-11-20T12:30'), 'site time to UTC');
expect_same('2026-11-20T12:30', ius_format_datetime_local('2026-11-20 20:30:00'), 'UTC to edit field');
expect_same('Nov 20, 2026 12:30', ius_format_expiration_for_display('2026-11-20 20:30:00'), 'admin display');
expect_same(null, ius_parse_expiration_input(''), 'blank means never');
expect_same(false, ius_parse_expiration_input('not a date'), 'malformed date');
expect_same(false, ius_parse_expiration_input(['2026-11-20T12:30']), 'array input');
expect_same(false, ius_parse_expiration_input('2026-03-08T02:30'), 'nonexistent daylight saving time');
expect_same(true, ius_row_is_expired((object) ['expires_at' => 'broken']), 'damaged stored expiration');
expect_same(true, ius_row_is_expired((object) ['expires_at' => '2026-02-31 12:00:00']), 'invalid stored date');
expect_same(true, ius_row_is_expired((object) ['expires_at' => '2000-01-01 00:00:00']), 'past expiration');
expect_same(false, ius_row_is_expired((object) ['expires_at' => '2099-01-01 00:00:00']), 'future expiration');
expect_same('', ius_format_datetime_local('broken'), 'damaged edit field');
expect_same('Invalid expiration', ius_format_expiration_for_display('broken'), 'damaged admin display');
expect_same(false, ius_row_is_expired((object) ['expires_at' => null]), 'no expiration');

echo "Expiration smoke tests passed.\n";
