@extends('layouts.app')

@section('title', 'Add Employee')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center gap-3 mb-4">
        <span class="avatar text-bg-primary" style="width: 56px; height: 56px; font-size: 24px" aria-hidden="true">
            <i class="bi bi-person-plus"></i>
        </span>
        <div>
            <h1 class="h3 mb-0">Add employee</h1>
            <div class="text-muted">Fields marked <span class="text-danger">*</span> are required.</div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <form action="{{ route('employees.store') }}" method="POST">
                @include('staff.employees._form', ['submitLabel' => 'Create employee'])
            </form>
        </div>

        {{-- Guide beside the form, so the page uses the full width --}}
        <div class="col-lg-4">
            <div class="panel" style="position: sticky; top: 5.5rem">
                <div class="panel-header"><h2 class="panel-title"><i class="bi bi-lightbulb me-1"></i> Before you save</h2></div>
                <div class="panel-body">
                    <ul class="list-unstyled small d-grid gap-3 mb-0">
                        <li><i class="bi bi-1-circle text-primary me-2"></i>Fields marked <span class="text-danger">*</span> are required. The server checks them too.</li>
                        <li><i class="bi bi-2-circle text-primary me-2"></i>The email must be unique, and the date of birth in the past (dd/mm/yyyy).</li>
                        <li><i class="bi bi-3-circle text-primary me-2"></i>Every employee starts with 3 leave days a year, plus 1.25 each month.</li>
                        <li><i class="bi bi-4-circle text-primary me-2"></i>To let the employee log in, create a user for them in Manage Users afterwards.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
