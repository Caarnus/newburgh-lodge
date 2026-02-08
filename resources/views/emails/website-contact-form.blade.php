<!doctype html>
<html>
<body>
    <h2>Website Contact Form Submission</h2>

    <p><strong>Name:</strong> {{ $data['name'] ?? '(not provided)' }}</p>
    <p><strong>Email:</strong> {{ $data['email'] }}</p>
    <p><strong>Phone:</strong> {{ $data['phone'] }}</p>
    <p><strong>Subject:</strong> {{ $data['subject'] }}</p>

    <hr>

    <p><strong>Message:</strong></p>
    <p>{!! nl2br(e($data['message'])) !!}</p>
</body>
</html>
