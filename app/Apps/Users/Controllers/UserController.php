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
    // ---------------------------------------------------------
    public function index(): string
    {
        $pdo = DB::pdo();

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
    // GET /users/new
    //
    // Displays a blank form for creating a new user.
    // ---------------------------------------------------------
    public function createForm(): string
    {
        return View::render('user_form', [
            'title'  => 'Új felhasználó létrehozása',
            'user'   => null,
            'action' => base_url('/users/new'),
        ]);
    }

    // ---------------------------------------------------------
    // POST /users/new
    //
    // Handles saving a newly created user.
    // ---------------------------------------------------------
    public function createSave(): void
    {
        if (!\verify_csrf()) {
            http_response_code(419);
            echo View::render('errors/419', [
                'title' => 'Page Expired',
                'message' => 'Invalid or missing CSRF token.',
            ]);
            exit;
        }

        $name   = trim((string)($_POST['name'] ?? ''));
        $email  = trim((string)($_POST['email'] ?? ''));
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        $pass   = trim((string)($_POST['password'] ?? ''));

        $ok = false;
        if ($name !== '' && $email !== '' && $pass !== '') {
            try {
                $stmt = DB::pdo()->prepare('
                    INSERT INTO users (name, email, password_hash, status, created_at, updated_at)
                    VALUES (:name, :email, crypt(:pass, gen_salt(\'bf\', 12)), :status, NOW(), NOW())
                ');
                $ok = $stmt->execute([
                    ':name'   => $name,
                    ':email'  => $email,
                    ':pass'   => $pass,
                    ':status' => $status,
                ]);
            } catch (\Throwable) {}
        }

        if (function_exists('flash_set')) {
            flash_set($ok ? 'success' : 'error', $ok ? 'User created successfully.' : 'Failed to create user.');
        }

        header('Location: ' . base_url('/users'));
        exit;
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

    // ---------------------------------------------------------
    // POST /users/{id}/delete
    //
    // Permanently deletes a user record.
    // ---------------------------------------------------------
    public function delete(array $params): void
    {
        if (!\verify_csrf()) {
            http_response_code(419);
            echo View::render('errors/419', [
                'title' => 'Page Expired',
                'message' => 'Invalid or missing CSRF token.',
            ]);
            exit;
        }

        $id = (int)($params['id'] ?? 0);
        $ok = false;

        if ($id > 0) {
            try {
                $stmt = DB::pdo()->prepare('DELETE FROM users WHERE id = :id');
                $ok = $stmt->execute([':id' => $id]);
            } catch (\Throwable) {}
        }

        if (function_exists('flash_set')) {
            flash_set($ok ? 'success' : 'error', $ok ? 'Felhasználó törölve.' : 'Nem sikerült törölni a felhasználót.');
        }

        header('Location: ' . base_url('/users'));
        exit;
    }

    // ---------------------------------------------------------
    // GET /users/{id}/view
    //
    // Displays user details, roles, permissions, assignments.
    // ---------------------------------------------------------
    public function view(array $params): string
    {
        $id = (int)($params['id'] ?? 0);
        $user = null;
        $roles = [];
        $perms = [];

        if ($id > 0) {
            $pdo = DB::pdo();

            // Alapadatok
            $stmt = $pdo->prepare('SELECT id, name, email, status, avatar, last_login_at, created_at, updated_at 
                                FROM users WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

            if ($user) {
                // Szerepek
                $sqlRoles = '
                    SELECT r.id, r.name, r.label
                    FROM roles r
                    JOIN user_roles ur ON ur.role_id = r.id
                    WHERE ur.user_id = :uid
                    ORDER BY r.name ASC
                ';
                $stmt = $pdo->prepare($sqlRoles);
                $stmt->execute([':uid' => $id]);
                $roles = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

                // Jogosultságok (a szerepeken keresztül)
              $sqlPerms = '
                SELECT DISTINCT p.name, p.label
                FROM permissions p
                JOIN role_permissions rp ON rp.permission_id = p.id
                JOIN roles r ON r.id = rp.role_id
                JOIN user_roles ur ON ur.role_id = r.id
                WHERE ur.user_id = :uid
                ORDER BY p.name ASC
            ';

                $stmt = $pdo->prepare($sqlPerms);
                $stmt->execute([':uid' => $id]);
                $perms = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            }
        }

        if (!$user) {
            http_response_code(404);
            return View::render('errors/404', [
                'title' => 'Felhasználó nem található',
            ]);
        }

        return View::render('view', [
            'title' => 'Felhasználó adatai',
            'user' => $user,
            'roles' => $roles,
            'perms' => $perms,
        ]);
    }


}
