<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Branch;
use App\Models\Department;
use Illuminate\Http\Request;

/**
 * Manage Departments (Admin/HR): list, add, edit and (soft) delete departments.
 */
class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->string('search'));

        // N+1 fix: the branch (and its company) are loaded in 2 extra queries for the whole page,
        // and the employee count is a sub-query; without with()/withCount() every row would
        // run its own queries for $department->branch and $department->employees->count().
        $departments = Department::with('branch.company')
            ->withCount('employees')
            ->search($search)
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.departments.index', compact('departments', 'search'));
    }

    public function create()
    {
        return view('admin.departments.create', [
            'department' => new Department,
            'branches' => $this->branches(),
        ]);
    }

    public function store(DepartmentRequest $request)
    {
        $department = Department::create($request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', "Department {$department->name} created successfully.");
    }

    public function edit(Department $department)
    {
        return view('admin.departments.edit', [
            'department' => $department,
            'branches' => $this->branches(),
        ]);
    }

    public function update(DepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', "Department {$department->name} updated successfully.");
    }

    /**
     * Soft delete. Refused while the department still has (active) employees, like the
     * database's restrictOnDelete rule would refuse a real delete.
     */
    public function destroy(Department $department)
    {
        $count = $department->employees()->count();

        if ($count > 0) {
            return back()->withErrors([
                'department' => "{$department->name} still has {$count} ".str('employee')->plural($count)
                    .'. Move them to another department first.',
            ]);
        }

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', "Department {$department->name} deleted.");
    }

    /**
     * Branches with their company, for the branch dropdown (grouped by company).
     */
    private function branches()
    {
        return Branch::with('company')->get()
            ->sortBy(fn (Branch $branch) => [$branch->company?->name, $branch->name])
            ->values();
    }
}
