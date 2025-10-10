<?php
declare(strict_types=1);

/**
 * Users app manifest
 * - Defines menu structure for user management and RBAC.
 * - Integrates with permission system (RequirePermission middleware).
 * - MenuLoader automatically filters inactive items (future enhancement).
 */

return [
    'name'    => 'users',
    'label'   => 'Felhasználók',
    'icon'    => 'fa-solid fa-user-gear',
    'prefix'  => '/users',
    'order'   => 20,
    'match'   => ['#^/users#', '#^/rbac#'],
    'perm'    => null, // parent toggler only
    'children'=> [
        [
            'label' => 'Felhasználók',
            'url'   => base_url('/users'),
            'match' => ['#^/users$#', '#^/users/#'],
            'icon'  => 'fa-regular fa-address-book',
            'order' => 1,
            'perm'  => 'users.view',
        ],
        [
            'label' => 'RBAC – Dashboard',
            'url'   => base_url('/rbac'),
            'match' => ['#^/rbac$#'],
            'icon'  => 'fa-solid fa-shield-halved',
            'order' => 2,
            'perm'  => 'rbac.manage',
        ],
        [
            'label' => 'RBAC – Szerepek',
            'url'   => base_url('/rbac/roles'),
            'match' => ['#^/rbac/roles#'],
            'icon'  => 'fa-regular fa-id-badge',
            'order' => 3,
            'perm'  => 'rbac.manage',
        ],
        [
            'label' => 'RBAC – Jogosultságok',
            'url'   => base_url('/rbac/permissions'),
            'match' => ['#^/rbac/permissions#'],
            'icon'  => 'fa-regular fa-keyboard',
            'order' => 4,
            'perm'  => 'rbac.manage',
        ],
        [
            'label' => 'RBAC – Hozzárendelések',
            'url'   => base_url('/rbac/assignments'),
            'match' => ['#^/rbac/assignments#'],
            'icon'  => 'fa-solid fa-diagram-project',
            'order' => 5,
            'perm'  => 'rbac.manage',
        ],
    ],
];
