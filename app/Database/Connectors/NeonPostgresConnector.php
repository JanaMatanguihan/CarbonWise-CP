<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector as BasePostgresConnector;

class NeonPostgresConnector extends BasePostgresConnector
{
    /**
     * Create a PostgreSQL DSN with Neon's endpoint ID.
     */
    protected function getDsn(array $config)
    {
        extract($config, EXTR_SKIP);

        $host = isset($host) ? "host={$host};" : '';

        $database = $connect_via_database ?? $database ?? null;
        $port = $connect_via_port ?? $port ?? null;

        $dsn = "pgsql:{$host}dbname='{$database}'";

        if (! is_null($port)) {
            $dsn .= ";port={$port}";
        }

        if (isset($charset)) {
            $dsn .= ";client_encoding='{$charset}'";
        }

        if (isset($application_name)) {
            $dsn .= ";application_name='".str_replace("'", "\'", $application_name)."'";
        }

        if (isset($sslmode)) {
            $dsn .= ";sslmode={$sslmode}";
        }

        $endpoint = env('DB_ENDPOINT');

        if ($endpoint) {
            $dsn .= ";options=endpoint={$endpoint}";
        }

        return $dsn;
    }
}