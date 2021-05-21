<?php

use App\Models\Currency;

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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'exchange_api' => [
        'yadio' => [
            'url' => env('YADIO_API_URL', 'https://api.yadio.io/exrates/')
        ],
        'exchangerate' => [
            'url' => env('EXCHANGERATE_API_URL', 'https://prime.exchangerate-api.com/v5/'),
            'key' => env('EXCHANGERATE_API_KEY', 'YOUR_API_KEY')
        ],
        'currencylayer' => [
            'url' => env('CURRENCY_LAYER_API_URL', 'https://apilayer.net/api/live'),
            'key' => env('CURRENCY_LAYER_API_KEY')
        ],
    ],

    'app_subdomain' => [
        'sub_domain' => env('APP_SUBDOMAIN', 'http://localhost:3000/')
    ],

    'expirations' => [
        'order' => env('ORDER_EXPIRATION_HOURS', 24)
    ]
];
