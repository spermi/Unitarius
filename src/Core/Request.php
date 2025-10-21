<?php
namespace Core;

final class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    }

    public function query(?string $key = null, $default = null)
    {
        return $key ? ($_GET[$key] ?? $default) : $_GET;
    }

    public function input(?string $key = null, $default = null)
    {
        return $key ? ($_POST[$key] ?? $default) : $_POST;
    }

    public function header(string $key, $default = null)
    {
        $k = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$k] ?? $default;
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    //---------------------------------------------------------
    // Return only specific POST keys as array
    // Example: $req->only(['name','email'])
    //---------------------------------------------------------
    public function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            if (isset($_POST[$k])) {
                $out[$k] = $_POST[$k];
            }
        }
        return $out;
    }

    //---------------------------------------------------------
    // Shortcut for accessing POST parameters
    //---------------------------------------------------------
    public function post(?string $key = null, $default = null)
    {
        return $key ? ($_POST[$key] ?? $default) : $_POST;
    }

}

