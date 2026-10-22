<?php

namespace App\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The comment typed when approving (optional) or rejecting (required: the employee should know why).
 */
class DecideLeaveRequestRequest extends FormRequest
{
    /**
     * The route checks LeaveRequestPolicy::approve, so this only validates.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => [$this->routeIs('leave-requests.reject') ? 'required' : 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Please write why the request is rejected.',
        ];
    }
}
