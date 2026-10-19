<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Allowed "rows per page" values for the users table.
     */
    private const PAGE_LENGTHS = [10, 25, 50, 100];

    /**
     * Columns the users table can be sorted by (DataTables column name => database column).
     */
    private const SORTABLE = ['name' => 'name', 'role' => 'role', 'created_at' => 'created_at'];

    /**
     * Manage Users page; the rows are loaded by DataTables from data().
     */
    public function index(Request $request)
    {
        return view('admin.users.index', [
            'roles' => $this->assignableRoles($request->user()),
            'pageLengths' => self::PAGE_LENGTHS,
        ]);
    }

    /**
     * JSON for the users DataTable: only users this person may manage (HR never gets Admins),
     * filtered by name and role, sorted and paged.
     */
    public function data(Request $request)
    {
        $actor = $request->user();
        $visibleRoles = $actor->manageableRoles();

        $filters = is_array($request->query('filters')) ? $request->query('filters') : [];
        $name = is_string($filters['name'] ?? null) ? trim($filters['name']) : '';
        $role = is_string($filters['role'] ?? null) ? $filters['role'] : '';
        $search = trim((string) data_get($request->query('search'), 'value', ''));

        // Name filter and search box both match the name, username or email.
        $matches = function ($query, string $text) {
            $like = '%'.addcslashes($text, '%_\\').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('username', 'like', $like)->orWhere('email', 'like', $like));
        };

        $base = User::with('employee')->whereIn('role', $visibleRoles);
        $query = (clone $base)
            ->when($name !== '', fn ($q) => $matches($q, $name))
            ->when($role !== '', fn ($q) => $q->where('role', $role)) // combined with whereIn above: a hidden role gives no rows
            ->when($search !== '', fn ($q) => $matches($q, $search));

        $column = data_get($request->query('columns'), (int) data_get($request->query('order'), '0.column', -1));
        if (data_get($column, 'orderable') === 'true' && isset(self::SORTABLE[data_get($column, 'name')])) {
            $query->orderBy(self::SORTABLE[data_get($column, 'name')], data_get($request->query('order'), '0.dir') === 'desc' ? 'desc' : 'asc');
        }
        $query->orderBy('name')->orderBy('id');

        $length = in_array($request->integer('length'), self::PAGE_LENGTHS, true) ? $request->integer('length') : self::PAGE_LENGTHS[0];
        $filtered = (clone $query)->count();
        $adminCount = User::where('role', 'admin')->count();

        return response()->json([
            'draw' => $request->integer('draw'),
            'recordsTotal' => $base->count(),
            'recordsFiltered' => $filtered,
            'data' => $query->skip(max(0, $request->integer('start')))->take($length)->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'initials' => $user->initials,
                'role' => $user->role,
                'role_label' => $user->role_label,
                'role_color' => $user->role_color,
                'created_at' => $user->created_at?->format('Y-m-d'),
                'employee' => $user->employee?->full_name,
                'is_self' => $user->is($actor),
                'can_delete' => ! $user->is($actor) && ! ($user->isAdmin() && $adminCount <= 1),
                'urls' => [
                    'edit' => route('admin.users.edit', $user),
                    'password' => route('admin.users.edit', $user).'#set-password',
                    'destroy' => route('admin.users.destroy', $user),
                ],
            ]),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.users.create', [
            'user' => new User(['role' => 'employee']),
            'roles' => $this->assignableRoles($request->user()),
            'employees' => $this->linkableEmployees(),
        ]);
    }

    public function store(Request $request)
    {
        $user = User::create($this->validated($request));

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} created successfully.");
    }

    public function edit(Request $request, User $user)
    {
        $this->ensureCanManage($request, $user);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->assignableRoles($request->user()),
            'employees' => $this->linkableEmployees($user),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureCanManage($request, $user);

        $data = $this->validated($request, $user);

        if ($data['role'] !== $user->role) {
            if ($user->is($request->user())) {
                throw ValidationException::withMessages(['role' => 'You cannot change your own role.']);
            }
            if ($user->isAdmin()) {
                $this->ensureAnotherAdminExists($user, 'role');
            }
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} updated successfully.");
    }

    public function destroy(Request $request, User $user)
    {
        $this->ensureCanManage($request, $user);

        $error = match (true) {
            $user->is($request->user()) => 'You cannot delete your own account.',
            $user->isAdmin() && User::where('role', 'admin')->count() <= 1 => 'You cannot delete the last admin.',
            default => null,
        };

        if ($error) {
            return $request->expectsJson()
                ? response()->json(['message' => $error], 422)
                : back()->withErrors(['user' => $error]);
        }

        $user->delete();
        $message = "User {$user->name} deleted successfully.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    /**
     * Users outside the current user's "manages" roles (e.g. Admins for HR) don't exist for them: 404.
     */
    private function ensureCanManage(Request $request, User $user): void
    {
        abort_unless($request->user()->canManageUser($user), 404);
    }

    /**
     * The roles the current user may give to someone, as [key => config].
     */
    private function assignableRoles(User $actor): array
    {
        return array_intersect_key(config('permissions.roles'), array_flip($actor->manageableRoles()));
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in($request->user()->manageableRoles())],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            // The employee record this login belongs to: required for the Employee role, one login per employee.
            'employee_id' => [
                Rule::requiredIf($request->input('role') === 'employee'), 'nullable', 'integer',
                'exists:employees,id', Rule::unique('users', 'employee_id')->ignore($user),
            ],
        ], [
            'employee_id.required' => 'Please choose the employee this login belongs to.',
            'employee_id.unique' => 'This employee already has a login.',
            'employee_id.exists' => 'The selected employee does not exist.',
        ]);
    }

    /**
     * Employees that can be linked to a login: those without one yet (plus the user's current one).
     */
    private function linkableEmployees(?User $user = null)
    {
        return Employee::query()
            ->whereDoesntHave('user', fn ($q) => $q->when($user, fn ($q) => $q->whereKeyNot($user->getKey())))
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);
    }

    private function ensureAnotherAdminExists(User $user, string $field): void
    {
        if (User::where('role', 'admin')->whereKeyNot($user->getKey())->doesntExist()) {
            throw ValidationException::withMessages([$field => 'There must be at least one admin.']);
        }
    }
}
