<?php

namespace App\Providers;

use App\Database\Connectors\NeonPostgresConnector;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\ServiceProvider;

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
    }
}