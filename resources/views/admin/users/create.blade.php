@extends('layouts.app')

@section('title', 'Add User')

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ auth()->user()->panel_label }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Manage Users</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add user</li>
        </ol>
    </nav>

    <h1 class="h4 fw-bold mb-3">Add user</h1>

    <div class="row">
        <div class="col-lg-9 col-xl-8">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @include('admin.users._form', ['submitLabel' => 'Create user'])
            </form>
        </div>
    </div>
@endsection
