@extends('layouts.app')

@section('title', 'Add Department')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ auth()->user()->panel_label }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.departments.index') }}">Manage Departments</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add department</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <span class="avatar text-bg-primary" style="width: 56px; height: 56px; font-size: 1.4rem" aria-hidden="true"><i class="bi bi-diagram-3"></i></span>
        <div class="me-auto">
            <h1 class="h4 fw-bold mb-1">Add department</h1>
            <div class="small text-muted">Fields marked <span class="text-danger">*</span> are required.</div>
        </div>
        <x-button variant="secondary" icon="bi-arrow-left" href="{{ route('admin.departments.index') }}">Back to departments</x-button>
    </div>

    <form method="POST" action="{{ route('admin.departments.store') }}">
        @include('admin.departments._form', ['submitLabel' => 'Create department'])
    </form>
@endsection
