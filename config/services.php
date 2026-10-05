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
    
    'zatca' => [
        'environment' => env(
            'ZATCA_ENVIRONMENT',
            'developer'
        ),

        'developer_url' => env(
            'ZATCA_DEVELOPER_URL',
            'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal'
        ),

        'simulation_url' => env(
            'ZATCA_SIMULATION_URL',
            'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation'
        ),

        'production_url' => env(
            'ZATCA_PRODUCTION_URL',
            'https://gw-fatoora.zatca.gov.sa/e-invoicing/core'
        ),

        'openssl_path' => env(
            'ZATCA_OPENSSL_PATH',
            'openssl'
        ),

        'auto_submit' => env(
            'ZATCA_AUTO_SUBMIT',
            false
        ),
        
    ],

];
