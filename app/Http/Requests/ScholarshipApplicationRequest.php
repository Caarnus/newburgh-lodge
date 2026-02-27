<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ScholarshipApplicationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Applicant
            'first_name' => ['required','string','max:60'],
            'last_name' => ['required','string','max:60'],
            'email' => ['required', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:30'],
            'siblings' => ['required', 'integer', 'min:0', 'max:50'],

            // Residency
            'is_warrick_resident' => ['required', 'accepted'],
            'address1' => ['required','string','max:120'],
            'address2' => ['nullable','string','max:120'],
            'city' => ['required','string','max:60'],
            'state' => ['required','string','size:2'],
            'zip' => ['required','string','max:10'],
            'residency_duration' => ['nullable','string','max:20'],

            // Education
            'current_school' => ['nullable','string','max:120'],
            'education_level' => ['nullable','string','max:40'],
            'current_year' => ['nullable','string','max:40'],
            'expected_graduation' => ['nullable', 'date'],
            'planned_program' => ['nullable','string','max:120'],
            'gpa' => ['nullable','string','max:10'],
            'gpa_scale' => ['nullable','string','max:10'],

            // Activities
            'activities' => ['nullable','string','max:1000'],
            'awards' => ['nullable','string','max:1000'],
            'reason' => ['nullable','string','max:1000'],

            // Lodge Relationship
            'lodge_relationship' => ['nullable','string','max:60'],
            'lodge_relationship_detail' => ['nullable','string','max:255'],

            // Spam defense
            'hp_field' => ['nullable','string','max:0'], // honeypot must be empty
            'started_at' => ['required','integer'], // timestamp (ms) from client

            // Optional Uploads
            'attachments' => ['nullable','array','max:3'],
            'attachments.*' => ['file','max:5120','mimes:pdf,jpg,jpeg,png'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            $startedAt = (int) $this->input('started_at');
            $elapsedMs = (int) (microtime(true) * 1000) - $startedAt;
            if ($elapsedMs < 5000) {
                $v->errors()->add('reason', 'Please take a moment to review your application before submitting.');
            }
        });
    }

    public function authorize(): bool
    {
        return true;
    }
}
