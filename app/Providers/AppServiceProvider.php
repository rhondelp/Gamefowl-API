<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

/**
 * File: app/Providers/AppServiceProvider.php
 *
 * Purpose:
 *   Application-level service registration. Currently empty because Laravel
 *   handles everything this project needs automatically — but it's the
 *   conventional home for future bindings (e.g. interface-to-implementation
 *   swaps) if the project grows.
 *
 * How it fits into the project:
 *   Registered in bootstrap/providers.php and booted on every request;
 *   anything bound here becomes available to controllers/services via the
 *   service container.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register container bindings. Nothing needed yet.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services after all providers registered.
     *
     * Override the default reset password URL generation to point to our
     * backend-hosted web form instead of a frontend SPA URL. The link will
     * be: {APP_URL}/reset-password?token={token}&email={email}
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            return config('app.url') . "/reset-password?token={$token}&email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
