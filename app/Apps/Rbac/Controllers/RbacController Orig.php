<?php
declare(strict_types=1);

namespace App\Apps\Rbac\Controllers;

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
        return View::render('index', [
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

        return View::render('roles', [
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

        return View::render('permissions', [
            'title'       => 'RBAC – Jogosultságok',
            'permissions' => $perms,
        ]);
    }

    // ---------------------------------------------------------
    // GET /rbac/assignments * Overview + attach/detach + pagination + sorting
    // ---------------------------------------------------------
    public function assignments(): string
    {
        $userRoles = [];
        $rolePerms = [];
        $users = [];
        $roles = [];
        $perms = [];

        // --- paging (page/per) + védelem ---
        $allowedPer = [10, 25, 50, 100];

        $ur_page = max(1, (int)($_GET['ur_page'] ?? 1));
        $ur_per  = (int)($_GET['ur_per'] ?? 25);
        if (!in_array($ur_per, $allowedPer, true)) { $ur_per = 25; }
        $ur_offset = ($ur_page - 1) * $ur_per;

        $rp_page = max(1, (int)($_GET['rp_page'] ?? 1));
        $rp_per  = (int)($_GET['rp_per'] ?? 25);
        if (!in_array($rp_per, $allowedPer, true)) { $rp_per = 25; }
        $rp_offset = ($rp_page - 1) * $rp_per;

        // --- sorting (whitelist) ---
        $urAllowed = ['user_id','user_name','user_email','role_id','role_name','role_label'];
        $urSort = isset($_GET['ur_sort']) && in_array($_GET['ur_sort'], $urAllowed, true) ? (string)$_GET['ur_sort'] : 'user_id';
        $urDir  = (isset($_GET['ur_dir']) && strtolower((string)$_GET['ur_dir']) === 'desc') ? 'DESC' : 'ASC';
        $urColMap = [
            'user_id'    => 'u.id',
            'user_name'  => 'u.name',
            'user_email' => 'u.email',
            'role_id'    => 'r.id',
            'role_name'  => 'r.name',
            'role_label' => 'r.label',
        ];
        $urOrderBy = $urColMap[$urSort] . ' ' . $urDir;

        $rpAllowed = ['role_id','role_name','role_label','permission_id','perm_name','perm_label'];
        $rpSort = isset($_GET['rp_sort']) && in_array($_GET['rp_sort'], $rpAllowed, true) ? (string)$_GET['rp_sort'] : 'role_name';
        $rpDir  = (isset($_GET['rp_dir']) && strtolower((string)$_GET['rp_dir']) === 'desc') ? 'DESC' : 'ASC';
        $rpColMap = [
            'role_id'       => 'r.id',
            'role_name'     => 'r.name',
            'role_label'    => 'r.label',
            'permission_id' => 'p.id',
            'perm_name'     => 'p.name',
            'perm_label'    => 'p.label',
        ];
        $rpOrderBy = $rpColMap[$rpSort] . ' ' . $rpDir;

        // Lapozó infók alapértékekkel (fallback)
        $urPager = ['page'=>$ur_page, 'per'=>$ur_per, 'total'=>0, 'pages'=>1];
        $rpPager = ['page'=>$rp_page, 'per'=>$rp_per, 'total'=>0, 'pages'=>1];

        try {
            $pdo = DB::pdo();

            // Select-listek az attach űrlapokhoz
            $roles = $pdo->query('SELECT id, name, label FROM roles ORDER BY name ASC')
                        ->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $perms = $pdo->query('SELECT id, name, label FROM permissions ORDER BY name ASC')
                        ->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $users = $pdo->query("SELECT id, COALESCE(NULLIF(TRIM(name), ''), email) AS name, email
                                FROM users
                                ORDER BY email ASC")
                        ->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // --- user_roles: total count ---
            $st = $pdo->query('
                SELECT COUNT(*) AS c
                FROM user_roles ur
                JOIN users u ON u.id = ur.user_id
                JOIN roles r ON r.id = ur.role_id
            ');
            $ur_total = (int)($st->fetchColumn() ?: 0);
            $urPager['total'] = $ur_total;
            $urPager['pages'] = max(1, (int)ceil($ur_total / max(1, $ur_per)));
            if ($ur_page > $urPager['pages']) {
                $ur_page = $urPager['pages'];
                $ur_offset = ($ur_page - 1) * $ur_per;
                $urPager['page'] = $ur_page;
            }

            // --- user_roles: paginated + sorted rows ---
            $sqlUr = '
                SELECT ur.user_id,
                    u.name  AS user_name,
                    u.email AS user_email,
                    ur.role_id,
                    r.name  AS role_name,
                    r.label AS role_label
                FROM user_roles ur
                JOIN users u ON u.id = ur.user_id
                JOIN roles r ON r.id = ur.role_id
                ORDER BY ' . $urOrderBy . '
                LIMIT :lim OFFSET :off
            ';
            $st = $pdo->prepare($sqlUr);
            $st->bindValue(':lim', $ur_per, \PDO::PARAM_INT);
            $st->bindValue(':off', $ur_offset, \PDO::PARAM_INT);
            $st->execute();
            $userRoles = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            // --- role_permissions: total count ---
            $st = $pdo->query('
                SELECT COUNT(*) AS c
                FROM role_permissions rp
                JOIN roles r       ON r.id = rp.role_id
                JOIN permissions p ON p.id = rp.permission_id
            ');
            $rp_total = (int)($st->fetchColumn() ?: 0);
            $rpPager['total'] = $rp_total;
            $rpPager['pages'] = max(1, (int)ceil($rp_total / max(1, $rp_per)));
            if ($rp_page > $rpPager['pages']) {
                $rp_page = $rpPager['pages'];
                $rp_offset = ($rp_page - 1) * $rp_per;
                $rpPager['page'] = $rp_page;
            }

            // --- role_permissions: paginated + sorted rows ---
            $sqlRp = '
                SELECT rp.role_id,
                    r.name  AS role_name,
                    r.label AS role_label,
                    rp.permission_id,
                    p.name  AS perm_name,
                    p.label AS perm_label
                FROM role_permissions rp
                JOIN roles r        ON r.id = rp.role_id
                JOIN permissions p  ON p.id = rp.permission_id
                ORDER BY ' . $rpOrderBy . '
                LIMIT :lim OFFSET :off
            ';
            $st = $pdo->prepare($sqlRp);
            $st->bindValue(':lim', $rp_per, \PDO::PARAM_INT);
            $st->bindValue(':off', $rp_offset, \PDO::PARAM_INT);
            $st->execute();
            $rolePerms = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        } catch (\Throwable $e) {
            // swallow; a view üres állapotot mutat
        }

        return View::render('rbac/assignments', [
            'title'       => 'RBAC – Hozzárendelések',
            'userRoles'   => $userRoles,
            'rolePerms'   => $rolePerms,
            'users'       => $users,
            'roles'       => $roles,
            'permissions' => $perms,
            'urPager'     => $urPager,
            'rpPager'     => $rpPager,
            'urSort'      => $urSort,
            'urDir'       => $urDir,
            'rpSort'      => $rpSort,
            'rpDir'       => $rpDir,
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
        return View::render('role_form', [
            'title' => 'RBAC – Új szerep',
            'role'  => null,
            'action' => base_url('/rbac/roles/create'),
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

        $ok = false;
        if ($name !== '' && $label !== '') {
            try {
                $stmt = DB::pdo()->prepare('INSERT INTO roles(name, label) VALUES(:n,:l)');
                $ok = $stmt->execute([':n' => $name, ':l' => $label]);
            } catch (\Throwable $e) {
                $ok = false;
            }
        }
        if (function_exists('flash_set')) {
            if ($ok) {
                flash_set('success', 'Role created successfully.');
            } else {
                flash_set('error', 'Could not create role.');
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
        return View::render('role_form', [
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

        $ok = false;
        if ($id > 0 && $name !== '' && $label !== '') {
            try {
                $st = DB::pdo()->prepare('UPDATE roles SET name=:n, label=:l, updated_at=NOW() WHERE id=:id');
                $ok = $st->execute([':n'=>$name, ':l'=>$label, ':id'=>$id]);
            } catch (\Throwable) {}
        }
        if (function_exists('flash_set')) {
            flash_set($ok ? 'success' : 'error', $ok ? 'Role updated successfully.' : 'Could not update role.');
        }
        header('Location: ' . base_url('/roles')); exit;
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
        $ok = false;

        if ($id > 0) {
            try {
                $pdo = DB::pdo();

                // --- Check role name ---
                $st = $pdo->prepare('SELECT name FROM roles WHERE id=:id');
                $st->execute([':id'=>$id]);
                $roleName = (string)($st->fetchColumn() ?? '');

                // --- Keep-one-admin guard ---
                if ($roleName === 'admin') {
                    $count = (int)$pdo->query('SELECT COUNT(*) FROM user_roles WHERE role_id = '.$id)->fetchColumn();
                    if ($count > 0) {
                        \flash_set('error', 'Cannot delete the admin role while it is assigned to users.');
                        header('Location: ' . base_url('/rbac/roles')); exit;
                    }
                }

                // If passed guard, safe to delete
                $st = $pdo->prepare('DELETE FROM roles WHERE id=:id');
                $ok = $st->execute([':id'=>$id]);
            } catch (\Throwable) {}
        }

        if (function_exists('flash_set')) {
            flash_set($ok ? 'success' : 'error', $ok ? 'Role deleted.' : 'Could not delete role.');
        }
        header('Location: ' . base_url('/roles')); exit;
    }


    // ---------------------------------------------------------
    // PERMISSIONS – CREATE / EDIT / DELETE
    // ---------------------------------------------------------

    // ---------------------------------------------------------
    // GET /rbac/permissions/create 
    // ---------------------------------------------------------
    public function permCreateForm(): string
    {
        return View::render('perm_form', [
            'title' => 'RBAC – Új jogosultság',
            'perm'  => null,
            'action' => base_url('/permissions/create'),
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

        $ok = false;
        if ($name !== '' && $label !== '') {
            try {
                $stmt = DB::pdo()->prepare('INSERT INTO permissions(name, label) VALUES(:n,:l)');
                $ok = $stmt->execute([':n' => $name, ':l' => $label]);
            } catch (\Throwable $e) {}
        }
        if (function_exists('flash_set')) {
            flash_set($ok ? 'success' : 'error', $ok ? 'Permission created successfully.' : 'Could not create permission.');
        }
        header('Location: ' . base_url('/permissions')); exit;
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

        $ok = false;
        if ($id > 0 && $name !== '' && $label !== '') {
            try {
                $st = DB::pdo()->prepare('UPDATE permissions SET name=:n, label=:l, updated_at=NOW() WHERE id=:id');
                $ok = $st->execute([':n'=>$name, ':l'=>$label, ':id'=>$id]);
            } catch (\Throwable) {}
        }
        if (function_exists('flash_set')) {
            flash_set($ok ? 'success' : 'error', $ok ? 'Permission updated successfully.' : 'Could not update permission.');
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

        $ok = false;
        if ($id > 0) {
            try {
                $st = DB::pdo()->prepare('DELETE FROM permissions WHERE id=:id');
                $ok = $st->execute([':id'=>$id]);
            } catch (\Throwable) {}
        }
        if (function_exists('flash_set')) {
            flash_set($ok ? 'success' : 'error', $ok ? 'Permission deleted.' : 'Could not delete permission.');
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
        $ok = false;
        try {
            if ($type === 'role_perm') {
                $roleId = (int)($_POST['role_id'] ?? 0);
                $permId = (int)($_POST['permission_id'] ?? 0);
                if ($roleId > 0 && $permId > 0) {
                    $ok = DB::pdo()->prepare(
                        'INSERT INTO role_permissions(role_id, permission_id) VALUES(:r,:p)
                         ON CONFLICT DO NOTHING'
                    )->execute([':r'=>$roleId, ':p'=>$permId]);
                }
            } elseif ($type === 'user_role') {
                $userId = (int)($_POST['user_id'] ?? 0);
                $roleId = (int)($_POST['role_id'] ?? 0);
                if ($userId > 0 && $roleId > 0) {
                    $ok = DB::pdo()->prepare(
                        'INSERT INTO user_roles(user_id, role_id) VALUES(:u,:r)
                         ON CONFLICT DO NOTHING'
                    )->execute([':u'=>$userId, ':r'=>$roleId]);
                }
            }
        } catch (\Throwable) { $ok = false; }

        if (function_exists('flash_set')) {
            flash_set($ok ? 'success' : 'error', $ok ? 'Assignment attached.' : 'Could not attach assignment.');
        }
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
        $ok = false;

        try {
            if ($type === 'role_perm') {
                $roleId = (int)($_POST['role_id'] ?? 0);
                $permId = (int)($_POST['permission_id'] ?? 0);
                if ($roleId > 0 && $permId > 0) {
                    $ok = DB::pdo()->prepare(
                        'DELETE FROM role_permissions WHERE role_id=:r AND permission_id=:p'
                    )->execute([':r'=>$roleId, ':p'=>$permId]);
                }
            } elseif ($type === 'user_role') {
                $userId = (int)($_POST['user_id'] ?? 0);
                $roleId = (int)($_POST['role_id'] ?? 0);
                $current = \current_user();

                if ($userId > 0 && $roleId > 0) {
                    $pdo = DB::pdo();

                    // ---- Self-demote guard ----
                    $st = $pdo->prepare('SELECT name FROM roles WHERE id = :id');
                    $st->execute([':id' => $roleId]);
                    $roleName = (string)($st->fetchColumn() ?? '');

                    if ($roleName === 'admin' && $current && (int)$current['id'] === $userId) {
                        \flash_set('error', 'You cannot remove your own admin role.');
                        header('Location: ' . base_url('/rbac/assignments')); exit;
                    }

                    // ---- Keep-one-admin guard ----
                    if ($roleName === 'admin') {
                        $count = (int)$pdo->query('SELECT COUNT(*) FROM user_roles WHERE role_id = '.$roleId)->fetchColumn();
                        if ($count <= 1) {
                            \flash_set('error', 'At least one admin user must remain.');
                            header('Location: ' . base_url('/rbac/assignments')); exit;
                        }
                    }

                    // If passed both guards → perform delete
                    $ok = $pdo->prepare(
                        'DELETE FROM user_roles WHERE user_id=:u AND role_id=:r'
                    )->execute([':u'=>$userId, ':r'=>$roleId]);
                }
            }
        } catch (\Throwable) { $ok = false; }

        if (function_exists('flash_set')) {
            flash_set($ok ? 'success' : 'error', $ok ? 'Assignment detached.' : 'Could not detach assignment.');
        }
        header('Location: ' . base_url('/rbac/assignments')); exit;
    }

// End Class
}
