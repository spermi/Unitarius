<?php
namespace Core;

final class Request
{
    public function method(): string { return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'); }
    public function uri(): string { return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; }
    public function query(?string $key=null, $default=null) { return $key ? ($_GET[$key] ?? $default) : $_GET; }
    public function input(?string $key=null, $default=null) { return $key ? ($_POST[$key] ?? $default) : $_POST; }
    public function header(string $key, $default=null) {
        $k = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$k] ?? $default;
    }
    public function isAjax(): bool { return strtolower($this->header('X-Requested-With','')) === 'xmlhttprequest'; }
}
