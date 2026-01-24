<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ route('racelab.assets', ['file' => 'app.css']) }}">
    <title>RaceLab</title>
</head>
<body>
    <div id="react-app"></div>

    <!-- Include compiled Racelab JS (served directly from package dist folder) -->
    <script src="{{ route('racelab.assets', ['file' => 'app.js']) }}" defer></script>
</body>
</html>