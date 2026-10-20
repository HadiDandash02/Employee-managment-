<?php

namespace App\Http\Requests\Staff;

use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends EmployeeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * sometimes: a field is only validated when it is sent, so a partial update
     *            (e.g. only the salary) is allowed.
     * filled:    but when a field is sent, it may not be empty.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'filled', 'string', 'max:100'],
            'last_name' => ['sometimes', 'filled', 'string', 'max:100'],
            'email' => ['sometimes', 'filled', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($this->route('employee'))],
            'department_id' => ['sometimes', 'filled', 'integer', 'exists:departments,id'],
            'salary' => ['sometimes', 'filled', 'numeric', 'min:0', 'max:99999999.99'],
            'date_of_birth' => ['sometimes', 'filled', 'date', 'before:today'],
            // The edit form always sends "skills" (empty when no box is ticked) so skills can be cleared.
            'skills' => ['sometimes', 'nullable', 'array'],
            ...$this->optionalRules(),
        ];
    }
}
