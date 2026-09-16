<?php

namespace App\Providers;

use App\Database\Connectors\NeonPostgresConnector;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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
        $this->app->make(DatabaseManager::class)->extend('pgsql', function ($config, $name) {
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

        // Customize the email verification link generation
    VerifyEmail::createUrlUsing(function ($notifiable) {
        $expireMinutes = config('auth.verification.expire', 60);

        // This generates a secure signed URL pointing to your web route
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