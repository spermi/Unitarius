<?php
declare(strict_types=1);
// ---------------------------------------------------------
// GLOBAL NAMESPACE HELPERS
// ---------------------------------------------------------
namespace {
    //---------------------------------------------------------
    // Request scheme helper (http or https)
    //---------------------------------------------------------
    if (!function_exists('request_scheme')) {
        function request_scheme(): string {
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return 'https';
            if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $p = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0] ?? '';
                return strtolower(trim($p)) === 'https' ? 'https' : 'http';
            }
            if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') return 'https';
            return 'http';
        }
    }

    //---------------------------------------------------------
    // Base URL helper (for linking to site root and routes)
    //---------------------------------------------------------
    if (!function_exists('base_url')) {
        function base_url(string $path = ''): string {
            $base = rtrim($_ENV['APP_URL'] ?? '', '/');
            if ($base === '') {
                $scheme = request_scheme();
                $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $script = $_SERVER['SCRIPT_NAME'] ?? '/public/index.php';
                $root   = rtrim(str_replace('/public/index.php', '', $script), '/');
                if ($root === '/index.php' || $root === '/') $root = '';
                $base = $scheme . '://' . $host . $root;
            }
            return rtrim($base, '/') . '/' . ltrim($path, '/');
        }
    }

    //---------------------------------------------------------
    // Asset URL helper (for linking to /public assets)
    //---------------------------------------------------------
    if (!function_exists('asset')) {
        function asset(string $path): string {
            // Ha VHost a /public-ra mutat, akkor NEM kell "public/" az URL-be
            return base_url(ltrim($path, '/'));
            // Ha almappából szerválsz és kell a /public:  return base_url('public/' . ltrim($path, '/'));
        }
    }

    //---------------------------------------------------------
    // Current user from session (or null if not logged in)
    //---------------------------------------------------------
    if (!function_exists('current_user')) {
        function current_user(): ?array { return $_SESSION['user'] ?? null; }
    }

    //---------------------------------------------------------
    // Is user logged in (session check)
    //---------------------------------------------------------
    if (!function_exists('is_logged_in')) {
        function is_logged_in(): bool { return isset($_SESSION['user']) && is_array($_SESSION['user']); }
    }

    //---------------------------------------------------------
    // Log in the given user (array from DB row)
    //---------------------------------------------------------
    if (!function_exists('login_user')) {
        function login_user(array $user): void {
            if (session_status() === \PHP_SESSION_ACTIVE) { @session_regenerate_id(true); }
            $_SESSION['user'] = [
                'id'     => (int)($user['id'] ?? 0),
                'email'  => (string)($user['email'] ?? ''),
                'name'   => (string)($user['name'] ?? ''),
                'status' => (int)($user['status'] ?? 1),
            ];
            try {
                if (class_exists(\Core\DB::class) && !empty($user['id'])) {
                    $pdo  = \Core\DB::pdo();
                    $stmt = $pdo->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
                    $stmt->execute([':id' => (int)$user['id']]);
                }
            } catch (\Throwable $e) { /* swallow */ }
        }
    }

    //---------------------------------------------------------
    // Log out the current user and destroy the session
    //---------------------------------------------------------
    if (!function_exists('logout_user')) {
        function logout_user(): void {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time()-42000, $p['path'] ?? '/', $p['domain'] ?? '', (bool)($p['secure'] ?? false), (bool)($p['httponly'] ?? true));
            }
            if (session_status() === \PHP_SESSION_ACTIVE) { @session_destroy(); }
        }
    }

    //---------------------------------------------------------
    // * RBAC: check if current user has the given permission.
    // * Uses session cache to avoid DB hits on each check.
    //---------------------------------------------------------
    if (!function_exists('can')) {
        function can(string $perm): bool {
            $user = current_user();
            if (!$user) return false;

            // Warm cache if missing
            if (!isset($_SESSION['perm_cache']) || !is_array($_SESSION['perm_cache'])) {
                $_SESSION['perm_cache'] = load_permissions_for_user((int)$user['id']);
            }

            return in_array($perm, $_SESSION['perm_cache'], true);
        }
    }

    //---------------------------------------------------------
    // Load permissions for a user from DB via role mappings.
    // Schema expected:
    //   users(id) -> user_roles(user_id, role_id)
    //                -> role_permissions(role_id, permission_id)
    //                -> permissions(id, name)
    //
    // @return string[] permission names
    //---------------------------------------------------------
    if (!function_exists('load_permissions_for_user')) {
        function load_permissions_for_user(int $userId): array {
            try {
                if (!class_exists(\Core\DB::class)) {
                    return [];
                }
                $pdo = \Core\DB::pdo();
                $sql = <<<SQL
                    SELECT p.name
                    FROM user_roles ur
                    JOIN role_permissions rp ON rp.role_id = ur.role_id
                    JOIN permissions p ON p.id = rp.permission_id
                    WHERE ur.user_id = :uid
                    GROUP BY p.name
                    ORDER BY p.name
                SQL;
                $st  = $pdo->prepare($sql);
                $st->execute([':uid' => $userId]);
                $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

                // Normalize + unique
                $perms = [];
                foreach ($rows as $r) {
                    $name = isset($r['name']) ? (string)$r['name'] : '';
                    if ($name !== '' && !in_array($name, $perms, true)) {
                        $perms[] = $name;
                    }
                }
                return $perms;
            } catch (\Throwable $e) {
                // Fail closed: no permissions if DB fails
                return [];
            }
        }
    }

}

// ---------------------------------------------------------
// CORE NAMESPACE 
// ---------------------------------------------------------
namespace Core {
    // Back-compat shim: allow \Core\Helpers::* calls to work by delegating to global functions.
    if (!class_exists(Helpers::class)) {
        final class Helpers
        {
            public static function request_scheme(): string { return \request_scheme(); }
            public static function base_url(string $path = ''): string { return \base_url($path); }
            public static function asset(string $path): string { return \asset($path); }

            public static function current_user(): ?array { return \current_user(); }
            public static function is_logged_in(): bool { return \is_logged_in(); }
            public static function login_user(array $user): void { \login_user($user); }
            public static function logout_user(): void { \logout_user(); }

            // Convenience proxies (optional)
            public static function can(string $perm): bool { return \can($perm); }
            /** @return string[] */
            public static function load_permissions_for_user(int $userId): array { return \load_permissions_for_user($userId); }
        }
    }
}
