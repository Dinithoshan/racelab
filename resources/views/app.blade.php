<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('vendor/racelab/app.css') }}">
    <title>RaceLab</title>
</head>
<body>
    <div id="react-app"></div>

    <!-- Include compiled Racelab JS (publish `dist` to public/vendor/racelab with `php artisan vendor:publish --tag=racelab-assets`) -->
    <script src="{{ asset('vendor/racelab/app.js') }}" defer></script>
</body>
</html>