<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    /** @use HasFactory<\Database\Factories\SkillFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Many-to-many: a skill is shared by many employees (pivot table employee_skill).
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)->withPivot('level')->withTimestamps();
    }
}
