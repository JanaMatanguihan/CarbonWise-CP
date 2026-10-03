<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// 1. Guard check: Ensure user is logged in
if (!isset($_SESSION['user_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// 2. Safely extract user ID from session
$user_data   = $_SESSION['user_profile'] ?? ($_SESSION['user_data'] ?? []);
$raw_user_id = $user_data['id'] ?? ($_SESSION['user_id'] ?? ($_SESSION['id'] ?? null));

if (!empty($raw_user_id) && !is_numeric($raw_user_id)) {
    $clean_id = preg_replace('/[^0-9]/', '', (string)$raw_user_id);
    $user_id  = !empty($clean_id) ? (int)$clean_id : null;
} else {
    $user_id = !empty($raw_user_id) ? (int)$raw_user_id : null;
}

if (empty($user_id)) {
    echo json_encode([]);
    exit;
}

// 3. Neon PostgreSQL Database Credentials
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

    // 4. Fetch notifications for the logged-in user sorted by creation date
    $stmt = $pdo->prepare("
        SELECT id, title, message, is_read, TO_CHAR(created_at, 'Mon DD, YYYY HH12:MI AM') AS created_at 
        FROM notifications 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([':user_id' => $user_id]);
    $notifications = $stmt->fetchAll();

    echo json_encode($notifications);
    exit;

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}