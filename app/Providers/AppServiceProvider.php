<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Services\Auth\FirebaseJwtGuard;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //app/
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        

        // Auth::extend('firebase_jwt', function ($app, $name, array $config) {

        //     $userProvider = Auth::createUserProvider($config['provider']);

        //     if (!$userProvider) {
        //         \Illuminate\Support\Facades\Log::error("Failed to create UserProvider for provider: " . ($config['provider'] ?? 'NOT SET'));
        //         throw new \InvalidArgumentException(
        //             "Auth user provider [{$config['provider']}] is not defined."
        //         );
        //     } else {
        //         \Illuminate\Support\Facades\Log::info("UserProvider created successfully for: " . ($config['provider'] ?? 'NOT SET'));
        //     }

        //     return new \App\Services\Auth\FirebaseJwtGuard(
        //         $userProvider,
        //         $app['request']
        //     );
        // });


        // --- THIS IS THE CRITICAL PART ---
        // This tells Laravel how to resolve the 'firebase_jwt' driver
        // that you specified in config/auth.php
        Auth::extend('firebase_jwt', function ($app, $name, array $config) {
            // The 'provider' key comes from your 'guards.api' config in auth.php
            $userProvider = Auth::createUserProvider($config['provider']);
            
            return new FirebaseJwtGuard($userProvider, $app['request']);
        });
    }
}
