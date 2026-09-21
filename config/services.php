<?php

return [

    'typesafe' => [
        'key' => env('TYPESAFE_API_KEY'),
        'url' => env('TYPESAFE_API_URL', 'https://api.typesafe.ai'),
        'model' => env('TYPESAFE_MODEL', 'jev-latest'),
        'search_min_probability' => (float) env(
            'TYPESAFE_SEARCH_MIN_PROBABILITY',
            0.60,
        ),
        'search_min_confidence' => (float) env(
            'TYPESAFE_SEARCH_MIN_CONFIDENCE',
            0.25,
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT'),
    ]
];
