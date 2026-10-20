<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

use App\Http\Requests\Employee\UpdateOwnContactRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * "My Profile": the logged-in user's own employee record (users.employee_id).
 * This is the start page of the Employee role, which cannot see any other employee.
 * The employee may change their own phone and address only (EmployeePolicy::updateContact).
 */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $employee = $request->user()->employee?->load(['department.branch.company', 'profile', 'skills']);

        return view('employee.profile', ['employee' => $employee]);
    }

    public function edit(Request $request)
    {
        $employee = $request->user()->employee;
        abort_if($employee === null, 404);

        // 403 unless the policy says yes: own record AND the profile.update-own permission.
        Gate::authorize('updateContact', $employee);

        return view('employee.profile-edit', ['employee' => $employee->load('profile')]);
    }

    public function update(UpdateOwnContactRequest $request)
    {
        $employee = $request->user()->employee;
        abort_if($employee === null, 404);

        Gate::authorize('updateContact', $employee);

        // validated() holds only phone and address, so salary, department, … sent in the form are ignored.
        $employee->profile()->updateOrCreate([], $request->validated());

        return redirect()->route('profile.show')->with('success', 'Your contact info was updated.');
    }
}
