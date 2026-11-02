@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    @php($hasUnread = auth()->user()->unreadNotifications()->exists())
    @php($hasRead = auth()->user()->readNotifications()->exists())

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 fw-bold mb-0">Notifications</h1>
            <div class="text-muted small">Leave requests sent to you and answers to yours.</div>
        </div>
        <div class="d-flex gap-2">
            @if ($hasUnread)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-button variant="secondary" icon="bi-check2-all" type="submit">Mark all as read</x-button>
                </form>
            @endif
            @if ($hasRead)
                <form method="POST" action="{{ route('notifications.destroy-read') }}" onsubmit="return confirm('Delete all notifications you have already read?')">
                    @csrf
                    @method('DELETE')
                    <x-button variant="danger-outline" icon="bi-trash" type="submit">Delete read</x-button>
                </form>
            @endif
        </div>
    </div>

    <div class="panel" style="max-width: 48rem">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                @include('partials.notification-item', ['actions' => true])
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
