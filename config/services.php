<?php

return [

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

    'open_library' => [
        'base_url' => env('OPEN_LIBRARY_BASE_URL', 'https://openlibrary.org'),
        'user_agent' => env('OPEN_LIBRARY_USER_AGENT', 'TurathBackend (configure OPEN_LIBRARY_USER_AGENT)'),
        'sync_subjects' => env('OPEN_LIBRARY_SYNC_SUBJECTS', 'history,literature'),
        'sync_limit' => (int) env('OPEN_LIBRARY_SYNC_LIMIT', 20),
        'sync_ebooks_only' => filter_var(env('OPEN_LIBRARY_SYNC_EBOOKS_ONLY', false), FILTER_VALIDATE_BOOLEAN),
        'request_delay_microseconds' => (int) env('OPEN_LIBRARY_REQUEST_DELAY_MICROSECONDS', 350000),
        'queue_connection' => env('OPEN_LIBRARY_QUEUE_CONNECTION', 'background'),
        'queue' => env('OPEN_LIBRARY_QUEUE', 'open-library'),
    ],

    'internet_archive' => [
        'base_url' => env('INTERNET_ARCHIVE_BASE_URL', 'https://archive.org'),
    ],

];
