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
        <h2 class="heading-2">Routine keeper</h2>
        <p>A metronome for the practicing musician</p>
    </header>

    <main class="metronome"
        x-data="routinePlayer([
            {
                name: 'Alternate Picking',
                bpm: 100,
                mode: 'timer',
                duration_seconds: 5
            },
            {
                name: 'Legato',
                bpm: 80,
                mode: 'timer',
                duration_seconds: 5
            },
            {
                name: 'Sweep Picking',
                bpm: 90,
                mode: 'timer',
                duration_seconds: null
            }
        ])"
    >
        <x-metronome.main />

        <x-metronome.panel />
    </main>
</body>
</html>