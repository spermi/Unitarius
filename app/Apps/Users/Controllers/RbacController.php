<?php
declare(strict_types=1);

namespace App\Apps\Users\Controllers;

use Core\DB;
use Core\View;

// ---------------------------------------------------------
// RbacController
//
// RBAC admin controller:
//  - /rbac              → dashboard
//  - /rbac/roles        → list roles
//  - /rbac/permissions  → list permissions
//  - /rbac/assignments  → list user↔role and role↔permission mappings
//
// Requires permission: "rbac.manage" (enforced by route middleware).
// ---------------------------------------------------------
final class RbacController
{
    // ---------------------------------------------------------
    // GET /rbac  * Simple dashboard page with links to subpages.
    // ---------------------------------------------------------
    public function index(): string
    {
        return View::render('rbac/index', [
            'title' => 'RBAC jogosultságkezelés',
        ]);
    }

    // ---------------------------------------------------------
    // GET /rbac/roles * List all roles.
    // ---------------------------------------------------------
    public function roles(): string
    {
        $roles = [];
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->query(
                'SELECT id, name, label, created_at, updated_at
                 FROM roles
                 ORDER BY id ASC
                 LIMIT 500'
            );
            $roles = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            // swallow; view will show empty state
        }

        return View::render('rbac/roles', [
            'title' => 'RBAC – Szerepek',
            'roles' => $roles,
        ]);
    }

    // ---------------------------------------------------------
    // GET /rbac/permissions * List all permissions.
    // ---------------------------------------------------------
    public function permissions(): string
    {
        $perms = [];
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->query(
                'SELECT id, name, label, created_at, updated_at
                 FROM permissions
                 ORDER BY name ASC
                 LIMIT 1000'
            );
            $perms = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            // swallow; view will show empty state
        }

        return View::render('rbac/permissions', [
            'title'       => 'RBAC – Jogosultságok',
            'permissions' => $perms,
        ]);
    }

    // ---------------------------------------------------------
    // GET /rbac/assignments * Overview + attach/detach form adatsorok.
    // ---------------------------------------------------------
    public function assignments(): string
    {
        $userRoles = [];
        $rolePerms = [];
        $users = [];
        $roles = [];
        $perms = [];

        try {
            $pdo = DB::pdo();

            // Select-listák az attach űrlapokhoz
            $roles = $pdo->query('SELECT id, name, label FROM roles ORDER BY name ASC')
                         ->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $perms = $pdo->query('SELECT id, name, label FROM permissions ORDER BY name ASC')
                         ->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $users = $pdo->query("SELECT id, COALESCE(NULLIF(TRIM(name), ''), email) AS name, email
                                  FROM users
                                  ORDER BY email ASC")
                         ->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // user ↔ role mappings (with names and emails)
            $stmt = $pdo->query(
                'SELECT ur.user_id,
                        u.name  AS user_name,
                        u.email AS user_email,
                        ur.role_id,
                        r.name  AS role_name,
                        r.label AS role_label
                 FROM user_roles ur
                 JOIN users u ON u.id = ur.user_id
                 JOIN roles r ON r.id = ur.role_id
                 ORDER BY u.id ASC, r.name ASC
                 LIMIT 2000'
            );
            $userRoles = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // role ↔ permission mappings
            $stmt = $pdo->query(
                'SELECT rp.role_id,
                        r.name  AS role_name,
                        r.label AS role_label,
                        rp.permission_id,
                        p.name  AS perm_name,
                        p.label AS perm_label
                 FROM role_permissions rp
                 JOIN roles r        ON r.id = rp.role_id
                 JOIN permissions p  ON p.id = rp.permission_id
                 ORDER BY r.name ASC, p.name ASC
                 LIMIT 5000'
            );
            $rolePerms = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            // swallow; view will show empty state
        }

        return View::render('rbac/assignments', [
            'title'       => 'RBAC – Hozzárendelések',
            'userRoles'   => $userRoles,
            'rolePerms'   => $rolePerms,
            'users'       => $users,
            'roles'       => $roles,
            'permissions' => $perms,
        ]);
    }

    // -----------------------------
    // ROLES – CREATE / EDIT / DELETE
    // -----------------------------

    // ---------------------------------------------------------
    // GET /rbac/roles/create 
    // ---------------------------------------------------------
    public function roleCreateForm(): string
    {
        return View::render('rbac/role_form', [
            'title' => 'RBAC – Új szerep',
            'role'  => null,
        ]);
    }

    // ---------------------------------------------------------
    // POST /rbac/roles/create 
    // ---------------------------------------------------------
    public function roleCreate(): void
    {
        if (!\verify_csrf()) {
            http_response_code(419);
            echo View::render('errors/419', [
                'title' => 'Page Expired',
                'message' => 'Invalid or missing CSRF token.',
            ], null);
            exit;
        }

        $name  = trim((string)($_POST['name']  ?? ''));
        $label = trim((string)($_POST['label'] ?? ''));

        if ($name !== '' && $label !== '') {
            try {
                $stmt = DB::pdo()->prepare('INSERT INTO roles(name, label) VALUES(:n,:l)');
                $stmt->execute([':n' => $name, ':l' => $label]);
            } catch (\Throwable $e) {
                // swallow -> redirect to list
            }
        }
        header('Location: ' . base_url('/rbac/roles')); exit;
    }

    // ---------------------------------------------------------
    // GET /rbac/roles/{id}/edit 
    // ---------------------------------------------------------
    public function roleEditForm(array $params): string
    {
        $id = (int)($params['id'] ?? 0);
        $role = null;
        if ($id > 0) {
            try {
                $st = DB::pdo()->prepare('SELECT id, name, label FROM roles WHERE id=:id');
                $st->execute([':id' => $id]);
                $role = $st->fetch(\PDO::FETCH_ASSOC) ?: null;
            } catch (\Throwable) {}
        }
        return View::render('rbac/role_form', [
            'title' => 'RBAC – Szerep szerkesztése',
            'role'  => $role,
        ]);
    }

    // ---------------------------------------------------------
    // POST /rbac/roles/{id}/edit 
    // ---------------------------------------------------------
    public function roleEdit(array $params): void
    {
        if (!\verify_csrf()) {
            http_response_code(419);
            echo View::render('errors/419', [
                'title' => 'Page Expired',
                'message' => 'Invalid or missing CSRF token.',
            ], null);
            exit;
        }

        $id    = (int)($params['id'] ?? 0);
        $name  = trim((string)($_POST['name']  ?? ''));
        $label = trim((string)($_POST['label'] ?? ''));

        if ($id > 0 && $name !== '' && $label !== '') {
            try {
                $st = DB::pdo()->prepare('UPDATE roles SET name=:n, label=:l, updated_at=NOW() WHERE id=:id');
                $st->execute([':n'=>$name, ':l'=>$label, ':id'=>$id]);
            } catch (\Throwable) {}
        }
        header('Location: ' . base_url('/rbac/roles')); exit;
    }

    // ---------------------------------------------------------
    // POST /rbac/roles/{id}/delete 
    // ---------------------------------------------------------
    public function roleDelete(array $params): void
    {
        if (!\verify_csrf()) {
            http_response_code(419);
            echo View::render('errors/419', [
                'title' => 'Page Expired',
                'message' => 'Invalid or missing CSRF token.',
            ], null);
            exit;
        }

        $id = (int)($params['id'] ?? 0);

        if ($id > 0) {
            try {
                // TODO: later guard "keep at least one admin".
                $st = DB::pdo()->prepare('DELETE FROM roles WHERE id=:id');
                $st->execute([':id'=>$id]);
            } catch (\Throwable) {}
        }
        header('Location: ' . base_url('/rbac/roles')); exit;
    }

    // ---------------------------------------------------------
    // PERMISSIONS – CREATE / EDIT / DELETE
    // ---------------------------------------------------------

    // ---------------------------------------------------------
    // GET /rbac/permissions/create 
    // ---------------------------------------------------------
    public function permCreateForm(): string
    {
        return View::render('rbac/perm_form', [
            'title' => 'RBAC – Új jogosultság',
            'perm'  => null,
        ]);
    }

    // ---------------------------------------------------------
    // POST /rbac/permissions/create 
    // ---------------------------------------------------------
    public function permCreate(): void
    {
        if (!\verify_csrf()) {
            http_response_code(419);
            echo View::render('errors/419', [
                'title' => 'Page Expired',
                'message' => 'Invalid or missing CSRF token.',
            ], null);
            exit;
        }

        $name  = trim((string)($_POST['name']  ?? ''));
        $label = trim((string)($_POST['label'] ?? ''));

        if ($name !== '' && $label !== '') {
            try {
                $stmt = DB::pdo()->prepare('INSERT INTO permissions(name, label) VALUES(:n,:l)');
                $stmt->execute([':n' => $name, ':l' => $label]);
            } catch (\Throwable $e) {}
        }
        header('Location: ' . base_url('/rbac/permissions')); exit;
    }

    // ---------------------------------------------------------
    // GET /rbac/permissions/{id}/edit 
    // ---------------------------------------------------------
    public function permEditForm(array $params): string
    {
        $id = (int)($params['id'] ?? 0);
        $perm = null;
        if ($id > 0) {
            try {
                $st = DB::pdo()->prepare('SELECT id, name, label FROM permissions WHERE id=:id');
                $st->execute([':id'=>$id]);
                $perm = $st->fetch(\PDO::FETCH_ASSOC) ?: null;
            } catch (\Throwable) {}
        }
        return View::render('rbac/perm_form', [
            'title' => 'RBAC – Jogosultság szerkesztése',
            'perm'  => $perm,
        ]);
    }

    // ---------------------------------------------------------
    // POST /rbac/permissions/{id}/edit 
    // ---------------------------------------------------------
    public function permEdit(array $params): void
    {
        if (!\verify_csrf()) {
            http_response_code(419);
            echo View::render('errors/419', [
                'title' => 'Page Expired',
                'message' => 'Invalid or missing CSRF token.',
            ], null);
            exit;
        }

        $id    = (int)($params['id'] ?? 0);
        $name  = trim((string)($_POST['name']  ?? ''));
        $label = trim((string)($_POST['label'] ?? ''));

        if ($id > 0 && $name !== '' && $label !== '') {
            try {
                $st = DB::pdo()->prepare('UPDATE permissions SET name=:n, label=:l, updated_at=NOW() WHERE id=:id');
                $st->execute([':n'=>$name, ':l'=>$label, ':id'=>$id]);
            } catch (\Throwable) {}
        }
        header('Location: ' . base_url('/rbac/permissions')); exit;
    }

    // ---------------------------------------------------------
    // POST /rbac/permissions/{id}/delete 
    // ---------------------------------------------------------
    public function permDelete(array $params): void
    {
        if (!\verify_csrf()) {
            http_response_code(419);
            echo View::render('errors/419', [
                'title' => 'Page Expired',
                'message' => 'Invalid or missing CSRF token.',
            ], null);
            exit;
        }

        $id = (int)($params['id'] ?? 0);

        if ($id > 0) {
            try {
                $st = DB::pdo()->prepare('DELETE FROM permissions WHERE id=:id');
                $st->execute([':id'=>$id]);
            } catch (\Throwable) {}
        }
        header('Location: ' . base_url('/rbac/permissions')); exit;
    }

    // ---------------------------------------------------------
    // ASSIGNMENTS – ATTACH / DETACH
    // ---------------------------------------------------------

    // ---------------------------------------------------------
    // POST /rbac/assignments/attach 
    // ---------------------------------------------------------
    public function assignmentsAttach(): void
    {
        if (!\verify_csrf()) {
            http_response_code(419);
            echo View::render('errors/419', [
                'title' => 'Page Expired',
                'message' => 'Invalid or missing CSRF token.',
            ], null);
            exit;
        }

        $type = (string)($_POST['type'] ?? ''); // 'role_perm' or 'user_role'
        try {
            if ($type === 'role_perm') {
                $roleId = (int)($_POST['role_id'] ?? 0);
                $permId = (int)($_POST['permission_id'] ?? 0);
                if ($roleId > 0 && $permId > 0) {
                    DB::pdo()->prepare(
                        'INSERT INTO role_permissions(role_id, permission_id) VALUES(:r,:p)
                         ON CONFLICT DO NOTHING'
                    )->execute([':r'=>$roleId, ':p'=>$permId]);
                }
            } elseif ($type === 'user_role') {
                $userId = (int)($_POST['user_id'] ?? 0);
                $roleId = (int)($_POST['role_id'] ?? 0);
                if ($userId > 0 && $roleId > 0) {
                    DB::pdo()->prepare(
                        'INSERT INTO user_roles(user_id, role_id) VALUES(:u,:r)
                         ON CONFLICT DO NOTHING'
                    )->execute([':u'=>$userId, ':r'=>$roleId]);
                }
            }
        } catch (\Throwable) {}
        header('Location: ' . base_url('/rbac/assignments')); exit;
    }

    // ---------------------------------------------------------
    // POST /rbac/assignments/detach 
    // ---------------------------------------------------------
    public function assignmentsDetach(): void
    {
        if (!\verify_csrf()) {
            http_response_code(419);
            echo View::render('errors/419', [
                'title' => 'Page Expired',
                'message' => 'Invalid or missing CSRF token.',
            ], null);
            exit;
        }

        $type = (string)($_POST['type'] ?? ''); // 'role_perm' or 'user_role'
        try {
            if ($type === 'role_perm') {
                $roleId = (int)($_POST['role_id'] ?? 0);
                $permId = (int)($_POST['permission_id'] ?? 0);
                if ($roleId > 0 && $permId > 0) {
                    // TODO: later guard for "do not remove last admin permission".
                    DB::pdo()->prepare(
                        'DELETE FROM role_permissions WHERE role_id=:r AND permission_id=:p'
                    )->execute([':r'=>$roleId, ':p'=>$permId]);
                }
            } elseif ($type === 'user_role') {
                $userId = (int)($_POST['user_id'] ?? 0);
                $roleId = (int)($_POST['role_id'] ?? 0);
                if ($userId > 0 && $roleId > 0) {
                    // TODO: self-demote guard later.
                    DB::pdo()->prepare(
                        'DELETE FROM user_roles WHERE user_id=:u AND role_id=:r'
                    )->execute([':u'=>$userId, ':r'=>$roleId]);
                }
            }
        } catch (\Throwable) {}
        header('Location: ' . base_url('/rbac/assignments')); exit;
    }

}
