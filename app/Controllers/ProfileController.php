<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\DB;
use Core\View;

final class ProfileController
{
    // ---------------------------------------------------------
    // GET /profile
    //
    // Displays the logged-in user's own profile details.
    // ---------------------------------------------------------
    public function view(): string
    {
        if (!is_logged_in()) {
            header('Location: ' . base_url('/login'));
            exit;
        }

        $current = current_user();
        $id = (int)$current['id'];

        $pdo = DB::pdo();

        // --- Fetch user data fresh from DB ---
        $stmt = $pdo->prepare('
            SELECT id, name, email, avatar, status, last_login_at, created_at, updated_at
            FROM users
            WHERE id = :id
        ');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        if (!$user) {
            http_response_code(404);
            return View::render('errors/404', [
                'title' => 'Profil nem található',
            ]);
        }

        // --- Fetch roles ---
        $stmt = $pdo->prepare('
            SELECT r.name, r.label
            FROM roles r
            JOIN user_roles ur ON ur.role_id = r.id
            WHERE ur.user_id = :uid
            ORDER BY r.name
        ');
        $stmt->execute([':uid' => $id]);
        $roles = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // --- Fetch permissions ---
        $stmt = $pdo->prepare('
            SELECT DISTINCT p.name, p.label
            FROM permissions p
            JOIN role_permissions rp ON rp.permission_id = p.id
            JOIN user_roles ur ON ur.role_id = rp.role_id
            WHERE ur.user_id = :uid
            ORDER BY p.name
        ');
        $stmt->execute([':uid' => $id]);
        $perms = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return View::render('profile/view', [
            'title' => 'Saját profilom',
            'user'  => $user,
            'roles' => $roles,
            'perms' => $perms,
        ]);
    }

    // ---------------------------------------------------------
    // POST /profile
    //
    // Saves profile changes (name, avatar only).
    // Email and roles are read-only.
    // ---------------------------------------------------------
    public function save(): void
    {
        if (!verify_csrf()) {
            http_response_code(419);
            echo View::render('errors/419', [
                'title' => 'Invalid CSRF token',
                'message' => 'The security verification for this request failed.',
            ]);
            exit;
        }

        if (!is_logged_in()) {
            header('Location: ' . base_url('/login'));
            exit;
        }

        $current = current_user();
        $id = (int)$current['id'];
        $name = trim((string)($_POST['name'] ?? ''));
        $avatar = trim((string)($_POST['avatar'] ?? ''));

        $ok = false;

        if ($name !== '') {
            try {
                $pdo = DB::pdo();

                // Always ensure updated_at moves forward to block Google sync overwrite
                $stmt = $pdo->prepare('
                    UPDATE users
                    SET name = :name,
                        avatar = :avatar,
                        updated_at = NOW()
                    WHERE id = :id
                ');

                $ok = $stmt->execute([
                    ':name'   => $name,
                    ':avatar' => $avatar,
                    ':id'     => $id,
                ]);
            } catch (\Throwable $e) {
                error_log('[ProfileController] save() failed: ' . $e->getMessage());
            }
        }

        if (function_exists('flash_set')) {
            flash_set(
                $ok ? 'success' : 'error',
                $ok ? 'Profile successfully updated.' : 'Failed to update profile.'
            );
        }

        header('Location: ' . base_url('/profile'));
        exit;
    }

}
