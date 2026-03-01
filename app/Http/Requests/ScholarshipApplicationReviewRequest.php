<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScholarshipApplicationReviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'score' => ['required', 'numeric', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
