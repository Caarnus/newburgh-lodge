<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Georgia, serif;
            line-height: 1.6;
            margin: 40px;
        }
        h1 {
            text-align: center;
            font-size: 2em;
            margin-bottom: 0.5em;
        }
        .content {
            max-width: 700px;
            margin: auto;
        }
        @media print {
            body {
                background: none;
                margin: 0;
                padding: 0;
            }
            .content {
                padding: 1in;
            }
        }
    </style>
</head>
<body>
<div class="content">
    <h1>{{ $title }}</h1>
    <p>{!! nl2br(e($body)) !!}</p>
</div>
</body>
</html>
