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

// --- Auth helpers ------------------------------------------------------------

/**
 * Get the currently authenticated user from session or null.
 */
if (!function_exists('current_user')) {
  
    function current_user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}


/**
 * Check if a user is authenticated.
 */
if (!function_exists('is_logged_in')) {
   
    function is_logged_in(): bool
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']);
    }
}


/**
 * Log a user in: rotate session id, put minimal user payload into session,
 * and (best-effort) update last_login_at in DB.
 *
 * Expected $user contains at least: id, email, name, status.
 */
if (!function_exists('login_user')) {
    
    function login_user(array $user): void
    {
        // Rotate session id to prevent fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
        }

        // Store minimal, non-sensitive payload in session
        $_SESSION['user'] = [
            'id'     => (int)($user['id'] ?? 0),
            'email'  => (string)($user['email'] ?? ''),
            'name'   => (string)($user['name'] ?? ''),
            'status' => (int)($user['status'] ?? 1),
        ];

        // Best-effort DB update (optional)
        try {
            if (class_exists(\Core\DB::class) && !empty($user['id'])) {
                $pdo  = \Core\DB::pdo();
                $stmt = $pdo->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
                $stmt->execute([':id' => (int)$user['id']]);
            }
        } catch (\Throwable $e) {
            // swallow – ErrorHandler/logs will capture if configured
        }
    }
}


/**
 * Log out current user and destroy the session cookie.
 */
if (!function_exists('logout_user')) {
    
    function logout_user(): void
    {
        // Clear session array
        $_SESSION = [];

        // Kill the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', (bool)($params['secure'] ?? false), (bool)($params['httponly'] ?? true));
        }

        // Destroy session
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }
    }
}
