<head>
    <x-google-tag />
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://dorelog.com/">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="https://dorelog.com/og-image.png">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://dorelog.com/">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="https://dorelog.com/og-image.png">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="product-events-endpoint" content="{{ route('analytics.events.store') }}">

    @if (request()->routeIs('login', 'register', 'password.*'))
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="robots" content="index, follow">
    @endif
    
    <style> [x-cloak] { display: none !important; } </style>
    
    @vite(['resources/styles/main.scss', 'resources/js/app.js'])

    @livewireStyles
</head>