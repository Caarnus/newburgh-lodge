@php
$local = $occurrenceStartUtc->timezone($timezone);
$tz = IntlTimeZone::createTimeZone($timezone);
$locale = 'en_US';
$abbr = $tz->getDisplayName(false, IntlTimeZone::DISPLAY_SHORT_GENERIC, $locale);
$startsIn = match ($reminderType) {
    'week' => 'in 1 week',
    'day' => 'in 1 day',
    default => 'soon',
};
@endphp

@component('mail::message')
<h1 style="text-align:center;">{{ $eventTitle }}</h1>

<p style="text-align:left;">
This is a volunteer reminder for <strong>{{ $eventTitle }}</strong>, starting {{ $startsIn }}.
</p>

<p style="text-align:center;">
<strong>When:</strong> {{ $local->format('l, F j, Y \a\t g:i A') }} ({{ $abbr }})
@if(!empty($location))
<br><strong>Where:</strong> {{ $location }}
@endif
</p>

@if(!empty($eventDescription))
<p style="text-align:left;">
{{ $eventDescription }}
</p>
@endif

<p style="text-align:left; margin-top: 16px;">
<strong>Your volunteer roles/time slots:</strong>
</p>

<ul>
@foreach($assignments as $assignment)
    <li>{{ $assignment['role_title'] }}: {{ $assignment['starts_at'] }} - {{ $assignment['ends_at'] }}</li>
@endforeach
</ul>

@if(!empty($signupUrl))
@component('mail::button', ['url' => $signupUrl])
View Volunteer Signup
@endcomponent
@endif

<p style="text-align:left; margin-top: 24px;">
Thanks,<br>
{{ config('app.name') }}
</p>
@endcomponent
