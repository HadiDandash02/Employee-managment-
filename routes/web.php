<?php

// Controllers are grouped by area (see app/Http/Controllers/README.md):
//   Auth (logged out) · Account (every user) · Staff (Admin/HR/Manager) · Admin (Admin/HR) · Employee (Employee role)
use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;
use App\Http\Controllers\Staff\EmployeeController;
use App\Http\Middleware\SetPanelPrefix;
use App\Models\Employee;
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
        Route::get('my-profile', [EmployeeProfileController::class, 'show'])->name('profile.show');
        // Self-service: phone and address only (EmployeePolicy::updateContact, checked in the controller).
        Route::get('my-profile/edit', [EmployeeProfileController::class, 'edit'])->name('profile.edit');
        Route::put('my-profile', [EmployeeProfileController::class, 'update'])->name('profile.update');
    });

    // Employees: checked by EmployeePolicy (app/Policies). ->can('update', 'employee') runs
    // EmployeePolicy::update($user, $employee) for the employee in the URL, so typing
    // /employees/25/edit by hand is refused (403) when that user may not edit employee 25.
    Route::get('employees', [EmployeeController::class, 'index'])->can('viewAny', Employee::class)->name('employees.index');
    Route::get('employees/data', [EmployeeController::class, 'data'])->can('viewAny', Employee::class)->name('employees.data');
    Route::get('employees/create', [EmployeeController::class, 'create'])->can('create', Employee::class)->name('employees.create');
    Route::post('employees', [EmployeeController::class, 'store'])->can('create', Employee::class)->name('employees.store');

    // Bulk and trash actions work on many ids at once, so they check the permission itself.
    Route::middleware('can:employees.delete')->group(function () {
        Route::delete('employees', [EmployeeController::class, 'bulkDestroy'])->name('employees.bulk-destroy');
        // Trash bulk actions (before employees/{employee}, so "trash" is not read as an employee id).
        Route::post('employees/trash/restore', [EmployeeController::class, 'bulkRestore'])->name('employees.trash.restore');
        Route::delete('employees/trash/all', [EmployeeController::class, 'emptyTrash'])->name('employees.trash.empty');
        Route::delete('employees/trash', [EmployeeController::class, 'bulkForceDelete'])->name('employees.trash.force');
    });

    Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->can('delete', 'employee')->name('employees.destroy');
    // Trash: withTrashed() lets route model binding find soft-deleted employees.
    Route::post('employees/{employee}/restore', [EmployeeController::class, 'restore'])->withTrashed()->can('restore', 'employee')->name('employees.restore');
    Route::delete('employees/{employee}/force', [EmployeeController::class, 'forceDelete'])->withTrashed()->can('forceDelete', 'employee')->name('employees.force-delete');
    Route::get('employees/{employee}/edit', [EmployeeController::class, 'edit'])->can('update', 'employee')->name('employees.edit');
    Route::put('employees/{employee}', [EmployeeController::class, 'update'])->can('update', 'employee')->name('employees.update');
    // Staff page of one employee: the user must open the list at all AND may see this employee.
    // (Employees see their own record on /my-profile instead.)
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])
        ->middleware(['can:viewAny,'.Employee::class, 'can:view,employee'])
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
            Route::get('dashboard', AdminDashboardController::class)->name('dashboard');
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
