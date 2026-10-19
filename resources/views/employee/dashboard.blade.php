@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
    {{-- Display only: every number comes from EmployeeDashboardService (no business logic in Blade). --}}
    <div class="mb-4">
        <h1 class="h4 fw-bold mb-0">Welcome, {{ $employee?->first_name ?? $user->name }}</h1>
        <div class="text-muted small">{{ now()->format('l, d M Y') }}</div>
    </div>

    @if (! $employee)
        <div class="panel">
            <div class="panel-body text-center py-5">
                <i class="bi bi-person-exclamation fs-1 text-muted d-block mb-2"></i>
                <div class="fw-semibold mb-1">Your account is not linked to an employee record yet.</div>
                <div class="text-muted small">Ask HR to link your login to your employee record in Manage Users.</div>
            </div>
        </div>
    @else
        <div class="row g-3 mb-4">
            @foreach ([
                ['Department', $employee->department?->name ?? '—', 'bi-diagram-3', 'primary', $employee->department?->branch?->name],
                ['Years of service', $summary['years_of_service'] ?? '—', 'bi-award', 'success', $employee->profile?->hire_date ? 'since '.$employee->profile->hire_date->format('d M Y') : 'hire date not set'],
                ['Next birthday', $summary['days_to_birthday'] === 0 ? 'Today 🎉' : $summary['days_to_birthday'].' days', 'bi-cake2', 'warning', $employee->date_of_birth->format('d M')],
                ['Skills', $summary['skills'], 'bi-stars', 'info', $summary['department_size'].' '.Str::plural('person', $summary['department_size']).' in your department'],
            ] as [$label, $value, $icon, $color, $hint])
                <div class="col-sm-6 col-xl-3">
                    <div class="panel stat-card">
                        <span class="stat-icon bg-{{ $color }}-subtle text-{{ $color }}-emphasis"><i class="bi {{ $icon }}"></i></span>
                        <div>
                            <div class="stat-value">{{ $value }}</div>
                            <div class="stat-label">{{ $label }}</div>
                            @if ($hint)<div class="small text-muted">{{ $hint }}</div>@endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="panel h-100">
                    <div class="panel-header"><h2 class="panel-title">My skills</h2></div>
                    <div class="panel-body">
                        @forelse ($employee->skills as $skill)
                            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle fw-medium me-1 mb-2 fs-6">
                                {{ $skill->name }}@if ($skill->pivot->level) <span class="opacity-75 small">· {{ $skill->pivot->level }}</span>@endif
                            </span>
                        @empty
                            <p class="text-muted mb-0">No skills recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="panel h-100">
                    <div class="panel-header"><h2 class="panel-title">Quick links</h2></div>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('profile.show') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-3 py-3">
                            <i class="bi bi-person-vcard text-primary"></i> My Profile <i class="bi bi-chevron-right ms-auto text-muted"></i>
                        </a>
                        <a href="{{ route('account.edit') }}#change-password" class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-3 py-3">
                            <i class="bi bi-key text-primary"></i> Change password <i class="bi bi-chevron-right ms-auto text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
