<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventSignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],

            'remind_week_before' => ['sometimes', 'boolean'],
            'remind_day_before' => ['sometimes', 'boolean'],
            'remind_hour_before' => ['sometimes', 'boolean'],

            // Honeypot: bots often fill this.
            // Empty string passes (length 0), any content fails.
            'website' => ['nullable', 'string', 'max:0'],

            // Timestamp the form sets on load; bots submit instantly.
            'hp_started_at' => ['required', 'date', function ($attribute, $value, $fail) {
                try {
                    $started = CarbonImmutable::parse($value);
                } catch (\Throwable) {
                    return $fail('Invalid form token.');
                }

                // Reject if it's in the future or unreasonably old
                if ($started->isFuture() || $started->diffInHours(now()) >= 6) {
                    return $fail('Invalid form token.');
                }

                // Require at least 3 seconds on page before submit
                if ($started->diffInSeconds(now()) < 3) {
                    return $fail('Please wait a moment and try again.');
                }
            }],
        ];
    }
}
