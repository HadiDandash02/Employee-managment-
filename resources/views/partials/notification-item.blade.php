{{-- One notification: icon, title, message, time. Unread ones are highlighted. Needs $notification.
     $actions = true   (the Notifications page) adds the "Mark as read" and "Delete" buttons;
     $actions = 'read' (the bell in the top bar) adds only "Mark as read". --}}
@php($data = $notification->data)
@php($actions = $actions ?? false)
<div class="list-group-item list-group-item-action notification-row d-flex align-items-start gap-2 px-3 py-2 {{ $notification->read_at ? '' : 'notification-unread' }}">
    <a href="{{ route('notifications.open', $notification->id) }}" class="d-flex gap-2 flex-grow-1 min-w-0 text-decoration-none">
        <span class="notification-icon bg-{{ $data['color'] ?? 'secondary' }}-subtle text-{{ $data['color'] ?? 'secondary' }}-emphasis">
            <i class="bi {{ $data['icon'] ?? 'bi-bell' }}"></i>
        </span>
        <span class="min-w-0">
            <span class="d-block small fw-semibold text-body">{{ $data['title'] ?? 'Notification' }}</span>
            <span class="d-block small text-muted notification-message">{{ $data['message'] ?? '' }}</span>
            <span class="d-block text-muted" style="font-size: .72rem">{{ $notification->created_at?->diffForHumans() }}</span>
        </span>
    </a>

    @if ($actions)
        <div class="notification-actions d-flex align-items-center gap-1 flex-shrink-0">
            @unless ($notification->read_at)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-soft btn-icon" data-bs-toggle="tooltip" title="Mark as read" aria-label="Mark as read">
                        <i class="bi bi-check2"></i>
                    </button>
                </form>
            @endunless
            @if ($actions !== 'read')
                <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" onsubmit="return confirm('Delete this notification?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-soft btn-icon notification-delete" data-bs-toggle="tooltip" title="Delete" aria-label="Delete notification">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            @endif
        </div>
    @elseif (! $notification->read_at)
        <span class="notification-dot ms-auto flex-shrink-0" aria-label="Unread"></span>
    @endif
</div>
