<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItineraryStopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => [$this->isMethod('POST') ? 'required' : 'prohibited', 'integer', 'exists:locations,id'],
            'visit_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10080'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
