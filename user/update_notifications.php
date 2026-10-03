<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// 1. Check user login session
if (!isset($_SESSION['user_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $notification_id = (int)$_POST['id'];

    // 2. Neon PostgreSQL Database Credentials
    $db_host     = 'ep-red-hill-a5erg1sb-pooler.us-east-2.aws.neon.tech';
    $endpoint_id = 'ep-red-hill-a5erg1sb-pooler'; 
    $db_port     = '5432';
    $db_name     = 'neondb';
    $db_user     = 'neondb_owner'; 
    $db_pass     = 'npg_B7h4oEQbqJdG'; 

    try {
        $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode=require;options='endpoint={$endpoint_id}'";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);

        // 3. Update notification status in Neon PostgreSQL
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $notification_id]);

        echo json_encode(['status' => 'success']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;