<?php

namespace App\Http\Requests\Staff;

use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends EmployeeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            // unique also counts employees in the trash (the database index does too).
            'email' => ['required', 'email', 'max:255', Rule::unique('employees', 'email')],
            // exists: the id must be a real row in the departments table.
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'salary' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'skills' => ['nullable', 'array'],
            ...$this->optionalRules(),
        ];
    }
}
