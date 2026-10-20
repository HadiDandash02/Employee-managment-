<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'department_id',
        'salary',
        'base_leave_days',
        'date_of_birth',
    ];

    /**
     * Leave days every employee starts the year with (config/leave.php; HR can change it per employee).
     * The monthly 1.25 days come on top: see App\Services\Leave\LeaveBalanceService.
     */
    protected $attributes = [
        'base_leave_days' => 3,
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'salary' => 'decimal:2',
            'base_leave_days' => 'float',
        ];
    }

    /**
     * Each employee belongs to one department (employees.department_id, restrict on delete).
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * One-to-one: extra contact/employment details in employee_profiles.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    /**
     * Many-to-many through the employee_skill pivot table, with the skill level on the pivot.
     */
    /**
     * The login account of this employee, if they have one (users.employee_id).
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('level')->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(mb_substr((string) $this->first_name, 0, 1).mb_substr((string) $this->last_name, 0, 1));
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /**
     * Bootstrap colour for the initials avatar.
     */
    public function getAvatarColorAttribute(): string
    {
        $colors = ['primary', 'success', 'danger', 'warning', 'info', 'secondary', 'dark'];

        return $colors[($this->id ?? 0) % count($colors)];
    }

    /**
     * Bootstrap colour for the department badge; a department always gets the same colour.
     */
    public function getDepartmentColorAttribute(): string
    {
        $colors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];

        return $colors[crc32(mb_strtolower((string) $this->department?->name)) % count($colors)];
    }

    /**
     * Columns the employee list can be sorted by, mapped from the "sort" query value.
     */
    public const SORTABLE = [
        'id' => 'id',
        'name' => 'first_name',
        'email' => 'email',
        'department' => 'department_id', // sorted by the department's name, see scopeSortBy()
        'salary' => 'salary',
        'hire_date' => 'hire_date', // on employee_profiles, see scopeSortBy()
        'date_of_birth' => 'date_of_birth',
        'added_on' => 'created_at',
    ];

    /**
     * Filter employees by name, email or department name.
     *
     * Every word must match one of the columns, so "jane doe" or "doe sales" also work.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $words = preg_split('/\s+/', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';

            $query->where(function (Builder $q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('department', fn (Builder $d) => $d->where('name', 'like', $like));
            });
        }

        return $query;
    }

    /**
     * Only the employees this user may see (the list version of EmployeePolicy::view()):
     * all of them (HR, Admin), their own department (Manager), or only themselves.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasPermission('employees.view-all')) {
            return $query;
        }

        if ($user->hasPermission('employees.view-team')) {
            $department = $user->employee?->department_id;

            // A manager whose login is not linked to an employee has no department: they see no one.
            return $department ? $query->where('department_id', $department) : $query->whereRaw('1 = 0');
        }

        return $query->whereKey($user->employee_id ?? 0);
    }

    /**
     * Apply the filter panel: department (id), branch (id), salary range, date of birth range and "added on" range.
     * Empty or invalid values are ignored.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $text = fn (string $key) => is_string($filters[$key] ?? null) ? trim($filters[$key]) : '';
        $number = fn (string $key) => is_numeric($filters[$key] ?? null) ? (float) $filters[$key] : null;
        $date = function (string $key) use ($filters): ?string {
            $value = $filters[$key] ?? null;
            $parsed = is_string($value) ? \DateTime::createFromFormat('!Y-m-d', $value) : false;

            return $parsed && $parsed->format('Y-m-d') === $value ? $value : null;
        };

        return $query
            ->when(ctype_digit($text('department_id')), fn ($q) => $q->where('department_id', (int) $text('department_id')))
            ->when(ctype_digit($text('branch_id')), fn ($q) => $q->whereHas('department', fn ($d) => $d->where('branch_id', (int) $text('branch_id'))))
            ->when($number('salary_min') !== null, fn ($q) => $q->where('salary', '>=', $number('salary_min')))
            ->when($number('salary_max') !== null, fn ($q) => $q->where('salary', '<=', $number('salary_max')))
            ->when($date('born_from'), fn ($q, $d) => $q->whereDate('date_of_birth', '>=', $d))
            ->when($date('born_to'), fn ($q, $d) => $q->whereDate('date_of_birth', '<=', $d))
            ->when($date('added_from'), fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($date('added_to'), fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
    }

    /**
     * Sort by one of the SORTABLE keys; unknown keys fall back to newest first.
     */
    public function scopeSortBy(Builder $query, ?string $sort, ?string $direction): Builder
    {
        if (! isset(self::SORTABLE[$sort])) {
            return $query->latest()->orderByDesc('id');
        }

        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if ($sort === 'department') {
            $query->orderBy(Department::select('name')->whereColumn('departments.id', 'employees.department_id'), $direction);
        } elseif ($sort === 'hire_date') {
            $query->orderBy(EmployeeProfile::select('hire_date')->whereColumn('employee_profiles.employee_id', 'employees.id'), $direction);
        } else {
            $query->orderBy(self::SORTABLE[$sort], $direction);
        }

        if ($sort === 'name') {
            $query->orderBy('last_name', $direction);
        }

        return $query->orderBy('id', $direction);
    }
}
