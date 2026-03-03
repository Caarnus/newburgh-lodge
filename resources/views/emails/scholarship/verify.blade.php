@component('mail::message')
# Verify your scholarship application

Hi {{ $application->first_name }},

Please verify your email address to submit your scholarship application.

@component('mail::button', ['url' => $verifyUrl])
    Verify Application
@endcomponent

This link will expire in 48 hours.

If you did not request this, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
