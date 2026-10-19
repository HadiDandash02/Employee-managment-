<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProfile extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeProfileFactory> */
    use HasFactory;

    protected $fillable = ['employee_id', 'phone', 'address', 'hire_date'];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
        ];
    }

    /**
     * The inverse of Employee hasOne EmployeeProfile (employee_profiles.employee_id).
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
