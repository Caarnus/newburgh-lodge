@php
$local = $occurrenceStartUtc->timezone($timezone);
$tz = IntlTimeZone::createTimeZone($timezone);
$locale = 'en_US';
$abbr = $tz->getDisplayName(false, IntlTimeZone::DISPLAY_SHORT_GENERIC, $locale);

$startsIn = match ($reminderType) {
    'week' => 'in 1 week',
    'day' => 'in 1 day',
    'hour' => 'in 1 hour',
    default => 'soon',
};
@endphp

@component('mail::message')
<h1 style="text-align:center;">{{ $eventTitle }}</h1>

<p style="text-align:left;">
This is a reminder that <strong>{{ $eventTitle }}</strong> starts {{ $startsIn }}.
</p>

<p style="text-align:left;">
{{ $eventDescription }}
</p>

<p style="text-align:center;">
<strong>When:</strong> {{ $local->format('l, F j, Y \a\t g:i A') }} ({{ $abbr }})
@if(!empty($location))
<br><strong>Where:</strong> {{ $location }}
@endif
</p>

@component('mail::button', ['url' => $manageUrl])
Manage reminders
@endcomponent

<p style="text-align:left; margin-top: 24px;">
Thanks,<br>
{{ config('app.name') }}
</p>

<p style="text-align:center; font-size: 10px;">
    If you no longer want reminders for this event, you can remove your signup: <a href="{{ $unsubscribeUrl }}">Remove my signup</a>
</p>
@endcomponent
