<?php

namespace App\Models;

use App\Enums\LeaveAction;
use App\Enums\LeaveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a leave request's history (created by LeaveRequestService only).
 */
class LeaveRequestAction extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'from_status',
        'to_status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'action' => LeaveAction::class,
            'from_status' => LeaveStatus::class,
            'to_status' => LeaveStatus::class,
        ];
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
