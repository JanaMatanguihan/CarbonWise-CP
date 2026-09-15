<?php
// Start session for login state tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns a PDO database connection to the Neon PostgreSQL database
 */
function getDBConnection() {
    // Read parameters from environment variables (Railway), with fallbacks
    $host     = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? 'ep-red-hill-a5erg1sb-pooler.us-east-2.aws.neon.tech';
    $port     = getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?? '5432';
    $dbname   = getenv('DB_NAME') ?: $_ENV['DB_DATABASE'] ?? 'neondb';
    $username = getenv('DB_USER') ?: $_ENV['DB_USERNAME'] ?? 'neondb_owner';
    $password = getenv('DB_PASS') ?: $_ENV['DB_PASSWORD'] ?? 'npg_B7h4oEQbqJdG';

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