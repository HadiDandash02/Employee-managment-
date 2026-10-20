<?php

namespace App\Models;

use App\Enums\LeavePeriod;
use App\Enums\LeaveStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    /** @use HasFactory<\Database\Factories\LeaveRequestFactory> */
    use HasFactory;

    /**
     * Only what the employee types (+ days, counted by the service). status, approved_by and
     * approved_at are set by App\Services\Leave\LeaveRequestService, never from a form.
     */
    protected $fillable = [
        'start_date',
        'start_period',
        'end_date',
        'end_period',
        'days',
        'reason',
    ];

    protected $attributes = [
        'status' => 'pending',
        'start_period' => 'morning',
        'end_period' => 'afternoon',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'start_period' => LeavePeriod::class,
            'end_period' => LeavePeriod::class,
            'days' => 'float',
            'approved_at' => 'datetime',
            'status' => LeaveStatus::class,
        ];
    }

    /**
     * withTrashed(): a request still shows its employee after they were moved to the trash.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * The user who approved or rejected it (leave_requests.approved_by).
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * The history, oldest first.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(LeaveRequestAction::class)->oldest()->orderBy('id');
    }

    public function isPending(): bool
    {
        return $this->status === LeaveStatus::Pending;
    }

    /**
     * Is this the user's own request (their login is linked to the employee who asked)?
     */
    public function isOwnedBy(User $user): bool
    {
        return $user->employee_id !== null && $user->employee_id === $this->employee_id;
    }

    /**
     * "07 Oct 2026 (afternoon) → 09 Oct 2026 (morning)", "07 Oct 2026, morning", "07 Oct 2026".
     * Only half days are spelled out; a full day needs no period.
     */
    public function getPeriodTextAttribute(): string
    {
        $start = $this->start_date->format('d M Y');

        if ($this->start_date->equalTo($this->end_date)) {
            return match (true) {
                $this->start_period === LeavePeriod::Afternoon => "{$start}, afternoon",
                $this->end_period === LeavePeriod::Morning => "{$start}, morning",
                default => $start,
            };
        }

        $from = $start.($this->start_period === LeavePeriod::Afternoon ? ' (afternoon)' : '');
        $to = $this->end_date->format('d M Y').($this->end_period === LeavePeriod::Morning ? ' (morning)' : '');

        return "{$from} → {$to}";
    }

    /**
     * "2.5" or "3": the days without a useless ".0".
     */
    public function getDaysTextAttribute(): string
    {
        return rtrim(rtrim(number_format($this->days, 1), '0'), '.');
    }

    /**
     * "1 day", "1.5 days", "0.5 days" (str()->plural() would round 1.5 down to "day").
     */
    public function getDaysLabelAttribute(): string
    {
        return $this->days_text.($this->days == 1 ? ' day' : ' days');
    }

    /**
     * Each half day as a sortable string: "2026-10-07-1" (morning) < "2026-10-07-2" (afternoon) < "2026-10-08-1".
     * Two leaves overlap when each one starts before the other ends (LeaveRequestService::submit()).
     */
    public static function halfDaySlot(string $date, LeavePeriod $period): string
    {
        return $date.($period === LeavePeriod::Afternoon ? '-2' : '-1');
    }

    public function startSlot(): string
    {
        return self::halfDaySlot($this->start_date->toDateString(), $this->start_period);
    }

    public function endSlot(): string
    {
        return self::halfDaySlot($this->end_date->toDateString(), $this->end_period);
    }

    /**
     * Only the requests this user may see (the list version of LeaveRequestPolicy::view()):
     * all of them (HR, Admin), their department's plus their own (Manager), or only their own (Employee).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('leave.approve') && $user->hasPermission('employees.view-all')) {
            return $query;
        }

        $department = $user->employee?->department_id;

        if ($user->hasPermission('leave.approve') && $user->hasPermission('employees.view-team') && $department) {
            return $query->where(fn (Builder $q) => $q
                ->whereHas('employee', fn (Builder $e) => $e->where('department_id', $department))
                ->orWhere('employee_id', $user->employee_id));
        }

        return $query->where('employee_id', $user->employee_id ?? 0);
    }

    /**
     * Requests of one employee whose dates overlap start–end (both ends included).
     */
    public function scopeOverlapping(Builder $query, int $employeeId, string $start, string $end): Builder
    {
        return $query->where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);
    }
}
