{{-- One notification: icon, title, message, time. Unread ones are highlighted. Needs $notification.
     $actions = true   (the Notifications page): a checkbox to select it, plus "Mark as read" and "Delete";
     $actions = 'read' (the bell in the top bar): only "Mark as read".
     The forms work without JavaScript (normal POST + redirect); with JavaScript they are sent by fetch()
     and the page updates in place (the notification script in layouts/app.blade.php). --}}
@php($data = $notification->data)
@php($actions = $actions ?? false)
@php($color = $data['color'] ?? 'secondary')
<div class="list-group-item notification-row d-flex align-items-start gap-2 px-3 py-3 {{ $notification->read_at ? '' : 'notification-unread' }}"
     data-notification-id="{{ $notification->id }}" data-read="{{ $notification->read_at ? 1 : 0 }}">
    @if ($actions === true)
        <input class="form-check-input mt-2 flex-shrink-0 js-notification-check" type="checkbox" value="{{ $notification->id }}"
               aria-label="Select this notification">
    @endif

    <a href="{{ route('notifications.open', $notification->id) }}" class="d-flex gap-3 flex-grow-1 min-w-0 text-decoration-none notification-link">
        <span class="notification-icon bg-{{ $color }}-subtle text-{{ $color }}-emphasis">
            <i class="bi {{ $data['icon'] ?? 'bi-bell' }}"></i>
        </span>
        <span class="min-w-0">
            <span class="d-flex align-items-center gap-2">
                <span class="small fw-semibold text-body">{{ $data['title'] ?? 'Notification' }}</span>
                @unless ($notification->read_at)
                    <span class="notification-dot flex-shrink-0 mt-0" aria-label="Unread"></span>
                @endunless
            </span>
            <span class="d-block small text-muted notification-message">{{ $data['message'] ?? '' }}</span>
            <span class="d-block notification-time" title="{{ $notification->created_at?->format('d M Y H:i') }}">
                <i class="bi bi-clock me-1"></i>{{ $notification->created_at?->diffForHumans() }}
            </span>
        </span>
    </a>

    @if ($actions)
        <div class="notification-actions d-flex align-items-center gap-1 flex-shrink-0">
            @unless ($notification->read_at)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="js-notification-form" data-action="read">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-soft btn-icon" title="Mark as read" aria-label="Mark as read">
                        <i class="bi bi-check2"></i>
                    </button>
                </form>
            @endunless
            @if ($actions !== 'read')
                <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" class="js-notification-form" data-action="delete"
                      onsubmit="return confirm('Delete this notification?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-soft btn-icon notification-delete" title="Delete" aria-label="Delete notification">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>
