<?php

namespace App\Events;

use App\Enums\LeaveAction;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * "Something happened to a leave request": submitted, approved, rejected or cancelled.
 * (php artisan make:event LeaveRequestStatusChanged)
 *
 * An event only CARRIES the facts; it does nothing itself. LeaveRequestService fires it after the
 * database COMMIT, and every listener reacts on its own (see app/Listeners). The service does not
 * know who listens, so a new reaction (an email, a calendar, …) is a new listener, not a service change.
 */
class LeaveRequestStatusChanged
{
    use Dispatchable;

    public function __construct(
        public LeaveRequest $leaveRequest,
        public LeaveAction $action,
        public User $by,
    ) {
    }
}
