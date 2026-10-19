@extends('layouts.app')

@section('title', 'Manage Departments')

@push('styles')
    <style>
        /* Teal header, like the other tables */
        .departments-table thead th {
            background: var(--brand); color: #fff; font-weight: 700; font-size: .78rem; white-space: nowrap;
            border: 0; border-right: 1px solid rgba(255, 255, 255, .18); padding: .85rem .75rem;
        }
        .departments-table thead th:last-child { border-right: 0; }
        .departments-table td { padding: .85rem .75rem; vertical-align: middle; font-size: .85rem; }
        .departments-table tbody tr:hover > td { background: #f7fbfa; }
        .department-desc { max-width: 22rem; }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-0">Manage Departments</h1>
            <div class="text-muted small">Departments, their codes and branches.</div>
        </div>
        <a href="{{ route('admin.departments.create') }}" class="btn btn-generate">
            <i class="bi bi-plus-lg me-1"></i> Add Department
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}</div>
    @endif

    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Departments</h2>
            <form method="GET" action="{{ route('admin.departments.index') }}" class="d-flex gap-2" role="search">
                <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
                       placeholder="Search name or code" aria-label="Search departments" style="min-width: 14rem">
                <button type="submit" class="btn btn-sm btn-soft"><i class="bi bi-search"></i></button>
                @if ($search !== '')
                    <a href="{{ route('admin.departments.index') }}" class="btn btn-sm btn-clear">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table departments-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 3rem">#</th>
                        <th>Department</th>
                        <th>Code</th>
                        <th>Branch</th>
                        <th class="text-center">Employees</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departments as $department)
                        <tr>
                            <td class="text-muted tabular">{{ $departments->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="fw-semibold">{{ $department->name }}</div>
                                @if ($department->description)
                                    <div class="small text-muted text-truncate department-desc" title="{{ $department->description }}">{{ $department->description }}</div>
                                @endif
                            </td>
                            <td><span class="badge text-bg-light border font-monospace">{{ $department->code ?? '—' }}</span></td>
                            <td>
                                {{-- loaded with with('branch.company'): no extra query per row --}}
                                <div>{{ $department->branch?->name ?? '—' }}</div>
                                <div class="small text-muted">{{ $department->branch?->company?->name }}</div>
                            </td>
                            <td class="text-center">
                                {{-- employees_count comes from withCount('employees') --}}
                                <a href="{{ route('employees.index', ['filters' => ['department_id' => $department->id]]) }}"
                                   class="badge rounded-pill {{ $department->employees_count ? 'bg-primary-subtle text-primary-emphasis border border-primary-subtle' : 'text-bg-light border text-muted' }} text-decoration-none"
                                   title="Show these employees">
                                    {{ $department->employees_count }}
                                </a>
                            </td>
                            <td class="text-end">
                                <div class="dropdown actions-menu d-inline-block">
                                    <button type="button" class="btn btn-sm btn-icon actions-toggle" data-bs-toggle="dropdown"
                                            aria-expanded="false" aria-label="Actions for {{ $department->name }}">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.departments.edit', $department) }}">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.departments.destroy', $department) }}" method="POST"
                                                  onsubmit="return confirm('Delete department {{ addslashes($department->name) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i> Delete</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-diagram-3 fs-2 d-block mb-1 opacity-50"></i>
                                {{ $search !== '' ? 'No departments match your search.' : 'No departments yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($departments->total() > 0)
            <div class="panel-body border-top d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
                <div class="small text-muted">
                    Showing {{ $departments->firstItem() }} to {{ $departments->lastItem() }} of {{ $departments->total() }} departments
                </div>
                {{ $departments->links() }}
            </div>
        @endif
    </div>
@endsection
