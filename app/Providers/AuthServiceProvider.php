<?php

namespace App\Providers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $urlArr = explode("/", $url);
            $verificateionUrl =  config("services.app_subdomain.sub_domain") . "verify-email/" . $urlArr[6] . "/" . $urlArr[7];
            return (new MailMessage)
                ->subject('Verificación de Cuenta de Correo - AndeanWide')
                ->greeting('Hola ' . Str::upper($notifiable->name))
                ->line('Haz clic en el botón para verificar tu dirección de correo electrónico.')
                ->action('Verificar dirección de correo', $verificateionUrl)
                ->line('Si no has creado ninguna cuenta, no se requiere acción alguna.')
                ->salutation('Saludos, AndeanWide');
        });
    }
}
