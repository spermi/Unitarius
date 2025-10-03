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

        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare('SELECT id,email,password_hash,name,status FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
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

    try {
        $client = new \Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            $_SESSION['flash_error'] = 'Google login hiba (token).';
            header('Location: ' . base_url('/login'));
            exit;
        }

        $client->setAccessToken($token['access_token']);

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

        // Success: same helper as password login
        login_user($user);

        $intended = $_SESSION['intended'] ?? null;
        unset($_SESSION['intended']);
        header('Location: ' . ($intended ?: base_url('/')));
        exit;

    } catch (\Throwable $e) {
        $_SESSION['flash_error'] = 'Google login kivétel.';
        header('Location: ' . base_url('/login'));
        exit;
    }
}

}