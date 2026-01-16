<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        if (env('FIREBASE_CREDENTIALS')) {
            $tempFile = storage_path('firebase_credentials.json');
            file_put_contents($tempFile, env('FIREBASE_CREDENTIALS'));
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $tempFile);
        }
    }

}
