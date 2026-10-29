<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreEmployeeRequest;
use App\Http\Requests\Staff\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * REST API for employees: /api/employees (Route::apiResource in routes/api.php).
 *
 * Same rules as the web pages: the same Form Requests validate (a failure is a 422 JSON answer,
 * not a redirect) and the same EmployeePolicy decides who may do what (403 JSON).
 * Only the employee's own fields are saved here; profile and skills stay on the web form.
 */
class EmployeeController extends Controller implements HasMiddleware
{
    /**
     * The policy checks run as route middleware, BEFORE the Form Request validates,
     * so a user who may not create employees gets 403, not a list of validation errors.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('can:viewAny,App\Models\Employee', only: ['index']),
            new Middleware('can:view,employee', only: ['show']),
            new Middleware('can:create,App\Models\Employee', only: ['store']),
            new Middleware('can:update,employee', only: ['update']),
            new Middleware('can:delete,employee', only: ['destroy']),
        ];
    }

    /**
     * GET /api/employees: 10 per page, with "links" and "meta" for the other pages (?page=2).
     */
    public function index(Request $request)
    {
        // visibleTo(): HR/Admin get everyone, a Manager only their own department.
        $employees = Employee::with('department')
            ->visibleTo($request->user())
            ->orderBy('id')
            ->paginate(10);

        return EmployeeResource::collection($employees);
    }

    /**
     * GET /api/employees/{id}
     */
    public function show(Employee $employee)
    {
        return new EmployeeResource($employee->load('department'));
    }

    /**
     * POST /api/employees: 201 Created with the new employee.
     */
    public function store(StoreEmployeeRequest $request)
    {
        $employee = Employee::create($request->safe()->only((new Employee)->getFillable()));

        // A resource of a model that was just created answers 201 instead of 200 by itself.
        return new EmployeeResource($employee->load('department'));
    }

    /**
     * PUT/PATCH /api/employees/{id}: only the fields that are sent change.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $employee->update($request->safe()->only($employee->getFillable()));

        return new EmployeeResource($employee->load('department'));
    }

    /**
     * DELETE /api/employees/{id}: soft delete (to the trash, like on the web), 204 No Content.
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return response()->noContent();
    }
}
