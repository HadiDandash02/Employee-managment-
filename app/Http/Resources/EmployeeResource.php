<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The JSON shape of one employee in the API (php artisan make:resource EmployeeResource).
 *
 * Only these fields leave the server: never deleted_at, updated_at, date_of_birth or the
 * internal ids (department_id). Adding a column to the table does not add it to the API.
 */
class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            // whenLoaded: only when the controller eager-loaded it, so a list never runs one query per row.
            'department' => $this->whenLoaded('department', fn () => $this->department?->name),
            // The model casts salary to a "4200.00" string; the API gives a number.
            'salary' => (float) $this->salary,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
