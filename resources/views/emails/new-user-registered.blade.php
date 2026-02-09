<p>A new user has registered on the Newburgh Lodge #174 website.</p>

<ul>
    <li><strong>Name:</strong> {{ $user->name }}</li>
    <li><strong>Email:</strong> {{ $user->email }}</li>
    <li><strong>Registered:</strong> {{ optional($user->created_at)->toDayDateTimeString() }}</li>
</ul>
