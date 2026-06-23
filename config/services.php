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

    'antiqscan_ai' => [
        'provider' => env('ANTIQSCAN_AI_PROVIDER', 'mammouth'),
        'base_url' => env('ANTIQSCAN_AI_BASE_URL', 'https://api.mammouth.ai/v1'),
        'api_key' => env('ANTIQSCAN_AI_API_KEY', env('MAMMOUTH_API_KEY')),
        'model' => env('ANTIQSCAN_AI_MODEL', 'gemini-2.5-flash-lite'),
        'max_output_tokens' => (int) env('ANTIQSCAN_AI_MAX_OUTPUT_TOKENS', 450),
        'show_run_debug' => (bool) env('ANTIQSCAN_AI_SHOW_RUN_DEBUG', false),
    ],

];
