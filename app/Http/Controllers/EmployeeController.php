<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $employees = Employee::search($search)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('employees.index', compact('employees', 'search'));
    }

    public function create()
    {
        return view('employees.create', ['employee' => new Employee]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $employee = Employee::create($request->validated());

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->full_name} created successfully.");
    }

    public function show(Employee $employee)
    {
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $employee->update($request->validated());

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->full_name} updated successfully.");
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->full_name} deleted successfully.");
    }
}
