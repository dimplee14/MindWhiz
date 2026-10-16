<?php
/**
 * Session Configuration
 */

return [
    'driver' => 'file',
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => __DIR__ . '/../storage/sessions',
    'connection' => null,
    'table' => 'sessions',
    'store' => null,
    'lottery' => [2, 100],
    'cookie' => 'memory_test_session',
    'path' => '/',
    // Use empty string to satisfy session_start type expectations on cookie_domain
    'domain' => env('SESSION_DOMAIN', ''),
    'secure' => env('SESSION_SECURE', false),
    'http_only' => env('SESSION_HTTP_ONLY', true),
    'same_site' => env('SESSION_SAME_SITE', 'Lax'),
];
