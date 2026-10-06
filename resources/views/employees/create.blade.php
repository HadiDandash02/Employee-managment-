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
                @include('employees._form', ['submitLabel' => 'Create employee'])
            </form>
        </div>
    </div>
@endsection
