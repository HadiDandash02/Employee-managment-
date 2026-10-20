{{-- One notification: icon, title, message, time. Unread ones are highlighted. Needs $notification. --}}
@php($data = $notification->data)
<a href="{{ route('notifications.open', $notification->id) }}"
   class="list-group-item list-group-item-action d-flex gap-2 px-3 py-2 {{ $notification->read_at ? '' : 'notification-unread' }}">
    <span class="notification-icon bg-{{ $data['color'] ?? 'secondary' }}-subtle text-{{ $data['color'] ?? 'secondary' }}-emphasis">
        <i class="bi {{ $data['icon'] ?? 'bi-bell' }}"></i>
    </span>
    <span class="min-w-0">
        <span class="d-block small fw-semibold text-body">{{ $data['title'] ?? 'Notification' }}</span>
        <span class="d-block small text-muted notification-message">{{ $data['message'] ?? '' }}</span>
        <span class="d-block text-muted" style="font-size: .72rem">{{ $notification->created_at?->diffForHumans() }}</span>
    </span>
    @unless ($notification->read_at)
        <span class="notification-dot ms-auto flex-shrink-0" aria-label="Unread"></span>
    @endunless
</a>
