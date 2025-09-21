<?php
declare(strict_types=1);

/**
 * Detect current request scheme (http/https),
 * taking into account proxy headers.
 */
function request_scheme(): string {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return 'https';
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $p = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0];
        return strtolower(trim($p)) === 'https' ? 'https' : 'http';
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return 'https';
    }
    return 'http';
}

/**
 * Base URL helper
 * Uses APP_URL from .env if present, otherwise builds from current request.
 */
function base_url(string $path = ''): string {
    $base = rtrim($_ENV['APP_URL'] ?? '', '/');
    if (!$base) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = request_scheme() . '://' . $host;
    }
    return $base . '/' . ltrim($path, '/');
}

/**
 * Asset URL helper – for files under /public.
 */
function asset(string $path): string {
    return base_url('public/' . ltrim($path, '/'));
}
