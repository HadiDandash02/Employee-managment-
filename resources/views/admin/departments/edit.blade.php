@extends('layouts.app')

@section('title', 'Edit '.$department->name)

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ auth()->user()->panel_label }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.departments.index') }}">Manage Departments</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit {{ $department->name }}</li>
        </ol>
    </nav>

    {{-- Which department is being edited --}}
    <div class="panel mb-4">
        <div class="panel-body d-flex flex-wrap align-items-center gap-3">
            <span class="avatar text-bg-primary" style="width: 56px; height: 56px; font-size: 1.4rem" aria-hidden="true"><i class="bi bi-diagram-3"></i></span>
            <div class="me-auto">
                <h1 class="h4 fw-bold mb-1">{{ $department->name }}</h1>
                <div class="d-flex flex-wrap align-items-center gap-3 small text-muted">
                    <span class="badge text-bg-light border font-monospace">{{ $department->code }}</span>
                    <span><i class="bi bi-building me-1"></i>{{ $department->branch?->name }}</span>
                    <span><i class="bi bi-briefcase me-1"></i>{{ $department->branch?->company?->name }}</span>
                </div>
            </div>
            <x-button variant="secondary" icon="bi-arrow-left" href="{{ route('admin.departments.index') }}">Back to departments</x-button>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.departments.update', $department) }}">
        @method('PUT')
        @include('admin.departments._form', ['submitLabel' => 'Save changes'])
    </form>
@endsection
