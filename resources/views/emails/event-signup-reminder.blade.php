@php
    $local = $occurrenceStartUtc->timezone($timezone);
@endphp

@component('mail::message')
    # {{ $eventTitle }}

    This is a reminder that **{{ $eventTitle }}** starts {{ match($reminderType) {
    'week' => 'in 1 week',
    'day' => 'in 1 day',
    'hour' => 'in 1 hour',
    default => 'soon',
} }}.

    **When:** {{ $local->format('l, F j, Y \a\t g:i A') }} ({{ $timezone }})
    @if(!empty($location))
        **Where:** {{ $location }}
    @endif

    @component('mail::button', ['url' => $manageUrl])
        Manage reminders
    @endcomponent

    If you no longer want reminders for this event, you can remove your signup:

    @component('mail::button', ['url' => $unsubscribeUrl])
        Remove my signup
    @endcomponent

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
