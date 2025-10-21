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
             FROM users WHERE deleted = 0   
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
        $newUserId = null;

        if ($name !== '' && $email !== '' && $pass !== '') {
            try {
                $stmt = DB::pdo()->prepare('
                    INSERT INTO users (name, email, password_hash, status, created_at, updated_at)
                    VALUES (:name, :email, crypt(:pass, gen_salt(\'bf\', 12)), :status, NOW(), NOW())
                    RETURNING id,uuid
                ');
                $stmt->execute([
                    ':name'   => $name,
                    ':email'  => $email,
                    ':pass'   => $pass,
                    ':status' => $status,
                ]);
                $newUserId = (int)$stmt->fetchColumn();
                $ok = $newUserId > 0;
            } catch (\Throwable $e) {
                error_log('[UsersController::createSave] ' . $e->getMessage());
            }
        }

        if ($ok && $newUserId) {
            // 🔔 Notify managers about the new user
            \Core\Messenger::broadcastPermission(
                'users.manage',
                'Új felhasználó létrehozva',
                'Egy új felhasználó lett létrehozva: ' . $name . ' (' . $email . ')',
                base_url('/users/' . $newUserId),
                'new_user'
            );
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
                // Lekérjük a lelkész státuszt is
                $stmt = DB::pdo()->prepare('
                    SELECT id, name, email, status, is_pastor
                    FROM users
                    WHERE id = :id
                ');
                $stmt->execute([':id' => $id]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            } catch (\Throwable $e) {
                error_log('[UsersController::editForm] ' . $e->getMessage());
            }
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

        $id       = (int)($params['id'] ?? 0);
        $name     = trim((string)($_POST['name'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $status   = isset($_POST['status']) ? (int)$_POST['status'] : 0;
        $isPastor = (int)($_POST['is_pastor'] ?? 0);

        $ok = false;

        if ($id > 0 && $name !== '' && $email !== '') {
            try {
                $pdo = DB::pdo();

                // Alapadatok frissítése
                $stmt = $pdo->prepare('
                    UPDATE users
                    SET name = :name,
                        email = :email,
                        status = :status,
                        updated_at = NOW()
                    WHERE id = :id
                ');
                $ok = $stmt->execute([
                    ':name'   => $name,
                    ':email'  => $email,
                    ':status' => $status,
                    ':id'     => $id,
                ]);

                // --- Mindig ellenőrizzük az aktuális DB állapotot ---
                $check = $pdo->prepare('SELECT is_pastor FROM users WHERE id = :id');
                $check->execute([':id' => $id]);
                $current = $check->fetchColumn();
                $current = filter_var($current, FILTER_VALIDATE_BOOLEAN);

                // Csak az admin tudja ezt futtatni
                if (function_exists('can') && can('users.manage')) {
                    // Ha a user lelkész (DB szerint), garantáljuk a 'lelkesz' role meglétét
                    if ($current === true) {
                        $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
                        $roleStmt->execute([':name' => 'lelkesz']);
                        $roleId = (int)$roleStmt->fetchColumn();

                        if ($roleId > 0) {
                            $checkRole = $pdo->prepare('
                                SELECT COUNT(*) FROM user_roles WHERE user_id = :uid AND role_id = :rid
                            ');
                            $checkRole->execute([':uid' => $id, ':rid' => $roleId]);
                            $hasRole = (int)$checkRole->fetchColumn() > 0;

                            if (!$hasRole) {
                                $addRole = $pdo->prepare('
                                    INSERT INTO user_roles (user_id, role_id)
                                    VALUES (:uid, :rid)
                                ');
                                $addRole->execute([':uid' => $id, ':rid' => $roleId]);
                                error_log("DEBUG: 'lelkesz' role auto-assigned to user id={$id}");
                            }
                        } else {
                            error_log("DEBUG: 'lelkesz' role not found in roles table!");
                        }
                    }
                }

            } catch (\Throwable $e) {
                error_log('[UsersController::edit] ' . $e->getMessage());
            }
        }

        if (function_exists('flash_set')) {
            flash_set(
                $ok ? 'success' : 'error',
                $ok ? 'A felhasználó módosítása sikeres.' : 'Nem sikerült módosítani a felhasználót.'
            );
        }

        header('Location: ' . base_url('/users'));
        exit;
    }


    // ---------------------------------------------------------
    // POST /users/{id}/delete
    //
    // Soft-deletes a user record: marks as inactive, deleted,
    // sets deleted_at timestamp and removes all role links.
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
            $pdo = DB::pdo();
            try {
                $pdo->beginTransaction();

                // Remove all role assignments for this user
                $pdo->prepare('DELETE FROM user_roles WHERE user_id = :id')
                    ->execute([':id' => $id]);

                // Soft-delete user (mark inactive + deleted)
                $stmt = $pdo->prepare('
                    UPDATE users
                    SET status = 0,
                        deleted = 1,
                        deleted_at = NOW(),
                        updated_at = NOW()
                    WHERE id = :id
                ');
                $stmt->execute([':id' => $id]);

                $pdo->commit();
                $ok = true;
            } catch (\Throwable $e) {
                $pdo->rollBack();
                error_log('[UserController::delete] ' . $e->getMessage());
            }
        }

        if (function_exists('flash_set')) {
            flash_set(
                $ok ? 'success' : 'error',
                $ok ? 'Felhasználó inaktiválva és töröltként megjelölve.' : 'Nem sikerült törölni a felhasználót.'
            );
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

    // ---------------------------------------------------------
    // GET /users/deleted
    // Lists deleted or inactive users for restoration.
    // ---------------------------------------------------------
    public function deleted_List(): string
    {
        $pdo = DB::pdo();
        $stmt = $pdo->query("
            SELECT id, name, email, status, deleted, deleted_at
            FROM users
            WHERE deleted = 1 
            ORDER BY deleted_at DESC NULLS LAST, id ASC
        ");
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return View::render('deleted_list', [
            'title' => 'Törölt / inaktív felhasználók',
            'perm'  => null,
            'users' => $users,
        ]);
    }

    // ---------------------------------------------------------
    // POST /users/{id}/restore
    // Restores a previously deleted user (admin only).
    // ---------------------------------------------------------
    public function restore(array $params): void
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
                $pdo = DB::pdo();
                $stmt = $pdo->prepare('
                    UPDATE users
                    SET deleted = 0,
                        deleted_at = NULL,
                        status = 0,
                        updated_at = NOW()
                    WHERE id = :id
                ');
                $ok = $stmt->execute([':id' => $id]);
            } catch (\Throwable $e) {
                if (function_exists('app_log')) {
                    app_log('[UserController::restore] ' . $e->getMessage());
                }
            }
        }

        if (function_exists('flash_set')) {
            flash_set(
                $ok ? 'success' : 'error',
                $ok ? 'Felhasználó sikeresen visszaállítva.' : 'Nem sikerült visszaállítani a felhasználót.'
            );
        }

        header('Location: ' . base_url('/users/deleted'));
        exit;
    }


}
