<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register bKash service as singleton so token is cached per-request lifecycle
        $this->app->singleton(\App\Services\BkashService::class);
        $this->app->singleton(\App\Services\PdfStatementService::class);
    }

    public function boot(): void
    {
        //
    }
}
