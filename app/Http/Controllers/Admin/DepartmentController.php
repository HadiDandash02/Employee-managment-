<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\Request;

/**
 * Manage Departments (Admin/HR): list, add, edit and (soft) delete departments.
 */
class DepartmentController extends Controller
{
    /**
     * Allowed "rows per page" values for the departments table.
     */
    private const PAGE_LENGTHS = [10, 25, 50, 100];

    /**
     * Manage Departments page; the rows are loaded by DataTables from data().
     */
    public function index()
    {
        return view('admin.departments.index', [
            'pageLengths' => self::PAGE_LENGTHS,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'branches' => $this->branches(),
        ]);
    }

    /**
     * JSON for the departments DataTable (server-side): search every column, filter, sort and page.
     *
     * @see https://datatables.net/manual/server-side
     */
    public function data(Request $request)
    {
        $filters = is_array($request->query('filters')) ? $request->query('filters') : [];
        $search = trim((string) data_get($request->query('search'), 'value', ''));
        $int = fn (string $key) => ctype_digit((string) ($filters[$key] ?? '')) ? (int) $filters[$key] : null;

        // N+1 fix: branch + company in 2 extra queries for the whole page, the employee count as a sub-query.
        $query = Department::query()
            ->with('branch.company')
            ->withCount('employees')
            ->search($search)
            ->when($int('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
            ->when($int('company_id'), fn ($q, $id) => $q->whereHas('branch', fn ($b) => $b->where('company_id', $id)))
            ->when(($filters['staffed'] ?? '') === 'yes', fn ($q) => $q->has('employees'))
            ->when(($filters['staffed'] ?? '') === 'no', fn ($q) => $q->doesntHave('employees'));

        // Sort by a whitelisted column only (DataTables sends the column name and the direction).
        $column = data_get($request->query('columns'), (int) data_get($request->query('order'), '0.column', -1));
        $direction = data_get($request->query('order'), '0.dir') === 'desc' ? 'desc' : 'asc';
        match (data_get($column, 'orderable') === 'true' ? data_get($column, 'name') : null) {
            'name' => $query->orderBy('name', $direction),
            'code' => $query->orderBy('code', $direction),
            'employees_count' => $query->orderBy('employees_count', $direction),
            'created_at' => $query->orderBy('created_at', $direction),
            'branch' => $query->orderBy(Branch::select('name')->whereColumn('branches.id', 'departments.branch_id'), $direction),
            default => $query->orderBy('name'),
        };
        $query->orderBy('id');

        $length = in_array($request->integer('length'), self::PAGE_LENGTHS, true) ? $request->integer('length') : self::PAGE_LENGTHS[0];
        $filtered = (clone $query)->count();

        return response()->json([
            'draw' => $request->integer('draw'),
            'recordsTotal' => Department::count(),
            'recordsFiltered' => $filtered,
            'data' => $query->skip(max(0, $request->integer('start')))->take($length)->get()->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
                'description' => $department->description,
                'code' => $department->code,
                'branch' => $department->branch?->name,
                'city' => $department->branch?->city,
                'company' => $department->branch?->company?->name,
                'employees_count' => $department->employees_count,
                'created_at' => $department->created_at?->format('d/m/Y'),
                'urls' => [
                    'edit' => route('admin.departments.edit', $department),
                    'destroy' => route('admin.departments.destroy', $department),
                    'employees' => route('employees.index', ['filters' => ['department_id' => $department->id]]),
                ],
            ]),
        ]);
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
            $message = "{$department->name} still has {$count} ".str('employee')->plural($count).'. Move them to another department first.';

            // The table deletes with fetch() and shows the JSON message; a normal form goes back with the error.
            return request()->expectsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['department' => $message]);
        }

        $department->delete();
        $message = "Department {$department->name} deleted.";

        return request()->expectsJson()
            ? response()->json(['message' => $message])
            : redirect()->route('admin.departments.index')->with('success', $message);
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
