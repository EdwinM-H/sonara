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

    'ai' => [
        'provider' => env('AI_PROVIDER', 'mock'),
        'api_key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL', 'gpt-image-1'),
        'google_model' => env('AI_GOOGLE_MODEL', 'gemini-2.5-flash-image'),
        'size' => env('AI_SIZE', '1024x1024'),
        'max_per_user' => (int) env('AI_MAX_PER_USER', 20),
        'period_days' => (int) env('AI_PERIOD_DAYS', 30),
    ],

    'voice' => [
        'stt_provider' => env('STT_PROVIDER', 'browser'),
        'stt_api_key' => env('STT_API_KEY'),
        'stt_region' => env('STT_API_REGION'),
        'tts_provider' => env('TTS_PROVIDER', 'browser'),
        'tts_api_key' => env('TTS_API_KEY'),
        'tts_region' => env('TTS_API_REGION'),
        'tts_voice' => env('TTS_VOICE', 'es-ES'),
    ],

];
