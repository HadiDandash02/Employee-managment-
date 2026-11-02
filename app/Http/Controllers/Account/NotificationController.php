<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

/**
 * The logged-in user's notifications (the bell in the top bar).
 * Always through $request->user()->notifications(), so nobody can open someone else's.
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('account.notifications', [
            'notifications' => $request->user()->notifications()->paginate(15),
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

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Mark one notification as read without opening it.
     */
    public function markRead(Request $request, string $notification)
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Delete one notification. Only the user's own: someone else's id is a 404.
     */
    public function destroy(Request $request, string $notification)
    {
        $request->user()->notifications()->findOrFail($notification)->delete();

        return back()->with('success', 'Notification deleted.');
    }

    /**
     * Clean up: delete every notification that was already read (unread ones are kept).
     */
    public function destroyRead(Request $request)
    {
        $count = $request->user()->readNotifications()->delete();

        return back()->with('success', $count.' read '.str('notification')->plural($count).' deleted.');
    }
}
