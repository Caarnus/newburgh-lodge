<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventSignupPreferencesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'remind_week_before' => ['required', 'boolean'],
            'remind_day_before' => ['required', 'boolean'],
            'remind_hour_before' => ['required', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
