<p>A member's contact information was updated.</p>

<ul>
    <li><strong>Member:</strong> {{ $person->display_name ?: 'Person #'.$person->id }}</li>
    <li><strong>Member #:</strong> {{ $person->memberProfile?->member_number ?: 'N/A' }}</li>
    <li><strong>Updated By:</strong> {{ $actor->name ?: $actor->email }}</li>
    <li><strong>Updated At:</strong> {{ now()->toDayDateTimeString() }}</li>
</ul>

<p><strong>Changed Fields</strong></p>
<ul>
    @foreach($formattedChanges as $change)
        <li>
            <strong>{{ $change['label'] }}:</strong>
            "{{ $change['before'] ?? '—' }}" -> "{{ $change['after'] ?? '—' }}"
        </li>
    @endforeach
</ul>

<p><a href="{{ $profileUrl }}">View member profile</a></p>

