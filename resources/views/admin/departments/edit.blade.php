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

    <h1 class="h4 fw-bold mb-3">Edit department <span class="text-muted fw-normal font-monospace">{{ $department->code }}</span></h1>

    <div class="row">
        <div class="col-lg-9 col-xl-8">
            <form method="POST" action="{{ route('admin.departments.update', $department) }}">
                @method('PUT')
                @include('admin.departments._form', ['submitLabel' => 'Save changes'])
            </form>
        </div>
    </div>
@endsection
