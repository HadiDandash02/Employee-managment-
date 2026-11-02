@extends('layouts.app')

@section('title', 'Add User')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ auth()->user()->panel_label }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Manage Users</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add user</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <span class="avatar text-bg-primary" style="width: 56px; height: 56px; font-size: 1.4rem" aria-hidden="true"><i class="bi bi-person-plus"></i></span>
        <div class="me-auto">
            <h1 class="h4 fw-bold mb-1">Add user</h1>
            <div class="small text-muted">Create a login. Fields marked <span class="text-danger">*</span> are required.</div>
        </div>
        <x-button variant="secondary" icon="bi-arrow-left" href="{{ route('admin.users.index') }}">Back to users</x-button>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}">
        @include('admin.users._form', ['submitLabel' => 'Create user'])
    </form>
@endsection
