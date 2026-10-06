<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'department',
        'salary',
        'date_of_birth',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'salary' => 'decimal:2',
        ];
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

        return $colors[crc32(mb_strtolower((string) $this->department)) % count($colors)];
    }

    /**
     * Columns the employee list can be sorted by, mapped from the "sort" query value.
     */
    public const SORTABLE = [
        'id' => 'id',
        'name' => 'first_name',
        'email' => 'email',
        'department' => 'department',
        'salary' => 'salary',
        'date_of_birth' => 'date_of_birth',
        'added_on' => 'created_at',
    ];

    /**
     * Filter employees by name, email or department.
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
                    ->orWhere('department', 'like', $like);
            });
        }

        return $query;
    }

    /**
     * Apply the filter panel: department, salary range, date of birth range and "added on" range.
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
            ->when($text('department') !== '', fn ($q) => $q->where('department', $text('department')))
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
        $query->orderBy(self::SORTABLE[$sort], $direction);

        if ($sort === 'name') {
            $query->orderBy('last_name', $direction);
        }

        return $query->orderBy('id', $direction);
    }
}
