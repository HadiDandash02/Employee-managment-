<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

use App\Services\Employee\DashboardService;
use Illuminate\Http\Request;

/**
 * The Employee Dashboard: start page of the Employee role.
 *
 * Access rules are NOT checked here; the route does that with middleware:
 *   auth                  → must be logged in (guests go to /login)
 *   can:profile.view-own  → must have the permission (403 otherwise)
 *   active                → must not be suspended (CheckEmployeeStatus, 403 otherwise)
 */
class DashboardController extends Controller
{
    /**
     * Constructor injection: Laravel's service container creates the service and passes it in,
     * so the controller never does `new DashboardService()` itself.
     */
    public function __construct(
        private DashboardService $dashboard,
    ) {
    }

    /**
     * Method injection: Laravel passes the current Request in.
     */
    public function __invoke(Request $request)
    {
        $employee = $request->user()->employee;

        return view('employee.dashboard', [
            'user' => $request->user(),
            'employee' => $employee,
            'summary' => $employee ? $this->dashboard->summaryFor($employee) : null,
        ]);
    }
}
