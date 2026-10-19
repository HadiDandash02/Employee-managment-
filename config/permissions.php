<?php

/*
|--------------------------------------------------------------------------
| Roles and permissions
|--------------------------------------------------------------------------
|
| Every permission is registered as a Laravel Gate (see AppServiceProvider),
| so it can be checked with @can('employees.create'), $user->can(...)
| or the "can:employees.create" route middleware.
|
| "permissions": what the role may do ('*' = everything).
| "manages":     which roles' accounts it may see, create, edit and delete
|                on the admin page ('*' = all roles). Users with other roles
|                are hidden from it completely.
|
| "panel":       name, icon and URL (/admin, /hr) of the management area
|                for roles with users.manage: "Admin" for admins, "HR" for HR,
|                so HR users never see the word "Admin".
|
| Every logged-in user may change their own password (My account page).
|
*/

return [

    'permissions' => [
        'employees.view' => 'View employees',
        'employees.create' => 'Add employees',
        'employees.update' => 'Edit employees',
        'employees.delete' => 'Delete employees',
        'users.manage' => 'Manage users',
        'departments.manage' => 'Manage departments',
    ],

    'roles' => [
        'admin' => [
            'label' => 'Admin',
            'description' => 'Full access, including all users.',
            'permissions' => ['*'],
            'manages' => ['*'],
            'panel' => ['label' => 'Admin', 'icon' => 'bi-shield-lock', 'slug' => 'admin'],
        ],
        'hr' => [
            'label' => 'HR',
            'description' => 'Full access to employees and user management.',
            'permissions' => ['employees.view', 'employees.create', 'employees.update', 'employees.delete', 'users.manage', 'departments.manage'],
            'manages' => ['hr', 'manager', 'viewer'],
            'panel' => ['label' => 'HR', 'icon' => 'bi-briefcase', 'slug' => 'hr'],
        ],
        'manager' => [
            'label' => 'Manager',
            'description' => 'Can view, add and edit employees, but not delete them.',
            'permissions' => ['employees.view', 'employees.create', 'employees.update'],
            'manages' => [],
        ],
        'viewer' => [
            'label' => 'Viewer',
            'description' => 'Can only view employees.',
            'permissions' => ['employees.view'],
            'manages' => [],
        ],
    ],

];
