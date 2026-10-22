<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Middleware\CheckEmployeeStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * /{role}/dashboard: one address, the right dashboard for the role.
 * A route can only point to one controller, so this one hands over:
 *   Admin, HR         → the management dashboard (Admin\DashboardController)
 *   Manager, Employee → the personal dashboard  (Employee\DashboardController), with the same
 *                       checks as the other employee pages (profile.view-own + not suspended).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->user()->can('users.manage')) {
            return app(AdminDashboardController::class)($request);
        }

        Gate::authorize('profile.view-own');

        // Run the "active" middleware here too: a suspended account gets the same 403 page.
        // response(): middleware must return a Response, and the controller returns a View.
        return app(CheckEmployeeStatus::class)->handle($request, fn (Request $request) => response(app(EmployeeDashboardController::class)($request)));
    }
}
