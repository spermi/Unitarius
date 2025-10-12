<?php
declare(strict_types=1);

return [
    'name'    => 'users',
    'label'   => 'Felhasználók',
    'icon'    => 'fa-solid fa-user-gear',
    'prefix'  => '/users',
    'order'   => 20,
    'match'   => ['#^/users#'],
    'perm'    => 'users.manage',
    'children'=> [
        [
            'label' => 'Összes Felhasználó',
            'url'   => base_url('/users'),
            'match' => ['#^/users$#', '#^/users/#'],
            'icon'  => 'fa-regular fa-address-book',
            'order' => 1,
            'perm'  => 'users.view',
        ],
    ],
];
