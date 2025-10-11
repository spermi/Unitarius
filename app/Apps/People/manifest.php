<?php
declare(strict_types=1);

return [
    'name'    => 'people',                 // unique key
    'label'   => 'Személyek',              // HU display name
    'icon'    => 'fa-solid fa-users',      // top-level icon
    'prefix'  => '/people',                // mount prefix
    'order'   => 10,                       // sort order (low first)
    'match'   => ['#^/people#'],           // active regex for parent
    'perm'    => null,                     // parent toggler only
    'children'=> [
        [
            'label' => 'Személyek listája', // submenu: list
            'url'   => base_url('/people'),
            'match' => ['#^/people$#', '#^/people/#'],
            'icon'  => 'fa-solid fa-list',
            'order' => 1,
            'perm'  => 'people.view',
        ],
        [
            'label' => 'Új személy',        // submenu: create new
            'url'   => base_url('/people/new'),
            'match' => ['#^/people/new#'],
            'icon'  => 'fa-regular fa-plus',
            'order' => 2,
            'perm'  => 'people.create',
        ],
    ],
];
