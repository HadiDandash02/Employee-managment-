<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared by StoreEmployeeRequest and UpdateEmployeeRequest: the custom error messages
 * and the readable field names used in every message.
 */
abstract class EmployeeRequest extends FormRequest
{
    /**
     * Who may send this form is checked by the route middleware (can:employees.create / can:employees.update).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Custom error messages (instead of Laravel's generic ones).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Please enter the :attribute.',
            'filled' => 'The :attribute cannot be empty.',
            'email.email' => 'Please enter a valid email address, like name@company.com.',
            'email.unique' => 'Another employee already uses this email. If they were deleted, restore them from the trash instead.',
            'department_id.required' => 'Please choose a department.',
            'department_id.exists' => 'The selected department does not exist. Please choose one from the list.',
            'salary.min' => 'The salary cannot be negative.',
            'salary.max' => 'The salary is too large.',
            'base_leave_days.max' => 'The base leave cannot be more than 60 days.',
            'base_leave_days.multiple_of' => 'The base leave must be in whole or half days (e.g. 3 or 3.5).',
            'date_of_birth.before' => 'The date of birth must be in the past.',
            'hire_date.before_or_equal' => 'The hire date cannot be in the future.',
            'skills.array' => 'Please choose skills from the list.',
            'skills.*.exists' => 'One of the selected skills does not exist.',
            'skills.*.distinct' => 'A skill was selected twice.',
        ];
    }

    /**
     * Readable names for the fields, used as :attribute in every message.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'email' => 'email address',
            'department_id' => 'department',
            'salary' => 'annual salary',
            'base_leave_days' => 'base leave days',
            'date_of_birth' => 'date of birth',
            'phone' => 'phone number',
            'address' => 'address',
            'hire_date' => 'hire date',
            'skills' => 'skills',
            'skills.*' => 'skill',
        ];
    }

    /**
     * Rules shared by create and update for the optional profile fields and skills.
     * nullable: the field may be left empty (it is stored as NULL).
     *
     * @return array<string, array<mixed>>
     */
    protected function optionalRules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            // Left out = keep it (or 3 for a new employee); sent = a number of whole or half days.
            'base_leave_days' => ['sometimes', 'filled', 'numeric', 'min:0', 'max:60', 'multiple_of:0.5'],
            'skills.*' => ['integer', 'distinct', 'exists:skills,id'],
        ];
    }
}
