<?php

$paymongoAdminEnabled = (bool) env(
    'PAYMONGO_ADMIN_ENABLED',
    false,
);

$paymongoSecretKey = trim(
    (string) env(
        'PAYMONGO_SECRET_KEY',
        '',
    ),
);

$paymongoWebhookSecret = trim(
    (string) env(
        'PAYMONGO_WEBHOOK_SECRET',
        '',
    ),
);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'paymongo' => [
        'admin_enabled' => $paymongoAdminEnabled,

        'secret_key' => $paymongoSecretKey,

        'webhook_secret' => $paymongoWebhookSecret,

        /*
         * This is only the deployment-level gate for new PayMongo
         * functionality. Historical webhook reconciliation must not use this
         * value as its processing gate.
         */
        'available' => $paymongoAdminEnabled
            && $paymongoSecretKey !== ''
            && $paymongoWebhookSecret !== '',
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
