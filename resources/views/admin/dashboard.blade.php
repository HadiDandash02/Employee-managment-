@extends('layouts.app')

@section('title', auth()->user()->panel_label.' Dashboard')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-0">{{ auth()->user()->panel_label }} Dashboard</h1>
            <div class="text-muted small">Overview, users and permissions.</div>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-generate">
            <i class="bi bi-person-plus me-1"></i> Add User
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}</div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        @foreach ([
            ['Employees', number_format($stats['employees']), 'bi-people', 'primary'],
            ['Departments', number_format($stats['departments']), 'bi-diagram-3', 'info'],
            ['Average salary', number_format($stats['average_salary'], 2), 'bi-cash-stack', 'success'],
            ['Total payroll', number_format($stats['payroll'], 2), 'bi-wallet2', 'warning'],
            ['Users', number_format($stats['users']), 'bi-person-badge', 'secondary'],
        ] as [$label, $value, $icon, $color])
            <div class="col-sm-6 col-xl">
                <div class="panel stat-card">
                    <span class="stat-icon bg-{{ $color }}-subtle text-{{ $color }}-emphasis"><i class="bi {{ $icon }}"></i></span>
                    <div>
                        <div class="stat-value tabular">{{ $value }}</div>
                        <div class="stat-label">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        {{-- Users --}}
        <div class="col-xl-8">
            <div class="panel h-100" id="users" style="scroll-margin-top: 5rem">
                <div class="panel-header">
                    <h2 class="panel-title">Users</h2>
                    <a href="{{ route('admin.users.index') }}" class="small">Manage users ({{ $users->count() }})</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="small text-muted">
                                <th class="ps-3 fw-semibold">User</th>
                                <th class="fw-semibold">Username</th>
                                <th class="fw-semibold">Role</th>
                                <th class="fw-semibold">Created</th>
                                <th class="text-end pe-3 fw-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                @php
                                    $roleColor = ['admin' => 'danger', 'hr' => 'warning', 'manager' => 'primary', 'viewer' => 'secondary'][$user->role] ?? 'secondary';
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar text-bg-{{ $roleColor }}" style="width: 34px; height: 34px; font-size: 12px">{{ $user->initials }}</span>
                                            <div class="lh-sm">
                                                <div class="fw-semibold">
                                                    {{ $user->name }}
                                                    @if ($user->is(auth()->user()))
                                                        <span class="badge text-bg-light border fw-normal ms-1">You</span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted">{{ '@'.$user->username }}</td>
                                    <td>
                                        <span class="badge rounded-pill bg-{{ $roleColor }}-subtle text-{{ $roleColor }}-emphasis border border-{{ $roleColor }}-subtle">
                                            {{ $user->role_label }}
                                        </span>
                                    </td>
                                    <td class="small text-muted text-nowrap">{{ $user->created_at?->format('Y-m-d') }}</td>
                                    <td class="text-end pe-3">
                                        {{-- One "⋮" button; the actions open in a small menu --}}
                                        <div class="dropdown actions-menu d-inline-block">
                                            <button type="button" class="btn btn-sm btn-icon actions-toggle" data-bs-toggle="dropdown"
                                                    aria-expanded="false" aria-label="Actions for {{ $user->name }}">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('admin.users.edit', $user) }}"><i class="bi bi-pencil-square"></i> Edit</a></li>
                                                <li><a class="dropdown-item" href="{{ route('admin.users.edit', $user) }}#set-password"><i class="bi bi-key"></i> Reset password</a></li>
                                                @unless ($user->is(auth()->user()))
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                                              onsubmit="return confirm('Delete user {{ addslashes($user->name) }}?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i> Delete</button>
                                                        </form>
                                                    </li>
                                                @endunless
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Departments --}}
        <div class="col-xl-4">
            <div class="panel h-100">
                <div class="panel-header">
                    <h2 class="panel-title">Departments</h2>
                </div>
                <div class="panel-body">
                    @php $max = max(1, $departments->max('employees_count')); @endphp
                    @forelse ($departments as $department)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <a href="{{ route('employees.index', ['filters' => ['department_id' => $department->id]]) }}" class="fw-semibold text-body text-decoration-none">
                                    {{ $department->name }} <span class="text-muted fw-normal">· {{ $department->branch?->name }}</span>
                                </a>
                                <span class="text-muted">{{ $department->employees_count }} · avg {{ number_format($department->employees_avg_salary, 0) }}</span>
                            </div>
                            <div class="progress" style="height: 8px" role="progressbar" aria-label="{{ $department->name }}: {{ $department->employees_count }} employees">
                                <div class="progress-bar" style="width: {{ $department->employees_count / $max * 100 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No employees yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Recent employees --}}
        <div class="col-xl-5">
            <div class="panel h-100">
                <div class="panel-header">
                    <h2 class="panel-title">Recently added</h2>
                    <a href="{{ route('employees.index') }}" class="small">View all</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($recentEmployees as $employee)
                        <a href="{{ route('employees.show', $employee) }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-3">
                            @include('employees._avatar', ['size' => 32])
                            <div class="lh-sm me-auto">
                                <div class="fw-semibold small">{{ $employee->full_name }}</div>
                                <div class="small text-muted">{{ $employee->department?->name }}</div>
                            </div>
                            <span class="small text-muted">{{ $employee->created_at?->diffForHumans() }}</span>
                        </a>
                    @empty
                        <div class="panel-body text-muted small">No employees yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Roles & permissions --}}
        <div class="col-xl-7">
            <div class="panel h-100">
                <div class="panel-header">
                    <h2 class="panel-title">Roles &amp; permissions</h2>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 small">
                        <thead>
                            <tr class="text-muted">
                                <th class="ps-3 fw-semibold">Permission</th>
                                @foreach ($roles as $role)
                                    <th class="text-center fw-semibold">{{ $role['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($permissions as $permission => $description)
                                <tr>
                                    <td class="ps-3">{{ $description }}</td>
                                    @foreach ($roles as $role)
                                        <td class="text-center">
                                            @if (in_array('*', $role['permissions'], true) || in_array($permission, $role['permissions'], true))
                                                <i class="bi bi-check-circle-fill text-success" aria-label="Allowed"></i>
                                            @else
                                                <i class="bi bi-dash-circle text-muted opacity-50" aria-label="Not allowed"></i>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => bootstrap.Tooltip.getOrCreateInstance(el));
</script>
@endpush
