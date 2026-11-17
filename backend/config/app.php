<?php

use Illuminate\Support\Facades\Facade;

return [
    'name'             => env('APP_NAME', 'bKash Wallet'),
    'env'              => env('APP_ENV', 'production'),
    'debug'            => (bool) env('APP_DEBUG', false),
    'url'              => env('APP_URL', 'http://localhost'),
    'frontend_url'     => env('FRONTEND_URL', 'http://localhost:5173'),
    'timezone'         => 'Asia/Dhaka',
    'locale'           => 'en',
    'fallback_locale'  => 'en',
    'faker_locale'     => 'en_US',
    'key'              => env('APP_KEY'),
    'cipher'           => 'AES-256-CBC',
    'maintenance'      => ['driver' => 'file'],
    'providers'        => Illuminate\Support\ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
    ])->toArray(),
    'aliases'          => Facade::defaultAliases()->merge([])->toArray(),
];
