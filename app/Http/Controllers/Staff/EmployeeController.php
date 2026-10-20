<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;

use App\Http\Requests\Staff\StoreEmployeeRequest;
use App\Http\Requests\Staff\UpdateEmployeeRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /**
     * Allowed "rows per page" values; -1 means all rows (used for exporting everything).
     */
    private const PAGE_LENGTHS = [10, 25, 50, 100, -1];

    /**
     * The page itself; the rows are loaded by DataTables from data().
     */
    public function index(Request $request)
    {
        return view('staff.employees.index', [
            'search' => trim($request->string('search')),
            'pageLengths' => self::PAGE_LENGTHS,
            'departments' => $this->departments(),
            'branches' => Branch::with('company')->get()->sortBy(fn (Branch $branch) => [$branch->company?->name, $branch->name])->values(),
            'trashCount' => Employee::onlyTrashed()->count(),
        ]);
    }

    /**
     * JSON for DataTables server-side processing: search, sort and one page of rows.
     * status=trash lists the soft-deleted employees instead.
     *
     * @see https://datatables.net/manual/server-side
     */
    public function data(Request $request)
    {
        $search = trim((string) data_get($request->query('search'), 'value', ''));
        $trash = $request->query('status') === 'trash';

        $sort = null;
        $direction = 'asc';
        $orderColumn = data_get($request->query('order'), '0.column');
        if (is_numeric($orderColumn)) {
            $column = data_get($request->query('columns'), (int) $orderColumn);
            if (data_get($column, 'orderable') === 'true' && isset(Employee::SORTABLE[data_get($column, 'name')])) {
                $sort = data_get($column, 'name');
                $direction = data_get($request->query('order'), '0.dir') === 'desc' ? 'desc' : 'asc';
            }
        }

        $length = in_array($request->integer('length'), self::PAGE_LENGTHS, true)
            ? $request->integer('length')
            : self::PAGE_LENGTHS[0];
        $start = max(0, $request->integer('start'));

        $base = $trash ? Employee::onlyTrashed() : Employee::query();
        $filters = is_array($request->query('filters')) ? $request->query('filters') : [];
        $query = (clone $base)->with(['department.branch', 'profile', 'user'])->search($search)->filter($filters)->sortBy($sort, $direction);
        $filtered = (clone $query)->count();

        if ($length !== -1) {
            $query->skip($start)->take($length);
        }

        return response()->json([
            'draw' => $request->integer('draw'),
            'recordsTotal' => $base->count(),
            'recordsFiltered' => $filtered,
            'trashTotal' => Employee::onlyTrashed()->count(),
            'data' => $query->get()->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'initials' => $employee->initials,
                'avatar_color' => $employee->avatar_color,
                'email' => $employee->email,
                'department' => $employee->department?->name,
                'branch' => $employee->department?->branch?->name,
                'department_color' => $employee->department_color,
                'salary' => (float) $employee->salary,
                'salary_formatted' => number_format($employee->salary, 2),
                'date_of_birth' => $employee->date_of_birth->format('Y-m-d'),
                'date_of_birth_formatted' => $employee->date_of_birth->format('d M Y'),
                'age' => $employee->age,
                'hire_date' => $employee->profile?->hire_date?->format('Y-m-d'),
                'added_on' => $employee->created_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $employee->deleted_at?->format('Y-m-d H:i'),
                // The login linked to this employee (users.employee_id), if any.
                'login' => $employee->user ? [
                    'username' => $employee->user->username,
                    'is_active' => $employee->user->is_active,
                    'edit_url' => $request->user()->can('manage-user', $employee->user) ? route('admin.users.edit', $employee->user) : null,
                ] : null,
                'urls' => $employee->trashed()
                    ? [
                        'restore' => route('employees.restore', $employee),
                        'force' => route('employees.force-delete', $employee),
                    ]
                    : [
                        'show' => route('employees.show', $employee),
                        'edit' => route('employees.edit', $employee),
                        'destroy' => route('employees.destroy', $employee),
                    ],
            ]),
        ]);
    }

    public function create()
    {
        return view('staff.employees.create', [
            'employee' => new Employee,
            'departments' => $this->departments(),
            'skills' => Skill::orderBy('name')->get(),
        ]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $employee = DB::transaction(function () use ($request) {
            $employee = Employee::create($request->safe()->only((new Employee)->getFillable()));
            $this->saveDetails($employee, $request);

            return $employee;
        });

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->full_name} created successfully.");
    }

    public function show(Employee $employee)
    {
        $employee->load(['department.branch.company', 'profile', 'skills', 'user']);

        $sameDepartment = Employee::where('department_id', $employee->department_id);

        $departmentCount = (clone $sameDepartment)->count();
        $departmentAverage = (float) (clone $sameDepartment)->avg('salary');
        $colleagues = (clone $sameDepartment)
            ->whereKeyNot($employee->getKey())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(5)
            ->get();

        return view('staff.employees.show', compact('employee', 'departmentCount', 'departmentAverage', 'colleagues'));
    }

    public function edit(Employee $employee)
    {
        return view('staff.employees.edit', [
            'employee' => $employee->load(['profile', 'skills']),
            'departments' => $this->departments(),
            'skills' => Skill::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        DB::transaction(function () use ($request, $employee) {
            $employee->update($request->safe()->only($employee->getFillable()));
            $this->saveDetails($employee, $request);
        });

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->full_name} updated successfully.");
    }

    /**
     * Soft delete: the employee goes to the trash and can be restored.
     */
    public function destroy(Request $request, Employee $employee)
    {
        $employee->delete();

        return $this->respond($request, "Employee {$employee->full_name} moved to the trash.");
    }

    /**
     * Move several employees to the trash at once (the checkboxes in the list).
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'max:1000'],
            'ids.*' => ['integer'],
        ])['ids'];

        $count = Employee::whereKey($ids)->delete();
        $message = $count.' '.Str::plural('employee', $count).' moved to the trash.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'deleted' => $count]);
        }

        return redirect()->route('employees.index')->with('success', $message);
    }

    /**
     * Restore several trashed employees at once (checkboxes in the Trash view).
     */
    public function bulkRestore(Request $request)
    {
        $count = Employee::onlyTrashed()->whereKey($this->validatedIds($request))->restore();

        return $this->respond($request, $count.' '.Str::plural('employee', $count).' restored.');
    }

    /**
     * Permanently delete several trashed employees (checkboxes in the Trash view).
     * Only rows that are really in the trash are touched.
     */
    public function bulkForceDelete(Request $request)
    {
        $count = $this->forceDeleteTrashed(Employee::onlyTrashed()->whereKey($this->validatedIds($request)));

        return $this->respond($request, $count.' '.Str::plural('employee', $count).' permanently deleted.');
    }

    /**
     * "Empty trash": permanently delete every trashed employee, on every page.
     */
    public function emptyTrash(Request $request)
    {
        $count = $this->forceDeleteTrashed(Employee::onlyTrashed());

        return $this->respond($request, $count ? "Trash emptied: {$count} ".Str::plural('employee', $count).' permanently deleted.' : 'The trash is already empty.');
    }

    /**
     * Bring a soft-deleted employee back from the trash.
     */
    public function restore(Request $request, Employee $employee)
    {
        abort_unless($employee->trashed(), 404);

        $employee->restore();

        return $this->respond($request, "Employee {$employee->full_name} restored.");
    }

    /**
     * Delete for good. Only allowed from the trash; the profile and skill links are removed by the
     * database (cascadeOnDelete on employee_profiles and employee_skill).
     */
    public function forceDelete(Request $request, Employee $employee)
    {
        abort_unless($employee->trashed(), 404);

        $employee->forceDelete();

        return $this->respond($request, "Employee {$employee->full_name} permanently deleted.");
    }

    /**
     * Save the hasOne profile and sync the belongsToMany skills from the request.
     */
    private function saveDetails(Employee $employee, StoreEmployeeRequest|UpdateEmployeeRequest $request): void
    {
        $profile = $request->safe()->only(['phone', 'address', 'hire_date']);
        if ($profile !== []) {
            $employee->profile()->updateOrCreate([], $profile);
        }

        // "skills" missing (partial update) = keep them; empty = remove all; a list = exactly those.
        if ($request->has('skills')) {
            $employee->skills()->sync($request->validated('skills') ?? []);
        }
    }

    /**
     * @return list<int>
     */
    private function validatedIds(Request $request): array
    {
        return $request->validate([
            'ids' => ['required', 'array', 'max:1000'],
            'ids.*' => ['integer'],
        ])['ids'];
    }

    /**
     * forceDelete() model by model (in a transaction), so the database cascades remove each
     * employee's profile and skill links, and model events still fire.
     */
    private function forceDeleteTrashed($query): int
    {
        return DB::transaction(function () use ($query) {
            $employees = $query->get();
            $employees->each->forceDelete();

            return $employees->count();
        });
    }

    private function respond(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('employees.index')->with('success', $message);
    }

    /**
     * All departments with their branch and company, for the dropdowns.
     */
    private function departments()
    {
        return Department::with('branch.company')->get()
            ->sortBy(fn (Department $department) => [$department->location, $department->name])
            ->values();
    }
}
