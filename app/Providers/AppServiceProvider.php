<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDrive\GoogleDriveAdapter;

// FOR LOGIN 
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// FOR SENDING EMAIL
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Storage::extend('google', function ($app, $config) {
            $client = new \Google_Client();
            $client->setClientId($config['client_id']);
            $client->setClientSecret($config['client_secret']);
            $client->refreshToken($config['refresh_token']);

            $service = new \Google_Service_Drive($client);

            $adapter = new GoogleDriveAdapter($service, $config['folder_id'] ?? null);

            return new Filesystem($adapter);
        });

        Inertia::share([
            'auth' => fn() => [
                'user' => Auth::check() ? [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name,
                    'uname' => Auth::user()->uname,
                ] : null,
            ],
        ]);
        Mail::extend('brevo', function () {
            $config = $this->app['config']->get('services.brevo', []);

            return (new BrevoTransportFactory())->create(
                new Dsn(
                    'brevo+api',
                    'default',
                    $config['key']
                )
            );
        });
    }
}
