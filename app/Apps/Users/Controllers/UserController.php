<?php
declare(strict_types=1);

namespace App\Apps\Users\Controllers;

use Core\DB;
use Core\View;

// ---------------------------------------------------------
// UserController
//
// Handles user listing and management.
// Accessible only to users with the "users.view" and "users.manage" permissions.
// ---------------------------------------------------------
final class UserController
{
    // ---------------------------------------------------------
    // GET /users
    //
    // Displays all users in a simple table (ID, name, email, status, last login, avatar).
    // Uses AdminLTE styling and referrerpolicy="no-referrer" for Google avatars.
    // ---------------------------------------------------------
    public function index(): string
    {
        $pdo = DB::pdo();

        // Fetch all users (limit for safety)
        $stmt = $pdo->query(
            'SELECT id, name, email, status, avatar, last_login_at 
             FROM users 
             ORDER BY id ASC 
             LIMIT 200'
        );
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return View::render('list', [
            'title' => 'Felhasználók listája',
            'users' => $users,
        ]);
    }

    // ---------------------------------------------------------
    // GET /users/{id}/edit
    //
    // Displays a simple edit form for a user.
    // ---------------------------------------------------------
    public function editForm(array $params): string
    {
        $id = (int)($params['id'] ?? 0);
        $user = null;

        if ($id > 0) {
            try {
                $stmt = DB::pdo()->prepare('SELECT id, name, email, status FROM users WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            } catch (\Throwable) {}
        }

        return View::render('user_form', [
            'title' => 'Felhasználó szerkesztése',
            'user'  => $user,
            'action'=> base_url('/users/' . $id . '/edit'),
        ]);
    }

    // ---------------------------------------------------------
    // POST /users/{id}/edit
    //
    // Handles the update request and saves changes.
    // ---------------------------------------------------------
    public function edit(array $params): void
    {
        if (!\verify_csrf()) {
            http_response_code(419);
            echo View::render('errors/419', [
                'title' => 'Page Expired',
                'message' => 'Invalid or missing CSRF token.',
            ]);
            exit;
        }

        $id     = (int)($params['id'] ?? 0);
        $name   = trim((string)($_POST['name'] ?? ''));
        $email  = trim((string)($_POST['email'] ?? ''));
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

        $ok = false;
        if ($id > 0 && $name !== '' && $email !== '') {
            try {
                $stmt = DB::pdo()->prepare('
                    UPDATE users 
                    SET name = :name, email = :email, status = :status, updated_at = NOW()
                    WHERE id = :id
                ');
                $ok = $stmt->execute([
                    ':name' => $name,
                    ':email'=> $email,
                    ':status'=> $status,
                    ':id'   => $id,
                ]);
            } catch (\Throwable) {}
        }

        if (function_exists('flash_set')) {
            flash_set($ok ? 'success' : 'error', $ok ? 'User updated successfully.' : 'Could not update user.');
        }

        header('Location: ' . base_url('/users'));
        exit;
    }
}
