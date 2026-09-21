<?php

namespace App\Providers;

use App\Database\Connectors\NeonPostgresConnector;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register the custom pgsql connector LAZILY so config is loaded first.
        $this->app->resolving(DatabaseManager::class, function (DatabaseManager $db) {
            $db->extend('pgsql', function ($config, $name) {
                $connector = new NeonPostgresConnector();
                $pdo = $connector->connect($config);

                $connection = new PostgresConnection(
                    $pdo,
                    $config['database'],
                    $config['prefix'] ?? '',
                    $config
                );

                $connection->setSchemaGrammar(
                    new PostgresGrammar($connection)
                );

                return $connection;
            });
        });

        VerifyEmail::createUrlUsing(function ($notifiable) {
            $expireMinutes = config('auth.verification.expire', 60);

            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes($expireMinutes),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });
    }
}