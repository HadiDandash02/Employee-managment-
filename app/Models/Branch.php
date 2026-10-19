<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Branch extends Model
{
    /** @use HasFactory<\Database\Factories\BranchFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'city'];

    /**
     * Each branch belongs to one company (branches.company_id).
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * One branch has many departments (departments.branch_id).
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * All employees of the branch, through its departments (branch → departments → employees).
     */
    public function employees(): HasManyThrough
    {
        return $this->hasManyThrough(Employee::class, Department::class);
    }
}
