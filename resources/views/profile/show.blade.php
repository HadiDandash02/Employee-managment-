@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold mb-0">My Profile</h1>
        <div class="text-muted small">Your employee record. Ask HR if something is wrong.</div>
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
        {{-- Header --}}
        <div class="panel mb-4">
            <div class="panel-body d-flex flex-wrap align-items-center gap-3">
                @include('employees._avatar', ['size' => 72])
                <div class="me-auto">
                    <h2 class="h4 fw-bold mb-1">{{ $employee->full_name }}</h2>
                    <div class="d-flex flex-wrap gap-3 text-muted small">
                        <span><i class="bi bi-hash"></i>{{ $employee->id }}</span>
                        <span><i class="bi bi-diagram-3 me-1"></i>{{ $employee->department?->name ?? '—' }}</span>
                        <span><i class="bi bi-envelope me-1"></i>{{ $employee->email }}</span>
                    </div>
                </div>
                <a href="{{ route('account.edit') }}#change-password" class="btn btn-soft">
                    <i class="bi bi-key me-1"></i> Change password
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="panel h-100">
                    <div class="panel-header"><h3 class="panel-title">Personal information</h3></div>
                    <div class="panel-body">
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
                                <div class="text-break">{{ $employee->email }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Date of birth</div>
                                <div>{{ $employee->date_of_birth->format('d M Y') }} <span class="text-muted">({{ $employee->age }} years)</span></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Phone</div>
                                <div>{{ $employee->profile?->phone ?: '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Address</div>
                                <div>{{ $employee->profile?->address ?: '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="panel h-100">
                    <div class="panel-header"><h3 class="panel-title">Employment</h3></div>
                    <div class="panel-body">
                        <div class="row g-4">
                            <div class="col-sm-6">
                                <div class="info-label">Department</div>
                                <div>{{ $employee->department?->name ?? '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Branch</div>
                                <div>{{ $employee->department?->branch?->name ?? '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Company</div>
                                <div>{{ $employee->department?->branch?->company?->name ?? '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Hire date</div>
                                <div>{{ $employee->profile?->hire_date?->format('d M Y') ?? '—' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">Annual salary</div>
                                <div>{{ number_format($employee->salary, 2) }} <span class="text-muted small">({{ number_format($employee->salary / 12, 2) }} / month)</span></div>
                            </div>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
