@extends('layouts.app')

@section('title', 'Edit '.$employee->full_name)

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
            <li class="breadcrumb-item"><a href="{{ route('employees.show', $employee) }}">{{ $employee->full_name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center gap-3 mb-4">
        @include('employees._avatar', ['size' => 56])
        <div>
            <h1 class="h3 mb-0">Edit employee</h1>
            <div class="text-muted">{{ $employee->full_name }} · #{{ $employee->id }}</div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <form action="{{ route('employees.update', $employee) }}" method="POST">
                @method('PUT')
                @include('employees._form', [
                    'submitLabel' => 'Save changes',
                    'cancelUrl' => route('employees.show', $employee),
                ])
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-info-circle me-1"></i> Record details</div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Employee ID</span> <span>#{{ $employee->id }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Created</span>
                        <span title="{{ $employee->created_at }}">{{ $employee->created_at?->diffForHumans() ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Last updated</span>
                        <span title="{{ $employee->updated_at }}">{{ $employee->updated_at?->diffForHumans() ?? '—' }}</span>
                    </li>
                </ul>
                <div class="card-body pt-2">
                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-eye me-1"></i> View profile
                    </a>
                </div>
            </div>

            <div class="card border-danger-subtle shadow-sm">
                <div class="card-header bg-danger-subtle text-danger-emphasis fw-semibold">
                    <i class="bi bi-exclamation-octagon me-1"></i> Danger zone
                </div>
                <div class="card-body">
                    <p class="small text-muted">Deleting an employee is permanent and cannot be undone.</p>
                    <form action="{{ route('employees.destroy', $employee) }}" method="POST"
                          onsubmit="return confirm('Delete {{ addslashes($employee->full_name) }}? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-trash me-1"></i> Delete employee
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
