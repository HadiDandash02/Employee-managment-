<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDashboardController;
use App\Http\Controllers\MyProfileController;
use App\Http\Middleware\SetPanelPrefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

// auth.session: changing your password signs that account out on other browsers too.
Route::middleware(['auth', 'auth.session', SetPanelPrefix::class])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // Start page: the dashboard for Admin/HR, the employee list for everyone else.
    Route::get('/', fn (Request $request) => redirect($request->user()->homeUrl()))->name('home');

    // Every user, whatever their role, can change their own password.
    Route::get('account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('account/password', [AccountController::class, 'updatePassword'])->name('account.password');

    // Employee pages: only users with the permission AND an active (not suspended) account.
    // "active" is the CheckEmployeeStatus middleware (alias registered in bootstrap/app.php).
    Route::middleware(['can:profile.view-own', 'active'])->group(function () {
        Route::get('employee/dashboard', EmployeeDashboardController::class)->name('employee.dashboard');
        Route::get('my-profile', MyProfileController::class)->name('profile.show');
    });

    // Employees: every action needs its own permission (config/permissions.php).
    Route::middleware('can:employees.view')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('employees/data', [EmployeeController::class, 'data'])->name('employees.data');
    });
    Route::middleware('can:employees.create')->group(function () {
        Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
    });
    Route::middleware('can:employees.delete')->group(function () {
        Route::delete('employees', [EmployeeController::class, 'bulkDestroy'])->name('employees.bulk-destroy');
        // Trash bulk actions (before employees/{employee}, so "trash" is not read as an employee id).
        Route::post('employees/trash/restore', [EmployeeController::class, 'bulkRestore'])->name('employees.trash.restore');
        Route::delete('employees/trash/all', [EmployeeController::class, 'emptyTrash'])->name('employees.trash.empty');
        Route::delete('employees/trash', [EmployeeController::class, 'bulkForceDelete'])->name('employees.trash.force');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        // Trash: withTrashed() lets route model binding find soft-deleted employees.
        Route::post('employees/{employee}/restore', [EmployeeController::class, 'restore'])->withTrashed()->name('employees.restore');
        Route::delete('employees/{employee}/force', [EmployeeController::class, 'forceDelete'])->withTrashed()->name('employees.force-delete');
    });
    Route::middleware('can:employees.update')->group(function () {
        Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    });
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])
        ->middleware('can:employees.view')
        ->name('employees.show');

    // Management area: /admin for admins, /hr for HR (see SetPanelPrefix). Other prefixes are 404.
    $panelSlugs = collect(config('permissions.roles'))->pluck('panel.slug')->filter()->implode('|');

    Route::middleware('can:users.manage')
        ->prefix('{panel}')
        ->where(['panel' => $panelSlugs])
        ->name('admin.')
        ->group(function () {
            // /admin → /admin/dashboard (HR: /hr → /hr/dashboard)
            Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('home');
            Route::get('dashboard', DashboardController::class)->name('dashboard');
            Route::get('users/data', [UserController::class, 'data'])->name('users.data');
            Route::resource('users', UserController::class)->except(['show']);
        });

    // Manage Departments (Admin/HR): /admin/departments or /hr/departments.
    Route::middleware('can:departments.manage')
        ->prefix('{panel}')
        ->where(['panel' => $panelSlugs])
        ->name('admin.')
        ->group(function () {
            Route::resource('departments', DepartmentController::class)->except(['show']);
        });
});
