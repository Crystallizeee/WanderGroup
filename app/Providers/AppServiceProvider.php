<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Google\Client;
use Google\Service\Drive;
use Masbug\Flysystem\GoogleDriveAdapter;
use League\Flysystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;

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
    public function boot(): void
    {
        if (str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        try {
            Storage::extend('google', function ($app, $config) {
                $client = new Client();
                $client->setClientId($config['client_id']);
                $client->setClientSecret($config['client_secret']);
                $client->refreshToken($config['refresh_token']);
                $client->addScope(Drive::DRIVE);

                $service = new Drive($client);
                $adapter = new GoogleDriveAdapter($service, null, [
                    'sharedFolderId' => $config['folder_id'] ?? null
                ]);
                
                $driver = new Filesystem($adapter);

                return new FilesystemAdapter($driver, $adapter, $config);
            });
        } catch (\Throwable $e) {
            // Prevent boot crashes if the credentials file is missing
        }
    }
}
