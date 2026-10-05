@extends('layouts.app')

@section('title', 'Employees')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Employees</h1>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">+ Add Employee</a>
    </div>

    <form method="GET" action="{{ route('employees.index') }}" class="row g-2 mb-3">
        <div class="col-sm-8 col-md-6">
            <input type="text" name="search" value="{{ $search }}" class="form-control"
                   placeholder="Search by name, email or department">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-outline-secondary">Search</button>
            @if ($search)
                <a href="{{ route('employees.index') }}" class="btn btn-link">Clear</a>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th class="text-end">Salary</th>
                        <th>Date of Birth</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td>{{ $employee->id }}</td>
                            <td>{{ $employee->full_name }}</td>
                            <td>{{ $employee->email }}</td>
                            <td>{{ $employee->department }}</td>
                            <td class="text-end">{{ number_format($employee->salary, 2) }}</td>
                            <td>{{ $employee->date_of_birth->format('Y-m-d') }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-info">View</a>
                                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this employee?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No employees found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $employees->links() }}
    </div>
@endsection
