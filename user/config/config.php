<?php
// Start session for login state tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns a PDO database connection to the Neon PostgreSQL database
 */
function getDBConnection() {
    $host     = getenv('DB_HOST') ?: 'ep-sparkling-math-a50wbxv4.us-east-2.aws.neon.tech';
    $port     = getenv('DB_PORT') ?: '5432';
    $dbname   = getenv('DB_NAME') ?: 'neondb';
    $username = getenv('DB_USER') ?: 'neondb_owner';
    $password = getenv('DB_PASS') ?: 'npg_B7h4oEQbqJdG';

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Neon Connection Error: " . $e->getMessage());
    }
}