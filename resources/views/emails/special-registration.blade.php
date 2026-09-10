<!doctype html>
<html><body>
<h2>{{ $registration->form === 'open-air-2026' ? 'Open Air Degree' : 'Golf Scramble' }} registration</h2>
<p><strong>Type:</strong> {{ $registration->registration_type ? str_replace('_', ' ', $registration->registration_type) : 'Attendee' }}</p>
<p><strong>Name:</strong> {{ $registration->name ?: '(team registration)' }}</p>
<p><strong>Email:</strong> {{ $registration->email }}</p>
<p><strong>Phone:</strong> {{ $registration->phone }}</p>
@if($registration->team_name)<p><strong>Team:</strong> {{ $registration->team_name }}</p>@endif
@if($registration->team_members)<p><strong>Members:</strong></p><ul>@foreach($registration->team_members as $member)<li>{{ $member }}</li>@endforeach</ul>@endif
</body></html>
