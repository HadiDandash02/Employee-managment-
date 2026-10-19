<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
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

        // Through the hasMany relationship; soft-deleted employees are not counted.
        $departments = Department::with('branch')
            ->whereHas('employees')
            ->withCount('employees')
            ->withAvg('employees', 'salary')
            ->orderByDesc('employees_count')
            ->orderBy('name')
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
            'recentEmployees' => Employee::with('department')->latest()->orderByDesc('id')->limit(5)->get(),
            'users' => $users,
            'roles' => array_intersect_key(config('permissions.roles'), array_flip($visibleRoles)),
            'permissions' => config('permissions.permissions'),
        ]);
    }
}
