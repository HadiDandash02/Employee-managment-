<?php

namespace App\Listeners;

use App\Events\LeaveRequestStatusChanged;
use Illuminate\Support\Facades\Log;

/**
 * Audit log: writes every leave request action to storage/logs/laravel.log.
 * (php artisan make:listener LogLeaveRequestActivity --event=LeaveRequestStatusChanged)
 *
 * Nothing registers this class: Laravel finds every listener in app/Listeners by the event
 * type-hinted in handle(). Check it with: php artisan event:list
 */
class LogLeaveRequestActivity
{
    public function handle(LeaveRequestStatusChanged $event): void
    {
        $leave = $event->leaveRequest;

        Log::info("Leave request #{$leave->id} {$event->action->value} by {$event->by->name}", [
            'leave_request_id' => $leave->id,
            'employee_id' => $leave->employee_id,
            'action' => $event->action->value,
            'status' => $leave->status->value,
            'days' => $leave->days,
            'by_user_id' => $event->by->id,
        ]);
    }
}
