<?php

return [
    /*
    |--------------------------------------------------------------------------
    | bKash API Configuration
    |--------------------------------------------------------------------------
    */

    'base_url'    => env('BKASH_BASE_URL', 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'),
    'app_key'     => env('BKASH_APP_KEY'),
    'app_secret'  => env('BKASH_APP_SECRET'),
    'username'    => env('BKASH_USERNAME'),
    'password'    => env('BKASH_PASSWORD'),

    'callback_url'            => env('BKASH_CALLBACK_URL'),
    'agreement_callback_url'  => env('BKASH_AGREEMENT_CALLBACK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    | AES-256-CBC key used to encrypt agreementId at rest.
    */
    'encrypt_key' => env('BKASH_ENCRYPT_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Gotenberg PDF Service
    |--------------------------------------------------------------------------
    */
    'gotenberg_url' => env('GOTENBERG_URL', 'http://localhost:3000'),

    /*
    |--------------------------------------------------------------------------
    | Token Cache TTL (seconds)
    |--------------------------------------------------------------------------
    */
    'token_ttl' => 3500, // slightly less than bKash's 1-hour token lifetime
];
