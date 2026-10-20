<?php

namespace App\Http\Requests\Leave;

use App\Enums\LeavePeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

    /**
     * No period chosen = full days (from the morning, until the afternoon).
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_period' => $this->input('start_period') ?: LeavePeriod::Morning->value,
            'end_period' => $this->input('end_period') ?: LeavePeriod::Afternoon->value,
        ]);
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'start_period' => ['required', Rule::enum(LeavePeriod::class)],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:'.now()->addYear()->toDateString()],
            'end_period' => ['required', Rule::enum(LeavePeriod::class)],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'The end date must be the same as or after the start date.',
            'end_date.before_or_equal' => 'A leave can be requested at most one year ahead.',
        ];
    }

    public function attributes(): array
    {
        return [
            'start_date' => 'start date',
            'start_period' => 'start (morning or afternoon)',
            'end_date' => 'end date',
            'end_period' => 'end (morning or afternoon)',
        ];
    }
}
