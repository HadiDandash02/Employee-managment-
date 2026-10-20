@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-0">Notifications</h1>
            <div class="text-muted small">Leave requests sent to you and answers to yours.</div>
        </div>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-soft"><i class="bi bi-check2-all me-1"></i> Mark all as read</button>
            </form>
        @endif
    </div>

    <div class="panel" style="max-width: 48rem">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                @include('partials.notification-item')
            @empty
                <div class="panel-body text-center text-muted py-5">
                    <i class="bi bi-bell-slash fs-2 d-block mb-1 opacity-50"></i>
                    No notifications yet.
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="panel-body border-top py-2">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
