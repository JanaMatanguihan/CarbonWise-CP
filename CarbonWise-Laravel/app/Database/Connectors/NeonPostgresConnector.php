<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector as BasePostgresConnector;

class NeonPostgresConnector extends BasePostgresConnector
{
    protected function getDsn(array $config)
    {
        $host = $config['host'] ?? '127.0.0.1';
        $database = $config['database'] ?? null;
        $port = $config['port'] ?? 5432;
        $charset = $config['charset'] ?? 'utf8';
        $sslmode = $config['sslmode'] ?? 'require';
        $endpoint = $config['endpoint'] ?? null;

        $dsn = "pgsql:"
            . "host={$host};"
            . "dbname='{$database}';"
            . "port={$port};"
            . "client_encoding='{$charset}';"
            . "sslmode={$sslmode}";

        if ($endpoint) {
            $dsn .= ";options='endpoint={$endpoint}'";
        }

        return $dsn;
    }
}