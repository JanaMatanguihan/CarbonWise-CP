<?php

namespace App\Providers;

use App\Database\Connectors\NeonPostgresConnector;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

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
        DB::extend('pgsql', function ($config, $name) {
            $connector = new NeonPostgresConnector();

            $pdo = $connector->connect($config);

            return new PostgresConnection(
                $pdo,
                $config['database'],
                $config['prefix'] ?? '',
                $config
            );
        });
    }
}