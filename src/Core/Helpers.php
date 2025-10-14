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

    // ---------------------------------------------------------
    // Log in the given user (array from DB row)
    // ---------------------------------------------------------
    if (!function_exists('login_user')) {
        function login_user(array $user): void {
            if (session_status() === \PHP_SESSION_ACTIVE) { @session_regenerate_id(true); }

            // Invalidate RBAC permission cache on login
            unset($_SESSION['perm_cache']);

            $_SESSION['user'] = [
                'id'     => (int)($user['id'] ?? 0),
                'email'  => (string)($user['email'] ?? ''),
                'name'   => (string)($user['name'] ?? ''),
                'status' => (int)($user['status'] ?? 1),
                'avatar' => (string)($user['avatar'] ?? ''),
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

    // ---------------------------------------------------------
    // Log out the current user and destroy the session
    // ---------------------------------------------------------
    if (!function_exists('logout_user')) {
        function logout_user(): void {
            // Invalidate RBAC permission cache on logout
            unset($_SESSION['perm_cache']);

            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(
                    session_name(), '', time()-42000,
                    $p['path'] ?? '/', $p['domain'] ?? '',
                    (bool)($p['secure'] ?? false), (bool)($p['httponly'] ?? true)
                );
            }
            if (session_status() === \PHP_SESSION_ACTIVE) { @session_destroy(); }
        }
    }

    //---------------------------------------------------------
    // Permission implication helper (supports wildcards)
    // Examples:
    //  - owned: "users.*"            implies required: "users.view", "users.user.write", ...
    //  - owned: "accounting.invoice.*" implies "accounting.invoice.view"
    //  - required: "users.*"         true if any owned perm starts with "users." or equals "users.*"
    //---------------------------------------------------------
    if (!function_exists('permission_implies')) {
        function permission_implies(string $owned, string $required): bool {
            if ($owned === $required) return true;

            $ownedIsWildcard = str_ends_with($owned, '.*');
            $reqIsWildcard   = str_ends_with($required, '.*');

            if ($ownedIsWildcard) {
                $prefix = substr($owned, 0, -2); // drop .*
                if ($required === $prefix) return true;
                return str_starts_with($required, $prefix . '.');
            }

            if ($reqIsWildcard) {
                $reqPrefix = substr($required, 0, -2);
                if ($owned === $reqPrefix) return true;
                return str_starts_with($owned, $reqPrefix . '.');
            }

            return false;
        }
    }

    //---------------------------------------------------------
    // * RBAC: check if current user has the given permission.
    // * Uses session cache to avoid DB hits on each check.
    // * Supports wildcard hierarchy via permission_implies().
    //---------------------------------------------------------
    if (!function_exists('can')) {
        function can(string $perm): bool {
            $user = current_user();
            if (!$user) return false;

            // Warm cache if missing
            if (!isset($_SESSION['perm_cache']) || !is_array($_SESSION['perm_cache'])) {
                $_SESSION['perm_cache'] = load_permissions_for_user((int)$user['id']);
            }

            $owned = $_SESSION['perm_cache'];

            // Exact match fast path
            if (in_array($perm, $owned, true)) return true;

            // Wildcard-aware implication checks (both directions)
            foreach ($owned as $have) {
                if (permission_implies((string)$have, $perm)) {
                    return true;
                }
            }

            return false;
        }
    }

    //---------------------------------------------------------
    // Helper: check if user has ANY of the given permissions
    //---------------------------------------------------------
    if (!function_exists('can_any')) {
        /** @param string[] $perms */
        function can_any(array $perms): bool {
            foreach ($perms as $p) {
                if (can((string)$p)) return true;
            }
            return false;
        }
    }

    //---------------------------------------------------------
    // Enforce permission (throws 403 if not granted)
    //---------------------------------------------------------
    if (!function_exists('require_can')) {
        function require_can(string $perm, ?string $message = null): void {
            if (!can($perm)) {
                http_response_code(403);
                $html = \Core\View::render('errors/403', [
                    'title'   => 'Access Forbidden',
                    'message' => $message ?: 'Access denied (required permission: ' . $perm . ').',
                ], null);
                echo $html;
                exit;
            }
        }
    }

    //---------------------------------------------------------
    // Enforce record ownership (403 if not the owner)
    //---------------------------------------------------------
    if (!function_exists('require_owner')) {
        function require_owner(int|string|null $recordUserId, ?string $message = null): void {
            $current = current_user();
            if (!$current) {
                http_response_code(401);
                $html = \Core\View::render('errors/401', [
                    'title'   => 'Unauthorized',
                    'message' => $message ?: 'You need to sign in to continue.',
                ], null);
                echo $html;
                exit;
            }
            $uid = (int)($current['id'] ?? 0);
            if ($uid <= 0 || (int)$recordUserId !== $uid) {
                http_response_code(403);
                $html = \Core\View::render('errors/403', [
                    'title'   => 'Access Forbidden',
                    'message' => $message ?: 'Access denied - you are not the owner of this record.',
                ], null);
                echo $html;
                exit;
            }
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

    //---------------------------------------------------------
    // -------- Flash messages --------
    //---------------------------------------------------------
    if (!function_exists('flash_set')) {
        function flash_set(string $type, string $msg): void {
            $_SESSION['_flash'][$type] = $msg;
        }
    }
    if (!function_exists('flash_get')) {
        function flash_get(?string $type = null): array|string|null {
            $all = $_SESSION['_flash'] ?? [];
            if ($type === null) {
                unset($_SESSION['_flash']);
                return $all;
            }
            $val = $all[$type] ?? null;
            if ($val !== null) {
                unset($_SESSION['_flash'][$type]);
            }
            if (empty($_SESSION['_flash'])) {
                unset($_SESSION['_flash']);
            }
            return $val;
        }
    }
    if (!function_exists('flash_has')) {
        function flash_has(string $type): bool {
            return isset($_SESSION['_flash'][$type]);
        }
    }

    //---------------------------------------------------------
    // CSRF token helpers
    //---------------------------------------------------------
    if (!function_exists('csrf_token')) {
        function csrf_token(): string {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['csrf_token'];
        }
    }

    if (!function_exists('csrf_field')) {
        function csrf_field(): string {
            $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
            return '<input type="hidden" name="_token" value="'.$token.'">';
        }
    }

    if (!function_exists('verify_csrf')) {
        function verify_csrf(): bool {
            $token = $_POST['_token'] ?? '';
            return is_string($token)
                && isset($_SESSION['csrf_token'])
                && hash_equals($_SESSION['csrf_token'], $token);
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
            public static function require_can(string $perm, ?string $message = null): void { \require_can($perm, $message); }
            public static function require_owner(int|string|null $recordUserId, ?string $message = null): void { \require_owner($recordUserId, $message); }

            /** @return string[] */
            public static function load_permissions_for_user(int $userId): array { return \load_permissions_for_user($userId); }
        }
    }
}
