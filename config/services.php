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

    'sms' => [
        'url' => env('SMS_API_URL', 'https://api.sms.example.com/send'),
        'api_key' => env('SMS_API_KEY'),
    ],

    'whatsapp' => [
        'url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0/me/messages'),
        'api_key' => env('WHATSAPP_API_KEY'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    ],

    'notifications' => [
        'channels' => [
            'email' => env('NOTIFICATION_EMAIL_ENABLED', true),
            'sms' => env('NOTIFICATION_SMS_ENABLED', false),
            'whatsapp' => env('NOTIFICATION_WHATSAPP_ENABLED', false),
        ],
    ],

];
