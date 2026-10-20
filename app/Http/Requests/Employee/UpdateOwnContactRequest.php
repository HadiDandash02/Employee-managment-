<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

/**
 * What an employee may change about themselves on "My Profile": phone and address only.
 * Any other field in the request (salary, department_id, …) is not validated, so it never reaches the database.
 */
class UpdateOwnContactRequest extends FormRequest
{
    /**
     * The controller checks EmployeePolicy::updateContact, so this only validates.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number may only contain digits, spaces, +, - and brackets.',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'phone number',
        ];
    }
}
