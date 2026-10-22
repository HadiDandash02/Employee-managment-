<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Notification;

/**
 * To the approvers (Manager of the department, HR): "Sam asked for 2.5 days".
 * Stored in the notifications table and shown under the bell in the top bar.
 */
class LeaveRequestSubmitted extends Notification
{
    public function __construct(
        private LeaveRequest $leaveRequest,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $leave = $this->leaveRequest;

        return [
            'title' => 'New leave request',
            'message' => "{$leave->employee->full_name} asked for {$leave->days_label}: {$leave->period_text}.",
            'icon' => 'bi-calendar-plus',
            'color' => 'warning',
            // Opened through NotificationController::open(), which builds the link with the reader's prefix.
            'leave_request_id' => $leave->id,
        ];
    }
}
