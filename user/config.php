<?php
// Start session for login state tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns a PDO database connection to the Neon PostgreSQL database
 */
function getDBConnection() {
    // Check custom environment variables, Railway defaults, $_ENV, and fallbacks
    $host     = trim(getenv('DB_HOST')     ?: $_ENV['DB_HOST']     ?: getenv('PGHOST')     ?: $_ENV['PGHOST']     ?: 'ep-red-hill-a5erg1sb-pooler.us-east-2.aws.neon.tech');
    $port     = trim(getenv('DB_PORT')     ?: $_ENV['DB_PORT']     ?: getenv('PGPORT')     ?: $_ENV['PGPORT']     ?: '5432');
    $dbname   = trim(getenv('DB_NAME')     ?: $_ENV['DB_NAME']     ?: $_ENV['DB_DATABASE'] ?: getenv('PGDATABASE') ?: $_ENV['PGDATABASE'] ?: 'neondb');
    $username = trim(getenv('DB_USER')     ?: $_ENV['DB_USER']     ?: $_ENV['DB_USERNAME'] ?: getenv('PGUSER')     ?: $_ENV['PGUSER']     ?: 'neondb_owner');
    $password = trim(getenv('DB_PASS')     ?: $_ENV['DB_PASS']     ?: $_ENV['DB_PASSWORD'] ?: getenv('PGPASSWORD') ?: $_ENV['PGPASSWORD'] ?: 'npg_B7h4oEQbqJdG');

    // Construct DSN string with mandatory sslmode=require for Neon
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::PGSQL_ATTR_DISABLE_PREPARES => true
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Displays exact connection failure details for easy debugging
        throw new Exception("Neon Connection Error: " . $e->getMessage());
    }
}