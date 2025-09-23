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
        return View::render('auth/login', [
            'title' => 'Login',
            'error' => $_SESSION['flash_error'] ?? null,
        ]);
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
}
