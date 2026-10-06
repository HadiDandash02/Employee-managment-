<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        // Only the users/roles this person may manage are shown (HR never sees Admins).
        $visibleRoles = $request->user()->manageableRoles();
        $users = User::whereIn('role', $visibleRoles)->orderByRaw("role = 'admin' desc")->orderBy('name')->get();

        $departments = Employee::query()
            ->selectRaw('department, COUNT(*) as total, AVG(salary) as average_salary')
            ->groupBy('department')
            ->orderByDesc('total')
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'employees' => Employee::count(),
                'departments' => $departments->count(),
                'average_salary' => (float) Employee::avg('salary'),
                'payroll' => (float) Employee::sum('salary'),
                'users' => $users->count(),
            ],
            'departments' => $departments,
            'recentEmployees' => Employee::latest()->orderByDesc('id')->limit(5)->get(),
            'users' => $users,
            'roles' => array_intersect_key(config('permissions.roles'), array_flip($visibleRoles)),
            'permissions' => config('permissions.permissions'),
        ]);
    }
}
