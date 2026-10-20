<?php

namespace App\Enums;

/**
 * Half of a working day. A leave starts on a morning or an afternoon and ends on a morning
 * or an afternoon, so half days are possible (see App\Services\Leave\LeaveDayCounter).
 */
enum LeavePeriod: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';

    public function label(): string
    {
        return match ($this) {
            self::Morning => 'Morning',
            self::Afternoon => 'Afternoon',
        };
    }
}
