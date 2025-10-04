<?php
declare(strict_types=1);

namespace App\Apps\Users\Controllers;

use Core\DB;
use Core\View;

/**
 * RbacController
 *
 * RBAC admin (read-only for now):
 *  - /rbac              → dashboard
 *  - /rbac/roles        → list roles
 *  - /rbac/permissions  → list permissions
 *  - /rbac/assignments  → list user↔role and role↔permission mappings
 *
 * Requires permission: "rbac.manage" (enforced by route middleware).
 */
final class RbacController
{
    /**
     * GET /rbac
     * Simple dashboard page with links to subpages.
     */
    public function index(): string
    {
        return View::render('rbac/index', [
            'title' => 'RBAC jogosultságkezelés',
        ]);
    }

    /**
     * GET /rbac/roles
     * List all roles.
     */
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

    /**
     * GET /rbac/permissions
     * List all permissions.
     */
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

    /**
     * GET /rbac/assignments
     * Read-only overview of user↔role and role↔permission mappings.
     */
    public function assignments(): string
    {
        $userRoles = [];
        $rolePerms = [];

        try {
            $pdo = DB::pdo();

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
            'title'      => 'RBAC – Hozzárendelések',
            'userRoles'  => $userRoles,
            'rolePerms'  => $rolePerms,
        ]);
    }
}
