<?php
// =============================================================================
// update_notifications.php
// Marks notifications as read for the logged-in user only.
//   POST id=<n>            -> mark one notification as read
//   POST action=mark_all   -> mark every unread notification as read
// Response: { "status": "success", "unread_count": N }
// =============================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

// 1. Check user login session
if (!isset($_SESSION['user_token'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// 2. Resolve the user ID exactly like the other pages do
$user_data   = $_SESSION['user_profile'] ?? ($_SESSION['user_data'] ?? []);
$raw_user_id = $user_data['id'] ?? ($_SESSION['user_id'] ?? ($_SESSION['id'] ?? null));

if (!empty($raw_user_id) && !is_numeric($raw_user_id)) {
    $clean_id = preg_replace('/[^0-9]/', '', (string)$raw_user_id);
    $user_id  = !empty($clean_id) ? (int)$clean_id : null;
} else {
    $user_id = !empty($raw_user_id) ? (int)$raw_user_id : null;
}

if (empty($user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user session']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? 'mark_one';
$notification_id = (int)($_POST['id'] ?? 0);

if ($action !== 'mark_all' && $notification_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// 3. Neon PostgreSQL credentials
$db_host     = 'ep-red-hill-a5erg1sb-pooler.us-east-2.aws.neon.tech';
$endpoint_id = 'ep-red-hill-a5erg1sb-pooler';
$db_port     = '5432';
$db_name     = 'neondb';
$db_user     = 'neondb_owner';
$db_pass     = 'npg_B7h4oEQbqJdG';

try {
    $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode=require;options='endpoint={$endpoint_id}'";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);

    // 4. Update ONLY rows that belong to the logged-in user
    if ($action === 'mark_all') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE, updated_at = NOW() WHERE user_id = :user_id AND COALESCE(is_read, FALSE) = FALSE");
        $stmt->execute([':user_id' => $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE, updated_at = NOW() WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $notification_id, ':user_id' => $user_id]);
    }

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND COALESCE(is_read, FALSE) = FALSE");
    $cnt->execute([':user_id' => $user_id]);

    echo json_encode(['status' => 'success', 'unread_count' => (int)$cnt->fetchColumn()]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to update notifications.']);
    exit;
}