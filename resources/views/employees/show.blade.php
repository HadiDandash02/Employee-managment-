@extends('layouts.app')

@section('title', $employee->full_name)

@section('content')
    <h1 class="h3 mb-3">{{ $employee->full_name }}</h1>

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">First Name</dt>
                <dd class="col-sm-9">{{ $employee->first_name }}</dd>

                <dt class="col-sm-3">Last Name</dt>
                <dd class="col-sm-9">{{ $employee->last_name }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $employee->email }}</dd>

                <dt class="col-sm-3">Department</dt>
                <dd class="col-sm-9">{{ $employee->department }}</dd>

                <dt class="col-sm-3">Salary</dt>
                <dd class="col-sm-9">{{ number_format($employee->salary, 2) }}</dd>

                <dt class="col-sm-3">Date of Birth</dt>
                <dd class="col-sm-9">{{ $employee->date_of_birth->format('Y-m-d') }}</dd>
            </dl>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">Edit</a>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Back to list</a>
    </div>
@endsection
