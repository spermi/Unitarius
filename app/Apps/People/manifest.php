<?php
declare(strict_types=1);

/**
 * Per-app manifest (menu metadata).
 * Required: name, label, prefix.
 * Optional: icon, order, match, perm, children[] (label,url,match,icon?,perm?)
 */
return [
  'name'    => 'people',                // unique key
  'label'   => 'Személyek',             // HU display name
  'icon'    => 'fa-solid fa-users',     // top-level icon
  'prefix'  => '/people',               // mount prefix
  'order'   => 10,                      // sort order (low first)
  'match'   => ['#^/people#'],          // active regex for parent
  'perm'    => null,                    // RBAC later (optional)
  'children'=> [
    [
      'label'=>'Lista',
      'url'=>base_url('/people'),
      'match'=>['#^/people$#'],
      'icon'=>'fa-regular fa-list',
      'perm'=>null,
    ],
    [
      'label'=>'Új személy',
      'url'=>base_url('/people/new'),
      'match'=>['#^/people/new#'],
      'icon'=>'fa-regular fa-plus',
      'perm'=>null,
    ],
  ],
];
