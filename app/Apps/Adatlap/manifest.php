<?php

declare(strict_types=1);

return [

    'name'    => 'adatlap',
    'label'   => 'Lelkészi adatlap',
    'icon'    => 'fa-solid fa-user-pen',
    'prefix'  => '/adatlap',
    'order'   => 40,
    'match'   => ['#^/adatlap#'],
    'perm'    => 'adatlap.lelkesz', // csak lelkészek láthatják
    'children'=> [
        [
            'label' => 'Saját adatlap',
            'url'   => base_url('/adatlap'),
            'match' => ['#^/adatlap$#'],
            'icon'  => 'fa-regular fa-address-card',
            'order' => 1,
        ],
        [
            'label' => 'Család és kapcsolatok',
            'url'   => base_url('/adatlap/family'),
            'match' => ['#^/adatlap/family#'],
            'icon'  => 'fa-solid fa-people-group',
            'order' => 2,
        ],
        [
            'label' => 'Szolgálati helyek',
            'url'   => base_url('/adatlap/service'),
            'match' => ['#^/adatlap/service#'],
            'icon'  => 'fa-solid fa-church',
            'order' => 3,
        ],
        [
            'label' => 'Tanulmányok',
            'url'   => base_url('/adatlap/studies'),
            'match' => ['#^/adatlap/studies#'],
            'icon'  => 'fa-solid fa-graduation-cap',
            'order' => 4,
        ],
    ],
];
