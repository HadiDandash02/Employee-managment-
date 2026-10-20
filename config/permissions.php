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
        'employees.view' => 'Open Manage Employees',
        // Which employees: all of them, or only the user's own department (EmployeePolicy).
        'employees.view-all' => 'See all employees',
        'employees.view-team' => 'See employees of own department',
        'employees.create' => 'Add employees',
        'employees.update' => 'Edit employees',
        'employees.delete' => 'Delete employees',
        'users.manage' => 'Manage users',
        'departments.manage' => 'Manage departments',
        'profile.view-own' => 'See own employee profile',
        'profile.update-own' => 'Edit own contact info (phone, address)',
        // Leave requests (LeaveRequestPolicy): a Manager approves only their own department.
        'leave.request' => 'Request leave (for themselves)',
        'leave.approve' => 'Approve or reject leave requests',
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
            'permissions' => ['employees.view', 'employees.view-all', 'employees.view-team', 'employees.create', 'employees.update', 'employees.delete', 'users.manage', 'departments.manage', 'profile.view-own', 'profile.update-own', 'leave.request', 'leave.approve'],
            'manages' => ['hr', 'manager', 'employee'],
            'panel' => ['label' => 'HR', 'icon' => 'bi-briefcase', 'slug' => 'hr'],
        ],
        'manager' => [
            'label' => 'Manager',
            'description' => 'Can view the employees of their own department and approve their leave.',
            'permissions' => ['employees.view', 'employees.view-team', 'profile.view-own', 'profile.update-own', 'leave.request', 'leave.approve'],
            'manages' => [],
        ],
        // A login for one employee (users.employee_id): sees only their own profile.
        'employee' => [
            'label' => 'Employee',
            'description' => 'Can see their own profile and request leave.',
            'permissions' => ['profile.view-own', 'profile.update-own', 'leave.request'],
            'manages' => [],
        ],
    ],

];
