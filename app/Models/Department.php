<?php

namespace App\Models;

use App\Support\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    /** @use HasFactory<\Database\Factories\DepartmentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['branch_id', 'name', 'code', 'description'];

    /**
     * Each department belongs to one branch (departments.branch_id).
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * One department has many employees (employees.department_id).
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * "Company · Branch" label, used to group departments in dropdowns.
     */
    public function getLocationAttribute(): string
    {
        return trim(($this->branch?->company?->name ?? '').' · '.($this->branch?->name ?? ''), ' ·');
    }

    /**
     * Search every column of the departments list: name, code, description, branch (name, city) and company.
     * Every word must match one of the columns, so "sales tripoli" finds Sales in the Tripoli branch.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        foreach (Search::words($term) as $word) {
            $like = Search::like($word);

            $query->where(fn (Builder $q) => $q
                ->where('departments.name', 'like', $like)
                ->orWhere('departments.code', 'like', $like)
                ->orWhere('departments.description', 'like', $like)
                ->orWhereHas('branch', fn (Builder $b) => $b
                    ->where('name', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhereHas('company', fn (Builder $c) => $c->where('name', 'like', $like))));
        }

        return $query;
    }
}
