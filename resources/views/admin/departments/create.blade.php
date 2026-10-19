@extends('layouts.app')

@section('title', 'Add Department')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ auth()->user()->panel_label }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.departments.index') }}">Manage Departments</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add department</li>
        </ol>
    </nav>

    <h1 class="h4 fw-bold mb-3">Add department</h1>

    <div class="row">
        <div class="col-lg-9 col-xl-8">
            <form method="POST" action="{{ route('admin.departments.store') }}">
                @include('admin.departments._form', ['submitLabel' => 'Create department'])
            </form>
        </div>
    </div>
@endsection
