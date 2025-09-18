<?php
function base_url(string $path = ''): string {
    $base = rtrim($_ENV['APP_URL'] ?? '/', '/');
    return $base . '/' . ltrim($path, '/');
}
