<?php

namespace App\Models;

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
     * Only what the employee types. status, approved_by and approved_at are set by
     * App\Services\Leave\LeaveRequestService, never from a form.
     */
    protected $fillable = [
        'start_date',
        'end_date',
        'reason',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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
     * Number of calendar days, both ends included (1–3 March = 3 days).
     */
    public function getDaysAttribute(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
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
