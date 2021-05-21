<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // Creating url (external url - spa url) for reset password
        ResetPassword::createUrlUsing(function($notifiable, $token){
            return config('services.app_subdomain.sub_domain') . "reset-password/{$token}?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
