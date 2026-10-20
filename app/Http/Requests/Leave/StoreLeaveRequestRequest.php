<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Basic form validation: is the input well-formed?
 * Rules that depend on other data (overlapping requests, …) are business rules: LeaveRequestService.
 */
class StoreLeaveRequestRequest extends FormRequest
{
    /**
     * The route checks LeaveRequestPolicy::create, so this only validates.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'The end date must be the same as or after the start date.',
        ];
    }

    public function attributes(): array
    {
        return [
            'start_date' => 'start date',
            'end_date' => 'end date',
        ];
    }
}
