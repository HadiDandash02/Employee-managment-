<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating and editing a department.
 */
class DepartmentRequest extends FormRequest
{
    /**
     * Who may send this form is checked by the route middleware (can:departments.manage).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Codes are stored in capitals: "eng-1" becomes "ENG-1".
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The unique rules also count soft-deleted departments, like the database's unique indexes do.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $department = $this->route('department');

        return [
            'name' => [
                'required', 'string', 'max:100',
                // unique per branch: two branches may both have "Sales", one branch may not have it twice
                Rule::unique('departments', 'name')->where('branch_id', $this->input('branch_id'))->ignore($department),
            ],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('departments', 'code')->ignore($department)],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Please enter the :attribute.',
            'name.unique' => 'This branch already has a department with this name.',
            'code.unique' => 'Another department already uses this code.',
            'code.alpha_dash' => 'The code may only contain letters, numbers, dashes and underscores (e.g. ENG-1).',
            'branch_id.required' => 'Please choose a branch.',
            'branch_id.exists' => 'The selected branch does not exist. Please choose one from the list.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'department name',
            'code' => 'department code',
            'branch_id' => 'branch',
            'description' => 'description',
        ];
    }
}
