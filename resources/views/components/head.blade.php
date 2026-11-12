<head>
    <x-google-tag />
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>{{ $title }}</title>
    
    @php
        $currentUrl = url()->current();
    @endphp

    <meta name="description" content="{{ $description }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ url('/og-image.png') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $currentUrl }}">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ url('/og-image.png') }}">
    
    <link rel="canonical" href="{{ $currentUrl }}">
    
    <link rel="icon" type="image/png" href="/favicon-96x96.png?v=20261105" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=20261105">
    <link rel="shortcut icon" href="/favicon.ico?v=20261105">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=20261105">
    <link rel="manifest" href="/site.webmanifest?v=20261105">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="product-events-endpoint" content="{{ route('analytics.events.store') }}">

    @if (request()->routeIs('login', 'register', 'password.*'))
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="robots" content="index, follow">
    @endif
    
    <style> [x-cloak] { display: none !important; } .subscription-icon { width: 89px; height: 89px; opacity: 0; } </style>
    
    @vite([
        'resources/styles/main.scss',
        request()->routeIs('welcome', 'routines.show', 'shared.show')
            ? 'resources/js/practice.js'
            : 'resources/js/app.js',
    ])

    @livewireStyles
</head>