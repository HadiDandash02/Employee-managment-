@extends('layouts.app')

@section('title', 'Edit '.$user->name)

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ auth()->user()->panel_label }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Manage Users</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit {{ $user->name }}</li>
        </ol>
    </nav>

    <h1 class="h4 fw-bold mb-3">Edit user <span class="text-muted fw-normal">{{ '@'.$user->username }}</span></h1>

    <div class="row">
        <div class="col-lg-9 col-xl-8">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @method('PUT')
                @include('admin.users._form', ['submitLabel' => 'Save changes'])
            </form>
        </div>
    </div>
@endsection
