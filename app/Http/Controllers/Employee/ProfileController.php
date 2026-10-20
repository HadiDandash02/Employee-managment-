<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

/**
 * "My Profile": the logged-in user's own employee record (users.employee_id).
 * This is the start page of the Employee role, which cannot see any other employee.
 */
class ProfileController extends Controller
{
    public function __invoke(Request $request)
    {
        $employee = $request->user()->employee?->load(['department.branch.company', 'profile', 'skills']);

        return view('employee.profile', ['employee' => $employee]);
    }
}
