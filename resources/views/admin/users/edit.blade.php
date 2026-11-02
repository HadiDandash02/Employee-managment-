@extends('layouts.app')

@section('title', 'Edit '.$user->name)

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ auth()->user()->panel_label }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Manage Users</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit {{ $user->name }}</li>
        </ol>
    </nav>

    {{-- Who is being edited --}}
    <div class="panel mb-4">
        <div class="panel-body d-flex flex-wrap align-items-center gap-3">
            <span class="avatar bg-{{ $user->role_color }}-subtle text-{{ $user->role_color }}-emphasis" style="width: 56px; height: 56px; font-size: 1.25rem" aria-hidden="true">{{ $user->initials }}</span>
            <div class="me-auto">
                <h1 class="h4 fw-bold mb-1">{{ $user->name }}</h1>
                <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                    <span>{{ '@'.$user->username }}</span>
                    <span aria-hidden="true">·</span>
                    <span><i class="bi bi-envelope me-1"></i>{{ $user->email }}</span>
                    <span class="badge rounded-pill bg-{{ $user->role_color }}-subtle text-{{ $user->role_color }}-emphasis border border-{{ $user->role_color }}-subtle">{{ $user->role_label }}</span>
                    @unless ($user->is_active)
                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle">Suspended</span>
                    @endunless
                </div>
            </div>
            <x-button variant="secondary" icon="bi-arrow-left" href="{{ route('admin.users.index') }}">Back to users</x-button>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @method('PUT')
        @include('admin.users._form', ['submitLabel' => 'Save changes'])
    </form>
@endsection
