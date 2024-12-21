<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Http::macro('forge', function () {
            return Http::withHeaders([
                'Authorization' => 'Bearer '.config('forge.token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->withUrlParameters(['serverId' => config('forge.server')])
                ->baseUrl('https://forge.laravel.com/api/v1')
                ->throw();
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
