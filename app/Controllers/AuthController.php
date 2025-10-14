<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\DB;
use Core\View;
use Core\Messenger;

final class AuthController
{

// ---------------------------------------------------------
// GET /login
// ---------------------------------------------------------
public function showLogin(): string
{
    if (is_logged_in()) {
        header('Location: ' . base_url('/'));
        exit;
    }

    $error = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_error']);

    if (isset($_GET['error']) && !$error) {
        switch ($_GET['error']) {
            case 'session_expired':
                $error = 'A munkamenet lejárt. Kérlek, jelentkezz be újra.';
                break;
            case 'invalid_token':
                $error = 'Érvénytelen vagy lejárt token.';
                break;
            case 'unauthorized':
                $error = 'A kért oldalhoz nincs jogosultságod.';
                break;
            default:
                $error = 'Ismeretlen hiba történt. Kérlek, próbáld újra.';
                break;
        }
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    ob_start();
    require __DIR__ . '/../Views/login.php';
    return (string)ob_get_clean();
}


// ---------------------------------------------------------
// POST /login
// ---------------------------------------------------------
public function doLogin(): void
{
    $email = trim((string)($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');

    unset($_SESSION['flash_error']);

    if ($email === '' || $pass === '') {
        $_SESSION['flash_error'] = 'Invalid credentials.';
        header('Location: ' . base_url('/login'));
        exit;
    }

    $__debug = (($_ENV['APP_ENV'] ?? 'production') === 'local') || isset($_GET['__debug']);
    if ($__debug) {
        header('X-Debug-Login-Email: ' . $email);
    }

    try {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('SELECT id,email,password_hash,name,status,avatar FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        if ($user && (int)$user['status'] === 0) {
            $_SESSION['flash_error'] = 'Your account is inactive. Please contact the administrator.';
            header('Location: ' . base_url('/login'));
            exit;
        }

    } catch (\Throwable $e) {
        error_log('[Password Login] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        if ($__debug) {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(500);
            echo "Password login exception:\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString();
            exit;
        }
        $_SESSION['flash_error'] = 'Login temporarily unavailable.';
        header('Location: ' . base_url('/login'));
        exit;
    }

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        $_SESSION['flash_error'] = 'Invalid credentials.';
        header('Location: ' . base_url('/login'));
        exit;
    }

    if ((int)$user['status'] !== 1) {
        $_SESSION['flash_error'] = 'Your account is inactive. Please contact the administrator.';
        header('Location: ' . base_url('/login'));
        exit;
    }

    try {
        $upd = $pdo->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
        $upd->execute([':id' => $user['id']]);
    } catch (\Throwable $e) {
        error_log('[Password Login] failed to update last_login_at: ' . $e->getMessage());
    }

    unset($_SESSION['auth_provider'], $_SESSION['oauth_name'], $_SESSION['oauth_avatar']);
    login_user($user);

    $intended = $_SESSION['intended'] ?? null;
    unset($_SESSION['intended']);
    header('Location: ' . ($intended ?: base_url('/')));
    exit;
}


// ---------------------------------------------------------
// POST /logout
// ---------------------------------------------------------
public function logout(): void
{
    unset($_SESSION['auth_provider'], $_SESSION['oauth_name'], $_SESSION['oauth_avatar']);
    unset($_SESSION['flash_error'], $_SESSION['intended']);

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    logout_user();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    header('Location: ' . base_url('/login'));
    exit;
}


// ---------------------------------------------------------
// GET /auth/google
// ---------------------------------------------------------
public function googleRedirect(): void
{
    unset($_SESSION['flash_error']);

    $clientId     = $_ENV['GOOGLE_CLIENT_ID']     ?? null;
    $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? null;
    $redirectUri  = $_ENV['GOOGLE_REDIRECT_URI']  ?? base_url('/auth/google/callback');

    if (!$clientId || !$clientSecret) {
        $_SESSION['flash_error'] = 'Google login nincs beállítva (hiányzó kliens adatok).';
        header('Location: ' . base_url('/login'));
        exit;
    }

    $client = new \Google_Client();
    $client->setClientId($clientId);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirectUri);
    $client->addScope('email');
    $client->addScope('profile');

    $url = $client->createAuthUrl();
    header('Location: ' . $url);
    exit;
}


// ---------------------------------------------------------
// GET /auth/google/callback
// ---------------------------------------------------------
public function googleCallback(): void
{
    unset($_SESSION['flash_error']);

    $__debug = (($_ENV['APP_ENV'] ?? 'production') === 'local') || isset($_GET['__debug']);
    if ($__debug) {
        header('X-Debug-AppEnv: ' . ($_ENV['APP_ENV'] ?? 'unknown'));
        header('X-Debug-RedirectUri: ' . ($_ENV['GOOGLE_REDIRECT_URI'] ?? 'N/A'));
    }

    $clientId     = $_ENV['GOOGLE_CLIENT_ID']     ?? null;
    $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? null;
    $redirectUri  = $_ENV['GOOGLE_REDIRECT_URI']  ?? base_url('/auth/google/callback');

    if (!$clientId || !$clientSecret) {
        $_SESSION['flash_error'] = 'Google login nincs beállítva (hiányzó kliens adatok).';
        header('Location: ' . base_url('/login'));
        exit;
    }

    if (!isset($_GET['code'])) {
        $_SESSION['flash_error'] = 'Google login sikertelen (nincs code).';
        header('Location: ' . base_url('/login'));
        exit;
    }

    $logFile = dirname(__DIR__, 2) . '/storage/logs/app.log';
    $logDir  = dirname($logFile);
    if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }

    try {
        $ca = $_ENV['CA_BUNDLE'] ?? null;
        $httpOptions = ($ca && is_file($ca)) ? ['verify' => $ca] : [];
        $httpClient = new \GuzzleHttp\Client($httpOptions);

        $client = new \Google_Client();
        $client->setHttpClient($httpClient);
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if ($__debug) { header('X-Debug-Token-HasError: ' . (isset($token['error']) ? '1' : '0')); }
        if (isset($token['error']) || empty($token['access_token'])) {
            @file_put_contents($logFile, "[Google OAuth] token error: " . json_encode($token) . PHP_EOL, FILE_APPEND);
            $_SESSION['flash_error'] = 'Google login hiba (token).';
            header('Location: ' . base_url('/login'));
            exit;
        }

        $client->setAccessToken($token['access_token']);
        $oauth = new \Google_Service_Oauth2($client);
        $info  = $oauth->userinfo->get();

        $email   = trim((string)$info->email);
        $nameG   = trim((string)($info->name ?? (($info->given_name ?? '') . ' ' . ($info->family_name ?? ''))));
        $avatarG = isset($info->picture) ? (string)$info->picture : '';

        if ($email === '') {
            $_SESSION['flash_error'] = 'Google login hiba (nincs e-mail).';
            header('Location: ' . base_url('/login'));
            exit;
        }

        $pdo = \Core\DB::pdo();

        $stmt = $pdo->prepare('SELECT id,email,password_hash,name,status,avatar FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        if ($user && (int)$user['status'] === 0) {
            $_SESSION['flash_error'] = 'Your account is inactive. Please contact the administrator.';
            header('Location: ' . base_url('/login'));
            exit;
        }

        if ($user && (int)$user['status'] === 1) {
            try {
                $check = $pdo->prepare('SELECT created_at, updated_at FROM users WHERE id = :id');
                $check->execute([':id' => $user['id']]);
                $meta = $check->fetch(\PDO::FETCH_ASSOC);
                $isNeverEdited = $meta && $meta['created_at'] === $meta['updated_at'];

                $sql = 'UPDATE users SET last_login_at = NOW(), updated_at = NOW()';
                $params = [':id' => $user['id']];

                if ($isNeverEdited) {
                    if ($nameG !== '' && $nameG !== (string)$user['name']) {
                        $sql .= ', name = :name';
                        $params[':name'] = $nameG;
                    }
                    if ($avatarG !== '' && $avatarG !== (string)($user['avatar'] ?? '')) {
                        $sql .= ', avatar = :avatar';
                        $params[':avatar'] = $avatarG;
                    }
                }

                $sql .= ' WHERE id = :id';
                $upd = $pdo->prepare($sql);
                $upd->execute($params);
            } catch (\Throwable $e) {
                @file_put_contents(
                    $logFile,
                    '[Google Login] conditional update failed: ' . $e->getMessage() . PHP_EOL,
                    FILE_APPEND
                );
            }

        } else {
            try {
                $randomPlain = bin2hex(random_bytes(32));
                $hash = password_hash($randomPlain, PASSWORD_BCRYPT);

                $ins = $pdo->prepare(
                    'INSERT INTO users (email, password_hash, name, status, avatar, last_login_at, created_at, updated_at)
                     VALUES (:email, :ph, :name, 1, :avatar, NOW(), NOW(), NOW())
                     RETURNING id, email, password_hash, name, status, avatar'
                );
                $ins->execute([
                    ':email'  => $email,
                    ':ph'     => $hash,
                    ':name'   => ($nameG !== '' ? $nameG : $email),
                    ':avatar' => ($avatarG !== '' ? $avatarG : null),
                ]);
                $user = $ins->fetch(\PDO::FETCH_ASSOC) ?: null;

                if (!$user) {
                    throw new \RuntimeException('Insert RETURNING returned no row');
                }

                // 🔔 Notify managers about the new user
                Messenger::broadcastPermission(
                    'users.manage',
                    'Új felhasználó!',
                    //'Egy új felhasználó csatlakozott a rendszerhez: ' . ($user['name'] ?? $user['email']),
                    ($user['name'] ?? $user['email']),
                    base_url('/users/' . $user['id']),
                    'new_user'
                );

            } catch (\Throwable $e) {
                @file_put_contents($logFile, '[Google Login] insert new user failed: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
                $_SESSION['flash_error'] = 'Nem sikerült létrehozni a felhasználót.';
                header('Location: ' . base_url('/login'));
                exit;
            }
        }

        $_SESSION['auth_provider'] = 'google';
        if ($nameG   !== '') { $_SESSION['oauth_name']   = $nameG; }
        if ($avatarG !== '') { $_SESSION['oauth_avatar'] = $avatarG; }

        login_user($user);
        $intended = $_SESSION['intended'] ?? null;
        unset($_SESSION['intended']);
        header('Location: ' . ($intended ?: base_url('/')));
        exit;

    } catch (\Throwable $e) {
        @file_put_contents(
            $logFile,
            '[Google OAuth] ' . $e->getMessage() . "\n" . $e->getTraceAsString() . PHP_EOL,
            FILE_APPEND
        );

        if ($__debug) {
            header('Content-Type: text/plain; charset=utf-8');
            http_response_code(500);
            echo "Google OAuth exception:\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString();
            exit;
        }

        $_SESSION['flash_error'] = 'Google login kivétel.';
        header('Location: ' . base_url('/login'));
        exit;
    }
}

}
