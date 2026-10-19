<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<\Database\Factories\DepartmentFactory> */
    use HasFactory;

    protected $fillable = ['branch_id', 'name'];

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
}
