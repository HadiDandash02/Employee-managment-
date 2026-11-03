@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    {{-- Header: title, then the buttons for everything at once (shown/hidden live by the notification script) --}}
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Notifications</h1>
            <div class="text-muted small">Leave requests sent to you and answers to yours.</div>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('notifications.read-all') }}" class="js-notification-form {{ $unreadCount ? '' : 'd-none' }}" data-action="read-all" data-show-when-unread>
                @csrf
                <x-button variant="secondary" icon="bi-check2-all" type="submit">Mark all as read</x-button>
            </form>
            <form method="POST" action="{{ route('notifications.destroy-read') }}" class="js-notification-form {{ $totalCount > $unreadCount ? '' : 'd-none' }}" data-action="delete-read" data-show-when-read
                  onsubmit="return confirm('Delete all notifications you have already read?')">
                @csrf
                @method('DELETE')
                <x-button variant="danger-outline" icon="bi-trash" type="submit">Delete read</x-button>
            </form>
        </div>
    </div>

    <div class="panel notifications-panel">
        {{-- Tabs: All / Unread, with live counts --}}
        <div class="panel-header flex-wrap">
            <ul class="nav notification-tabs">
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'all' ? 'active' : '' }}" href="{{ route('notifications.index') }}">
                        All <span class="tab-count" data-total-count>{{ $totalCount }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $filter === 'unread' ? 'active' : '' }}" href="{{ route('notifications.index', ['filter' => 'unread']) }}">
                        Unread <span class="tab-count tab-count-unread" data-unread-count>{{ $unreadCount }}</span>
                    </a>
                </li>
            </ul>

            {{-- Selection toolbar: act on one or more checked notifications --}}
            @if ($notifications->isNotEmpty())
                <div class="d-flex align-items-center gap-2 ms-auto bulk-bar" data-bulk-bar data-url="{{ route('notifications.bulk') }}">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" id="select-all-notifications" data-select-all>
                        <label class="form-check-label small text-muted" for="select-all-notifications">Select all</label>
                    </div>
                    <span class="small fw-semibold text-body d-none" data-selected-label></span>
                    <x-button variant="secondary" size="sm" icon="bi-check2" data-bulk="read" disabled>Mark as read</x-button>
                    <x-button variant="danger-outline" size="sm" icon="bi-trash" data-bulk="delete" disabled>Delete</x-button>
                </div>
            @endif
        </div>

        <div class="list-group list-group-flush" data-notification-list="page" @if ($filter === 'unread') data-only-unread @endif>
            {{-- Grouped by day: Today, Yesterday, Earlier --}}
            @php($lastGroup = null)
            @foreach ($notifications as $notification)
                @php($group = $notification->created_at?->isToday() ? 'Today' : ($notification->created_at?->isYesterday() ? 'Yesterday' : 'Earlier'))
                @if ($group !== $lastGroup)
                    <div class="notification-group">{{ $group }}</div>
                    @php($lastGroup = $group)
                @endif
                @include('partials.notification-item', ['actions' => true])
            @endforeach

            <div class="notification-empty py-5 {{ $notifications->isEmpty() ? '' : 'd-none' }}" data-notification-empty>
                <span class="notification-empty-icon"><i class="bi {{ $filter === 'unread' ? 'bi-check2-circle' : 'bi-bell-slash' }}"></i></span>
                <div class="fw-semibold">{{ $filter === 'unread' ? "You're all caught up." : 'No notifications yet.' }}</div>
                <div class="small text-muted">New leave requests and decisions will appear here.</div>
            </div>
        </div>

        @if ($notifications->hasPages())
            <div class="panel-body border-top py-2">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
