<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

/**
 * The logged-in user's notifications (the bell in the top bar and the Notifications page).
 * Always through $request->user()->notifications(), so nobody can open or change someone else's.
 *
 * Every action answers in two ways (respond()):
 *   - normal form (no JavaScript): back to the page with a green message;
 *   - fetch() from the notification script (Accept: application/json): JSON with the new counts,
 *     so the page updates in place without reloading.
 */
class NotificationController extends Controller
{
    /**
     * ?filter=unread shows only the unread ones (the "Unread" tab).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        return view('account.notifications', [
            'notifications' => ($filter === 'unread' ? $user->unreadNotifications() : $user->notifications())
                ->paginate(15)
                ->withQueryString(),
            'filter' => $filter,
            'unreadCount' => $user->unreadNotifications()->count(),
            'totalCount' => $user->notifications()->count(),
        ]);
    }

    /**
     * Click on a notification: mark it read and go to what it is about.
     */
    public function open(Request $request, string $notification)
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        // Build the link from the id with the reader's own prefix (/hr/…, /manager/…), never a stored URL:
        // the employee who sent the request has another prefix, and URLs may change.
        if (isset($notification->data['leave_request_id'])) {
            return redirect()->route('leave-requests.show', $notification->data['leave_request_id']);
        }

        return redirect()->to($notification->data['url'] ?? route('notifications.index'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->respond($request, 'All notifications marked as read.');
    }

    /**
     * Mark one notification as read without opening it.
     */
    public function markRead(Request $request, string $notification)
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return $this->respond($request, 'Notification marked as read.');
    }

    /**
     * Several selected notifications at once (the checkboxes on the Notifications page):
     * action=read marks them read, action=delete deletes them. Ids of other users' notifications are ignored.
     */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'in:read,delete'],
            'ids' => ['required', 'array', 'max:100'],
            'ids.*' => ['string'],
        ]);

        $selected = $request->user()->notifications()->whereKey($data['ids']);

        if ($data['action'] === 'read') {
            $count = (clone $selected)->whereNull('read_at')->update(['read_at' => now()]);
            $message = $count.' '.str('notification')->plural($count).' marked as read.';
        } else {
            $count = $selected->delete();
            $message = $count.' '.str('notification')->plural($count).' deleted.';
        }

        return $this->respond($request, $message);
    }

    /**
     * Delete one notification. Only the user's own: someone else's id is a 404.
     */
    public function destroy(Request $request, string $notification)
    {
        $request->user()->notifications()->findOrFail($notification)->delete();

        return $this->respond($request, 'Notification deleted.');
    }

    /**
     * Clean up: delete every notification that was already read (unread ones are kept).
     */
    public function destroyRead(Request $request)
    {
        $count = $request->user()->readNotifications()->delete();

        return $this->respond($request, $count.' read '.str('notification')->plural($count).' deleted.');
    }

    /**
     * JSON with the new counts for fetch(), a redirect with a green message for a normal form.
     */
    private function respond(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            $user = $request->user();

            return response()->json([
                'message' => $message,
                'unread_count' => $user->unreadNotifications()->count(),
                'total_count' => $user->notifications()->count(),
            ]);
        }

        return back()->with('success', $message);
    }
}
