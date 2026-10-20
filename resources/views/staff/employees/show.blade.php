@extends('layouts.app')

@section('title', $employee->full_name)

@section('content')
    @php
        $hasPeers = $departmentCount > 1 && $departmentAverage > 0;
        $difference = $hasPeers ? ($employee->salary - $departmentAverage) / $departmentAverage * 100 : 0;
    @endphp

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $employee->full_name }}</li>
        </ol>
    </nav>

    {{-- Profile header --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body p-4 d-flex flex-wrap align-items-center gap-3">
            @include('partials.avatar', ['size' => 72])
            <div class="me-auto">
                <h1 class="h3 mb-1">{{ $employee->full_name }}</h1>
                <div class="d-flex flex-wrap gap-3 text-muted small">
                    <span><i class="bi bi-hash"></i>{{ $employee->id }}</span>
                    <span><i class="bi bi-diagram-3 me-1"></i>{{ $employee->department?->name }}</span>
                    <a href="mailto:{{ $employee->email }}" class="text-muted text-decoration-none">
                        <i class="bi bi-envelope me-1"></i>{{ $employee->email }}
                    </a>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @can('update', $employee)
                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">
                        <i class="bi bi-pencil-square me-1"></i> Edit
                    </a>
                @endcan
                @can('delete', $employee)
                    <form action="{{ route('employees.destroy', $employee) }}" method="POST"
                          onsubmit="return confirm('Move {{ addslashes($employee->full_name) }} to the trash? You can restore them later.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-1"></i> Delete
                        </button>
                    </form>
                @endcan
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    {{-- Quick stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="info-label mb-1"><i class="bi bi-cash-stack me-1"></i> Annual salary</div>
                    <div class="fs-4 fw-semibold">{{ number_format($employee->salary, 2) }}</div>
                    <div class="small text-muted">{{ number_format($employee->salary / 12, 2) }} per month</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="info-label mb-1"><i class="bi bi-graph-up me-1"></i> vs. department average</div>
                    @if ($hasPeers)
                        <div class="fs-4 fw-semibold {{ $difference >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="bi {{ $difference >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                            {{ number_format(abs($difference), 1) }}%
                        </div>
                        <div class="small text-muted">Average: {{ number_format($departmentAverage, 2) }}</div>
                    @else
                        <div class="fs-4 fw-semibold text-muted">—</div>
                        <div class="small text-muted">Only employee in this department</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="info-label mb-1"><i class="bi bi-cake2 me-1"></i> Age</div>
                    <div class="fs-4 fw-semibold">{{ $employee->age }} years</div>
                    <div class="small text-muted">Born {{ $employee->date_of_birth->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="info-label mb-1"><i class="bi bi-people me-1"></i> Department size</div>
                    <div class="fs-4 fw-semibold">{{ $departmentCount }}</div>
                    <div class="small text-muted">{{ Str::plural('employee', $departmentCount) }} in {{ $employee->department?->name }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-person-vcard me-1"></i> Personal information</div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <div class="info-label">First name</div>
                            <div>{{ $employee->first_name }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="info-label">Last name</div>
                            <div>{{ $employee->last_name }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="info-label">Email</div>
                            <div class="d-flex align-items-center gap-2">
                                <a href="mailto:{{ $employee->email }}" class="text-break">{{ $employee->email }}</a>
                                <button type="button" class="btn btn-sm btn-link p-0 text-muted js-copy"
                                        data-copy="{{ $employee->email }}" data-bs-toggle="tooltip" title="Copy email"
                                        aria-label="Copy email">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="info-label">Date of birth</div>
                            <div>{{ $employee->date_of_birth->format('d M Y') }} <span class="text-muted">({{ $employee->age }} years)</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-briefcase me-1"></i> Employment</div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <div class="info-label">Department</div>
                            <a href="{{ route('employees.index', ['filters' => ['department_id' => $employee->department_id]]) }}"
                               class="badge text-bg-light border text-decoration-none fs-6 fw-normal">
                                {{ $employee->department?->name }}
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <div class="info-label">Annual salary</div>
                            <div>{{ number_format($employee->salary, 2) }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="info-label">Leave</div>
                            <div>
                                {{ App\Services\Leave\LeaveBalance::format($employee->base_leave_days) }} days a year
                                + {{ config('leave.monthly_accrual') }} per month
                            </div>
                        </div>
                        {{-- department → branch → company (belongsTo chain) --}}
                        <div class="col-sm-6">
                            <div class="info-label">Branch</div>
                            <div>{{ $employee->department?->branch?->name ?? '—' }}
                                @if ($employee->department?->branch?->city)
                                    <span class="text-muted">({{ $employee->department->branch->city }})</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="info-label">Company</div>
                            <div>{{ $employee->department?->branch?->company?->name ?? '—' }}</div>
                        </div>
                        {{-- hasOne profile --}}
                        <div class="col-sm-6">
                            <div class="info-label">Hire date</div>
                            <div>{{ $employee->profile?->hire_date?->format('d M Y') ?? '—' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="info-label">Phone</div>
                            <div>{{ $employee->profile?->phone ?: '—' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="info-label">Address</div>
                            <div>{{ $employee->profile?->address ?: '—' }}</div>
                        </div>
                        {{-- belongsToMany skills, with the level stored on the pivot table --}}
                        <div class="col-12">
                            <div class="info-label mb-1">Skills</div>
                            @forelse ($employee->skills as $skill)
                                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle fw-medium me-1 mb-1">
                                    {{ $skill->name }}@if ($skill->pivot->level) <span class="opacity-75">· {{ $skill->pivot->level }}</span>@endif
                                </span>
                            @empty
                                <span class="text-muted">—</span>
                            @endforelse
                        </div>
                        @if ($hasPeers)
                            <div class="col-12">
                                <div class="info-label mb-2">Salary compared with department average</div>
                                @php
                                    $max = max($employee->salary, $departmentAverage) * 1.1;
                                @endphp
                                <div class="small d-flex justify-content-between mb-1">
                                    <span>{{ $employee->first_name }}</span><span>{{ number_format($employee->salary, 2) }}</span>
                                </div>
                                <div class="progress mb-2" style="height: 8px" role="progressbar"
                                     aria-label="{{ $employee->first_name }}'s salary">
                                    <div class="progress-bar" style="width: {{ $employee->salary / $max * 100 }}%"></div>
                                </div>
                                <div class="small d-flex justify-content-between mb-1">
                                    <span>{{ $employee->department?->name }} average</span><span>{{ number_format($departmentAverage, 2) }}</span>
                                </div>
                                <div class="progress" style="height: 8px" role="progressbar" aria-label="Department average salary">
                                    <div class="progress-bar bg-secondary" style="width: {{ $departmentAverage / $max * 100 }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-clock-history me-1"></i> Record</div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Created</span>
                        <span title="{{ $employee->created_at }}">{{ $employee->created_at?->diffForHumans() ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Last updated</span>
                        <span title="{{ $employee->updated_at }}">{{ $employee->updated_at?->diffForHumans() ?? '—' }}</span>
                    </li>
                    {{-- The login linked to this employee (users.employee_id) --}}
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">Login account</span>
                        @if ($employee->user)
                            <span>
                                @can('manage-user', $employee->user)
                                    <a href="{{ route('admin.users.edit', $employee->user) }}" class="text-decoration-none">
                                        <i class="bi bi-key me-1"></i>{{ '@'.$employee->user->username }}
                                    </a>
                                @else
                                    <i class="bi bi-key me-1"></i>{{ '@'.$employee->user->username }}
                                @endcan
                                @unless ($employee->user->is_active)
                                    <span class="badge text-bg-danger fw-normal ms-1">Suspended</span>
                                @endunless
                            </span>
                        @else
                            <span class="text-muted">No login</span>
                        @endif
                    </li>
                </ul>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-people me-1"></i> Colleagues in {{ $employee->department?->name }}</span>
                    <span class="badge text-bg-secondary">{{ $departmentCount - 1 }}</span>
                </div>
                @if ($colleagues->isEmpty())
                    <div class="card-body small text-muted">No other employees in this department.</div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($colleagues as $colleague)
                            <a href="{{ route('employees.show', $colleague) }}"
                               class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                                @include('partials.avatar', ['employee' => $colleague, 'size' => 32])
                                <div class="small lh-sm">
                                    <div class="fw-medium">{{ $colleague->full_name }}</div>
                                    <div class="text-muted">{{ $colleague->email }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    @if ($departmentCount - 1 > $colleagues->count())
                        <div class="card-footer bg-white text-center">
                            <a href="{{ route('employees.index', ['filters' => ['department_id' => $employee->department_id]]) }}" class="small">
                                View all {{ $departmentCount - 1 }} colleagues
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => bootstrap.Tooltip.getOrCreateInstance(el));

    document.querySelectorAll('.js-copy').forEach(button => {
        button.addEventListener('click', async () => {
            const tooltip = bootstrap.Tooltip.getInstance(button);
            try {
                await navigator.clipboard.writeText(button.dataset.copy);
                tooltip?.setContent({ '.tooltip-inner': 'Copied!' });
            } catch {
                tooltip?.setContent({ '.tooltip-inner': 'Copy failed' });
            }
            setTimeout(() => tooltip?.setContent({ '.tooltip-inner': 'Copy email' }), 1500);
        });
    });
</script>
@endpush
