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

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL'),
    ],

    'fedapay' => [
        'secret_key'     => env('FEDAPAY_SECRET_KEY'),
        'webhook_secret' => env('FEDAPAY_WEBHOOK_SECRET'),
        'environment'    => env('FEDAPAY_ENV', 'sandbox'),
    ],

    'paydunya' => [
        'master_key'     => env('PAYDUNYA_MASTER_KEY'),
        'private_key'    => env('PAYDUNYA_PRIVATE_KEY'),
        'public_key'     => env('PAYDUNYA_PUBLIC_KEY'),
        'token'          => env('PAYDUNYA_TOKEN'),
        'webhook_secret' => env('PAYDUNYA_WEBHOOK_SECRET'),
        'environment'    => env('PAYDUNYA_ENV', 'sandbox'),
    ],

    'cinetpay' => [
        'apikey'         => env('CINETPAY_APIKEY'),
        'site_id'        => env('CINETPAY_SITE_ID'),
        'secret_key'     => env('CINETPAY_SECRET_KEY'),
    ],

    'flutterwave' => [
        'public_key'     => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key'     => env('FLUTTERWAVE_SECRET_KEY'),
        'webhook_secret' => env('FLUTTERWAVE_WEBHOOK_SECRET'),
    ],

    'feexpay' => [
        'shop_id'  => env('FEEXPAY_SHOP_ID'),
        'token'    => env('FEEXPAY_TOKEN'),
    ],

];
