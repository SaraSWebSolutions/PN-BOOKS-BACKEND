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

    // config/services.php — add this array
'gemini' => [
    'key'   => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
],

'airwallex' => [
    'base_url'       => env('AIRWALLEX_BASE_URL'),
    'client_id'      => env('AIRWALLEX_CLIENT_ID'),
    'api_key'        => env('AIRWALLEX_API_KEY'),
    'webhook_secret' => env('AIRWALLEX_WEBHOOK_SECRET'),
],


'etimeoffice' => [
    'auth' => env('ETIMEOFFICE_AUTH'),
],
    'meta' => [
    'page_access_token' => env('FB_PAGE_ACCESS_TOKEN'),
    'page_id'            => env('FB_PAGE_ID'),
    'verify_token'        => env('FB_WEBHOOK_VERIFY_TOKEN'),
],

];
