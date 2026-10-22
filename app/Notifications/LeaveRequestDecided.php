<?php

namespace App\Notifications;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use Illuminate\Notifications\Notification;

/**
 * To the employee: "Your leave … was approved by Mona" (or rejected, with the reason).
 */
class LeaveRequestDecided extends Notification
{
    public function __construct(
        private LeaveRequest $leaveRequest,
        private ?string $comment = null,
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
        $status = $leave->status;
        $word = strtolower($status->label());

        return [
            'title' => "Leave request {$word}",
            'message' => "Your leave {$leave->period_text} ({$leave->days_label}) was {$word} by {$leave->approver?->name}."
                .($this->comment ? " Comment: {$this->comment}" : ''),
            'icon' => $status === LeaveStatus::Approved ? 'bi-check-circle' : 'bi-x-circle',
            'color' => $status->color(),
            'url' => route('leave-requests.show', $leave, false),
            'leave_request_id' => $leave->id,
        ];
    }
}
