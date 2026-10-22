<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\NotificationController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Employee\ProfileController as EmployeeProfileController;
use App\Http\Controllers\Leave\LeaveRequestController;
use App\Http\Controllers\Staff\EmployeeController;
use App\Http\Middleware\SetPanelPrefix;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Every logged-in page uses these:
//   auth           → must be logged in (else redirect to /login)
//   auth.session   → changing your password signs that account out on other browsers too
//   SetPanelPrefix → the URL starts with the user's role: /admin, /hr, /manager or /employee;
//                    another role's prefix is a 404. route('…') fills in the user's own prefix.
$loggedIn = ['auth', 'auth.session', SetPanelPrefix::class];

// admin|hr|manager|employee (config/permissions.php "prefix")
$prefixes = collect(config('permissions.roles'))->pluck('prefix')->filter()->implode('|');

// Guest-only routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// Without a prefix: the start page and logging out
Route::middleware($loggedIn)->group(function () {
    // Start page: the dashboard (Admin, HR, Employee) or the employee list (Manager), see User::homeUrl().
    Route::get('/', fn (Request $request) => redirect($request->user()->homeUrl()))->name('home');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Old addresses without the role prefix (bookmarks): /employees/5 → /hr/employees/5 (301).
    Route::get('/{path}', fn (Request $request, string $path) => redirect(
        url($request->user()->url_prefix.'/'.$path).($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301,
    ))->where('path', '(employees|account|notifications|my-profile|leave-requests)(/[A-Za-z0-9/-]*)?');
});

// Everything else lives under the role: /admin/..., /hr/..., /manager/..., /employee/...
Route::middleware($loggedIn)->prefix('{panel}')->where(['panel' => $prefixes])->group(function () {

    // Authenticated users-only routes (every role)
    // /hr → /hr/dashboard (or the start page of the role)
    Route::get('/', fn (Request $request) => redirect($request->user()->homeUrl()))->name('panel.home');
    // One address for every role; Account\DashboardController shows the management dashboard
    // (Admin, HR) or the personal one (Manager, Employee).
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // My account (change password)
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');

    // Notifications (the bell in the top bar)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/notifications/{notification}', [NotificationController::class, 'open'])->name('notifications.open');

    // My profile (self-service): phone and address only (EmployeePolicy::updateContact, checked in the controller)
    // "active" is the CheckEmployeeStatus middleware: a suspended account cannot open these pages.
    Route::middleware(['can:profile.view-own', 'active'])->group(function () {
        Route::get('/my-profile', [EmployeeProfileController::class, 'show'])->name('profile.show');
        Route::get('/my-profile/edit', [EmployeeProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/my-profile', [EmployeeProfileController::class, 'update'])->name('profile.update');
    });

    // Leave Requests (every role, LeaveRequestPolicy)
    // Employee: ask for leave and follow it. Manager: approve/reject their department. HR: everyone.
    // The business rules (not your own, only pending, …) are in App\Services\Leave\LeaveRequestService.
    Route::middleware('active')->group(function () {
        Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->can('viewAny', LeaveRequest::class)->name('leave-requests.index');
        Route::get('/leave-requests/create', [LeaveRequestController::class, 'create'])->can('create', LeaveRequest::class)->name('leave-requests.create');
        Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->can('create', LeaveRequest::class)->name('leave-requests.store');
        Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show'])->can('view', 'leaveRequest')->name('leave-requests.show');
        Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->can('approve', 'leaveRequest')->name('leave-requests.approve');
        Route::post('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->can('approve', 'leaveRequest')->name('leave-requests.reject');
        Route::post('/leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->can('cancel', 'leaveRequest')->name('leave-requests.cancel');
    });

    // Staff Routes: Manage Employees (Admin, HR, Manager)
    // Checked by EmployeePolicy (app/Policies). ->can('update', 'employee') runs EmployeePolicy::update()
    // for the employee in the URL, so typing /hr/employees/25/edit by hand is 403 when that user may not edit 25.

    // List + DataTables JSON (which rows: Employee::scopeVisibleTo())
    Route::get('/employees', [EmployeeController::class, 'index'])->can('viewAny', Employee::class)->name('employees.index');
    Route::get('/employees/data', [EmployeeController::class, 'data'])->can('viewAny', Employee::class)->name('employees.data');

    // Create
    Route::get('/employees/create', [EmployeeController::class, 'create'])->can('create', Employee::class)->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->can('create', Employee::class)->name('employees.store');

    // Bulk delete + trash (many ids at once, so they check the permission itself).
    // Before /employees/{employee}, so "trash" is not read as an employee id.
    Route::middleware('can:employees.delete')->group(function () {
        Route::delete('/employees', [EmployeeController::class, 'bulkDestroy'])->name('employees.bulk-destroy');
        Route::post('/employees/trash/restore', [EmployeeController::class, 'bulkRestore'])->name('employees.trash.restore');
        Route::delete('/employees/trash/all', [EmployeeController::class, 'emptyTrash'])->name('employees.trash.empty');
        Route::delete('/employees/trash', [EmployeeController::class, 'bulkForceDelete'])->name('employees.trash.force');
    });

    // One employee
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->can('update', 'employee')->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->can('update', 'employee')->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->can('delete', 'employee')->name('employees.destroy');
    // Must open the list at all AND may see this employee (Employees use /employee/my-profile instead).
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])
        ->middleware(['can:viewAny,'.Employee::class, 'can:view,employee'])
        ->name('employees.show');

    // Trash: withTrashed() lets route model binding find soft-deleted employees.
    Route::post('/employees/{employee}/restore', [EmployeeController::class, 'restore'])->withTrashed()->can('restore', 'employee')->name('employees.restore');
    Route::delete('/employees/{employee}/force', [EmployeeController::class, 'forceDelete'])->withTrashed()->can('forceDelete', 'employee')->name('employees.force-delete');

    // Admin / HR Routes: Manage Users (/admin/users, /hr/users)
    Route::middleware('can:users.manage')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/data', [UserController::class, 'data'])->name('users.data');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Admin / HR Routes: Manage Departments (/admin/departments, /hr/departments)
    Route::middleware('can:departments.manage')->name('admin.')->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::match(['put', 'patch'], '/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
    });
});
