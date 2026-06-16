<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>focuus</title>
    <meta name="title" content="If you practice every day... this is for YOU">
    <meta property="og:description" content="A focused practice tool for musicians with a metronome, timer, and exercises to help you stay consistent every day.">
    
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://focuus.coelloweb.dev/">
    <meta property="og:title" content="If you practice every day... this is for YOU">
    <meta property="og:description" content="A focused practice tool for musicians with a metronome, timer, and exercises to help you stay consistent every day.">
    <meta property="og:image" content="https://focuus.coelloweb.dev/og-image.png">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://focuus.coelloweb.dev/">
    <meta name="twitter:title" content="If you practice every day... this is for YOU">
    <meta property="og:description" content="A focused practice tool for musicians with a metronome, timer, and exercises to help you stay consistent every day.">
    <meta name="twitter:image" content="https://focuus.coelloweb.dev/og-image.png">

    @vite(['resources/styles/main.scss', 'resources/js/app.js'])
    
    <style> [x-cloak] { display: none !important; } </style>
</head>
<body>

    <header class="main-heading">
        <h2 class="heading-2" style="text-transform: lowercase; font-weight: normal;">focuus</h2>
        <p>A practice timer for focused musicians</p>
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