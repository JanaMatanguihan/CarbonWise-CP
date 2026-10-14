<?php
// =============================================================================
// get_notifications.php
// Single source of truth for the notification bell on ALL pages
// (dashboard, activity_input, reports, mitigation_strategies, profile).
//
// Response:
// { "status": "success", "unread_count": 3,
//   "notifications": [ { id, title, message, type, is_read, created_at, time_ago } ] }
// =============================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

// 1. Guard check: ensure user is logged in
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
    echo json_encode(['status' => 'success', 'unread_count' => 0, 'notifications' => []]);
    exit;
}

// Optional ?limit= (default 20, max 50)
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$limit = max(1, min(50, $limit));

// 3. Neon PostgreSQL credentials
$db_host     = 'ep-red-hill-a5erg1sb-pooler.us-east-2.aws.neon.tech';
$endpoint_id = 'ep-red-hill-a5erg1sb-pooler';
$db_port     = '5432';
$db_name     = 'neondb';
$db_user     = 'neondb_owner';
$db_pass     = 'npg_B7h4oEQbqJdG';

function cw_time_ago($seconds) {
    $seconds = max(0, (int)$seconds);
    if ($seconds < 60)      return 'just now';
    if ($seconds < 3600)    { $n = (int)floor($seconds / 60);     return $n . ' minute' . ($n > 1 ? 's' : '') . ' ago'; }
    if ($seconds < 86400)   { $n = (int)floor($seconds / 3600);   return $n . ' hour'   . ($n > 1 ? 's' : '') . ' ago'; }
    if ($seconds < 604800)  { $n = (int)floor($seconds / 86400);  return $n . ' day'    . ($n > 1 ? 's' : '') . ' ago'; }
    if ($seconds < 2592000) { $n = (int)floor($seconds / 604800); return $n . ' week'   . ($n > 1 ? 's' : '') . ' ago'; }
    $n = (int)floor($seconds / 2592000);
    return $n . ' month' . ($n > 1 ? 's' : '') . ' ago';
}

try {
    $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode=require;options='endpoint={$endpoint_id}'";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);

    // 4. Latest notifications for this user
    $stmt = $pdo->prepare("
        SELECT id, title, message, type,
               COALESCE(is_read, FALSE) AS is_read,
               TO_CHAR(created_at, 'Mon DD, YYYY HH12:MI AM') AS created_at,
               GREATEST(0, EXTRACT(EPOCH FROM (NOW() - created_at)))::bigint AS age_seconds
        FROM notifications
        WHERE user_id = :user_id
        ORDER BY created_at DESC, id DESC
        LIMIT {$limit}
    ");
    $stmt->execute([':user_id' => $user_id]);
    $rows = $stmt->fetchAll();

    $notifications = [];
    foreach ($rows as $r) {
        $notifications[] = [
            'id'         => (int)$r['id'],
            'title'      => (string)($r['title'] ?? ''),
            'message'    => (string)($r['message'] ?? ''),
            'type'       => strtolower((string)($r['type'] ?? 'info')),
            'is_read'    => ($r['is_read'] === true || $r['is_read'] === 't' || $r['is_read'] === 1 || $r['is_read'] === '1'),
            'created_at' => (string)($r['created_at'] ?? ''),
            'time_ago'   => cw_time_ago($r['age_seconds'] ?? 0),
        ];
    }

    // 5. True unread total (not limited to the list above)
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND COALESCE(is_read, FALSE) = FALSE");
    $cnt->execute([':user_id' => $user_id]);

    echo json_encode([
        'status'        => 'success',
        'unread_count'  => (int)$cnt->fetchColumn(),
        'notifications' => $notifications
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to load notifications.']);
    exit;
}