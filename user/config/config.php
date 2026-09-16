<?php
// Start session for login state tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns a PDO database connection to the Neon PostgreSQL database
 */
function getDBConnection() {
    // Fill in your credentials from the Neon "Connect" modal
    $host     = 'ep-sparkling-math-a50wbxv4.us-east-2.aws.neon.tech'; // Example from your console URL
    $port     = '5432';
    $dbname   = 'neondb';
    $username = 'YOUR_NEON_USERNAME'; // From Neon Connect Modal
    $password = 'YOUR_NEON_PASSWORD'; // From Neon Connect Modal

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