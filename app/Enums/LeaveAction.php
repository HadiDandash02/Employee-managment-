<?php

namespace App\Enums;

/**
 * What happened to a leave request: one row in leave_request_actions (the history).
 */
enum LeaveAction: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Submitted => 'bi-send',
            self::Approved => 'bi-check-circle-fill text-success',
            self::Rejected => 'bi-x-circle-fill text-danger',
            self::Cancelled => 'bi-slash-circle text-secondary',
        };
    }
}
