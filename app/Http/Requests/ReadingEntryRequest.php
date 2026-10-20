<?php

namespace App\Http\Requests;

use App\Models\ReadingEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReadingEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'status' => [$this->isMethod('POST') ? 'required' : 'sometimes', Rule::in(array_keys(ReadingEntry::STATUSES))],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'rating' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];

        if ($this->isMethod('POST')) {
            $rules['book_id'] = ['required', 'integer', 'exists:books,id'];
        }

        return $rules;
    }
}
