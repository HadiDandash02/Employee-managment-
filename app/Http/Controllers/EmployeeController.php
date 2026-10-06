<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\Request;
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
        return view('employees.index', [
            'search' => trim($request->string('search')),
            'pageLengths' => self::PAGE_LENGTHS,
            'departments' => $this->departments(),
        ]);
    }

    /**
     * JSON for DataTables server-side processing: search, sort and one page of rows.
     *
     * @see https://datatables.net/manual/server-side
     */
    public function data(Request $request)
    {
        $search = trim((string) data_get($request->query('search'), 'value', ''));

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

        $filters = is_array($request->query('filters')) ? $request->query('filters') : [];
        $query = Employee::search($search)->filter($filters)->sortBy($sort, $direction);
        $filtered = (clone $query)->count();

        if ($length !== -1) {
            $query->skip($start)->take($length);
        }

        return response()->json([
            'draw' => $request->integer('draw'),
            'recordsTotal' => Employee::count(),
            'recordsFiltered' => $filtered,
            'data' => $query->get()->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'initials' => $employee->initials,
                'avatar_color' => $employee->avatar_color,
                'email' => $employee->email,
                'department' => $employee->department,
                'department_color' => $employee->department_color,
                'salary' => (float) $employee->salary,
                'salary_formatted' => number_format($employee->salary, 2),
                'date_of_birth' => $employee->date_of_birth->format('Y-m-d'),
                'date_of_birth_formatted' => $employee->date_of_birth->format('d M Y'),
                'age' => $employee->age,
                'added_on' => $employee->created_at?->format('Y-m-d H:i:s'),
                'urls' => [
                    'show' => route('employees.show', $employee),
                    'edit' => route('employees.edit', $employee),
                    'destroy' => route('employees.destroy', $employee),
                ],
            ]),
        ]);
    }

    public function create()
    {
        return view('employees.create', [
            'employee' => new Employee,
            'departments' => $this->departments(),
        ]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $employee = Employee::create($request->validated());

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->full_name} created successfully.");
    }

    public function show(Employee $employee)
    {
        $sameDepartment = Employee::where('department', $employee->department);

        $departmentCount = (clone $sameDepartment)->count();
        $departmentAverage = (float) (clone $sameDepartment)->avg('salary');
        $colleagues = (clone $sameDepartment)
            ->whereKeyNot($employee->getKey())
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(5)
            ->get();

        return view('employees.show', compact('employee', 'departmentCount', 'departmentAverage', 'colleagues'));
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', [
            'employee' => $employee,
            'departments' => $this->departments(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $employee->update($request->validated());

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->full_name} updated successfully.");
    }

    public function destroy(Request $request, Employee $employee)
    {
        $employee->delete();

        $message = "Employee {$employee->full_name} deleted successfully.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('employees.index')->with('success', $message);
    }

    /**
     * Delete several employees at once (the checkboxes in the list).
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'max:1000'],
            'ids.*' => ['integer'],
        ])['ids'];

        $count = Employee::whereKey($ids)->delete();
        $message = $count.' '.Str::plural('employee', $count).' deleted successfully.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'deleted' => $count]);
        }

        return redirect()->route('employees.index')->with('success', $message);
    }

    /**
     * Existing department names, offered as suggestions in the form.
     */
    private function departments()
    {
        return Employee::query()->distinct()->orderBy('department')->pluck('department');
    }
}
