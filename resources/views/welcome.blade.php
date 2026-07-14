<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>DoreLog - Save your drills. Keep your tempo.</title>

    <meta name="title" content="DoreLog - The metronome that remembers your drills.">
    <meta name="description" content="Save your exercises, BPMs, and practice routines so you can pick up exactly where you left off.">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://dorelog.com/">
    <meta property="og:title" content="DoreLog - Save your drills. Keep your tempo.">
    <meta property="og:description" content="Save your exercises, BPMs, and practice routines so you can pick up exactly where you left off.">
    <meta property="og:image" content="https://dorelog.com/og-image.png">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://dorelog.com/">
    <meta name="twitter:title" content="DoreLog - Save your drills. Keep your tempo.">
    <meta name="twitter:description" content="Save your exercises, BPMs, and practice routines so you can pick up exactly where you left off.">
    <meta name="twitter:image" content="https://dorelog.com/og-image.png">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta
        name="product-events-endpoint"
        content="{{ route('analytics.events.store') }}"
    >
    @vite(['resources/styles/main.scss', 'resources/js/app.js'])
    
    <style> [x-cloak] { display: none !important; } </style>
</head>
<body>

    <header class="main-heading">
        <h2 class="heading-2" style="text-transform: lowercase; font-weight: normal;">dorelog</h2>
        <p>Save your drills. Keep your tempo.</p>
    </header>

    <main class="metronome | container"
        data-type="wide"
        data-spacing="none"
        x-data="routinePlayer()"
        @keydown.window="handleKeydown($event)"
    >
        <x-metronome.main-metro />

        <x-metronome.panel />

        <x-windows.confirm-modal />
    </main>
</body>
</html>