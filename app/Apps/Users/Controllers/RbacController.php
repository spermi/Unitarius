<?php
declare(strict_types=1);

namespace App\Apps\Users\Controllers;

use Core\View;

/**
 * RbacController
 *
 * Handles RBAC configuration pages (roles, permissions, mappings).
 * Currently only displays a placeholder.
 * Accessible only to users with the "rbac.manage" permission.
 */
final class RbacController
{
    /**
     * GET /rbac
     *
     * Displays the RBAC management dashboard placeholder.
     */
    public function index(): string
    {
        return View::render('rbac/index', [
            'title' => 'RBAC jogosultságkezelés',
        ]);
    }
}
