<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pulse</title>

    @vite(['resources/styles/main.scss', 'resources/js/app.js'])
    
    <style> [x-cloak] { display: none !important; } </style>
</head>
<body>

    <header class="main-heading">
        <h2 class="heading-2" style="text-transform: lowercase; font-weight: normal;">focuus</h2>
        <p>A practice timer for focused musicians</p>
    </header>

    <main class="metronome" x-data="routinePlayer()">
        <x-metronome.main />

        <x-metronome.panel />
    </main>
</body>
</html>