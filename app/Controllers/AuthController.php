<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\DB;
use Core\View;

final class AuthController
{
    // GET /login
    public function showLogin(): string
{
    if (is_logged_in()) { header('Location: ' . base_url('/')); exit; }

    // Render standalone view with output buffering (no global layout)
    $error = $_SESSION['flash_error'] ?? null; // pass vars explicitly
    ob_start();
    require __DIR__ . '/../Views/login.php';
    return (string)ob_get_clean();
}

    // POST /login
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

    // Env-based debug: show details only in local (or with ?__debug=1)
    $__debug = (($_ENV['APP_ENV'] ?? 'production') === 'local') || isset($_GET['__debug']);
    if ($__debug) {
        header('X-Debug-Login-Email: ' . $email);
    }

    try {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare('SELECT id,email,password_hash,name,status FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    } catch (\Throwable $e) {
        // Always log; only expose details in local
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

    if (!$user || (int)$user['status'] !== 1 || !password_verify($pass, $user['password_hash'])) {
        $_SESSION['flash_error'] = 'Invalid credentials.';
        header('Location: ' . base_url('/login'));
        exit;
    }

    // success
    login_user($user);

    $intended = $_SESSION['intended'] ?? null;
    unset($_SESSION['intended']);
    header('Location: ' . ($intended ?: base_url('/')));
    exit;
}


    // POST /logout
    public function logout(): void
    {
        logout_user();
        header('Location: ' . base_url('/login'));
        exit;
    }

// GET /auth/google
public function googleRedirect(): void
{
    unset($_SESSION['flash_error']);

    // Read from .env; fallback a callback URL-re
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


// GET /auth/google/callback
public function googleCallback(): void
{
    unset($_SESSION['flash_error']);

    // Debug only in local (or when ?__debug=1)
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

    // --- File log path without base_path() helper
    $logFile = dirname(__DIR__, 2) . '/storage/logs/app.log';
    $logDir  = dirname($logFile);
    if (!is_dir($logDir)) { @mkdir($logDir, 0777, true); }

    try {
        // Create client
        $client = new \Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        // Exchange code for token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        if ($__debug) {
            header('X-Debug-Token-HasError: ' . (isset($token['error']) ? '1' : '0'));
        }

        if (isset($token['error'])) {
            @file_put_contents($logFile, "[Google OAuth] token error: " . json_encode($token) . PHP_EOL, FILE_APPEND);
            $_SESSION['flash_error'] = 'Google login hiba (token).';
            header('Location: ' . base_url('/login'));
            exit;
        }

        if (empty($token['access_token'])) {
            throw new \RuntimeException('Empty access_token in token response');
        }

        $client->setAccessToken($token['access_token']);

        // Fetch user info
        $oauth = new \Google_Service_Oauth2($client);
        $info  = $oauth->userinfo->get();

        $email = trim((string)$info->email);
        $name  = trim((string)($info->name ?? (($info->given_name ?? '') . ' ' . ($info->family_name ?? ''))));

        if ($email === '') {
            $_SESSION['flash_error'] = 'Google login hiba (nincs e-mail).';
            header('Location: ' . base_url('/login'));
            exit;
        }

        // Only allow existing local users (no schema change)
        $pdo  = \Core\DB::pdo();
        $stmt = $pdo->prepare('SELECT id,email,password_hash,name,status FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        if (!$user || (int)$user['status'] !== 1) {
            $_SESSION['flash_error'] = 'Nincs helyi fiók ehhez az e-mailhez. Vedd fel a kapcsolatot az adminnal.';
            header('Location: ' . base_url('/login'));
            exit;
        }
        
        // Succes: Extra session context for UI
        $_SESSION['auth_provider'] = 'google';
        $_SESSION['oauth_name']    = $name;
        $_SESSION['oauth_avatar']  = isset($info->picture) ? (string)$info->picture : null;
        // external logger? do we need to log this separately ?
        // check if we need to update name in local db?
        

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