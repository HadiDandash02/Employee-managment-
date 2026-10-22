<?php

namespace App\Enums;

/**
 * The status of a leave request. Stored as the string value in leave_requests.status.
 *
 * One enum instead of 'pending' / 'approved' / 'rejected' typed in many files:
 * a typo becomes an error, and the label and colour live in one place.
 */
enum LeaveStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled'; // withdrawn by the employee while it was still pending

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Bootstrap colour for the status badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'bi-hourglass-split',
            self::Approved => 'bi-check-circle',
            self::Rejected => 'bi-x-circle',
            self::Cancelled => 'bi-slash-circle',
        };
    }
}
