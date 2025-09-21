<?php
declare(strict_types=1);
return [
  'name'   => 'users',
  'label'  => 'Felhasználók',
  'icon'   => 'fa-solid fa-user-gear',
  'prefix' => '/users',
  'order'  => 20,
  'hidden' => false, // do not show in menu
  'match'  => ['#^/users#'],
  'perm'   => null,
  'children'=> [
    ['label'=>'Felhasználók','url'=>base_url('/users'),'match'=>['#^/users$#'],'icon'=>'fa-regular fa-address-book','perm'=>null],
    ['label'=>'RBAC','url'=>base_url('/users/rbac'),'match'=>['#^/users/rbac#'],'icon'=>'fa-solid fa-shield-halved','perm'=>null],
  ],
];
