<?php
declare(strict_types=1);

return [
    'name'    => 'rbac',
    'label'   => 'RBAC jogosultságkezelés',
    'icon'    => 'fa-solid fa-shield-halved',
    'prefix'  => '/rbac',
    'order'   => 30,
    'match'   => ['#^/rbac#'],
    'perm'    => 'rbac.manage',
    'children'=> [
        [
            'label' => 'Áttekintés',
            'url'   => base_url('/rbac'),
            'match' => ['#^/rbac$#'],
            'icon'  => 'fa-solid fa-gauge',
            'order' => 1,
        ],
        [
            'label' => 'Szerepek',
            'url'   => base_url('/rbac/roles'),
            'match' => ['#^/rbac/roles#'],
            'icon'  => 'fa-regular fa-id-badge',
            'order' => 2,
        ],
        [
            'label' => 'Jogosultságok',
            'url'   => base_url('/rbac/permissions'),
            'match' => ['#^/rbac/permissions#'],
            'icon'  => 'fa-regular fa-keyboard',
            'order' => 3,
        ],
        [
            'label' => 'Hozzárendelések',
            'url'   => base_url('/rbac/assignments'),
            'match' => ['#^/rbac/assignments#'],
            'icon'  => 'fa-solid fa-diagram-project',
            'order' => 4,
        ],
    ],
];
