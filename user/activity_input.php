<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Campus-only access: location must be verified (and match the account's campus)
require_once __DIR__ . '/require_campus.php';

// 1. Guard check: Redirect unauthenticated users
if (!isset($_SESSION['user_token'])) {
    header('Location: login.php');
    exit;
}

// 2. Extract session variables safely and resolve dynamic user ID
$user_data = $_SESSION['user_profile'] ?? ($_SESSION['user_data'] ?? []);
$raw_user_id = $user_data['id'] ?? ($_SESSION['user_id'] ?? ($_SESSION['id'] ?? null));

if (!empty($raw_user_id) && !is_numeric($raw_user_id)) {
    $clean_id = preg_replace('/[^0-9]/', '', (string)$raw_user_id);
    $user_id  = !empty($clean_id) ? (int)$clean_id : null;
} else {
    $user_id = !empty($raw_user_id) ? (int)$raw_user_id : null;
}

$user_metadata = $user_data['user_metadata'] ?? [];

// 3. Extract user metadata and display roles
$raw_name = $_SESSION['user_name'] ?? ($user_data['full_name'] ?? ($user_metadata['full_name'] ?? ($user_metadata['name'] ?? '')));

if (isset($user_data['role']) && strtolower($user_data['role']) !== 'authenticated') {
    $raw_role = $user_data['role'];
} else {
    $raw_role = $user_metadata['role'] ?? '';
}

$full_name = !empty($raw_name) ? ucwords(strtolower(trim($raw_name))) : 'Unknown User'; 
$role      = (!empty($raw_role) && strtolower($raw_role) !== 'authenticated') ? ucwords(strtolower(trim($raw_role))) : '';

// Profile Picture Initial Fallback from session metadata
$avatar_url = $user_metadata['avatar_url'] ?? null; 

// --- NEON POSTGRESQL DATABASE CONNECTION ---
$host        = 'ep-red-hill-a5erg1sb-pooler.us-east-2.aws.neon.tech';
$endpoint_id = 'ep-red-hill-a5erg1sb-pooler'; 
$port        = 5432;
$db          = 'neondb';
$user        = 'neondb_owner';
$password    = 'npg_B7h4oEQbqJdG';

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require;options='endpoint=$endpoint_id'";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['action']) && $_GET['action'] === 'fetch_notifications')) {
        header('Content-Type: application/json');
        echo json_encode([
            "status"  => "error",
            "message" => "Database connection failed. Please try again in a moment."
        ]);
        exit;
    }
}

// --- NOTIFICATION HELPER (writes to the same `notifications` table the bell reads from) ---
function cw_push_notification($pdo, $user_id, $title, $message, $type = 'info') {
    if (empty($pdo) || empty($user_id)) { return false; }
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, created_at, updated_at)
            VALUES (:user_id, :title, :message, :type, FALSE, NOW(), NOW())
        ");
        $stmt->execute([
            ':user_id' => $user_id,
            ':title'   => $title,
            ':message' => $message,
            ':type'    => $type
        ]);
        return true;
    } catch (\PDOException $e) {
        return false;
    }
}

// Compares today's total with the recent daily average and files a notification
function cw_notify_after_activity($pdo, $user_id, $today_date) {
    try {
        $t = $pdo->prepare("SELECT COALESCE(SUM(total_emission), 0) FROM carbon_records WHERE user_id = :u AND record_date = :d");
        $t->execute([':u' => $user_id, ':d' => $today_date]);
        $today_total = (float)$t->fetchColumn();

        $since = date('Y-m-d', strtotime($today_date . ' -7 days'));
        $a = $pdo->prepare("
            SELECT AVG(day_total) FROM (
                SELECT record_date, SUM(total_emission) AS day_total
                FROM carbon_records
                WHERE user_id = :u AND record_date >= :since AND record_date < :d
                GROUP BY record_date
            ) x
        ");
        $a->execute([':u' => $user_id, ':since' => $since, ':d' => $today_date]);
        $avg = (float)$a->fetchColumn();

        $pct = ($avg > 0) ? (int)round(abs($today_total - $avg) / $avg * 100) : 0;

        if ($avg > 0 && $today_total < $avg && $pct > 0) {
            cw_push_notification($pdo, $user_id, 'Great progress! 💚', "You emitted {$pct}% less than your recent average. Keep it up!", 'success');
        } elseif ($avg > 0 && $today_total > $avg && $pct > 0) {
            cw_push_notification($pdo, $user_id, 'Emissions above your average', "You emitted {$pct}% more than your recent average. Check Mitigation Strategies for ways to cut back.", 'warning');
        } else {
            cw_push_notification($pdo, $user_id, 'Activity logged', "Your footprint today is " . number_format($today_total, 2) . " kg CO₂e so far. Keep logging to see how you compare.", 'info');
        }
    } catch (\PDOException $e) {
        // Never break saving an activity because of a notification problem
    }
}

// FETCH AVATAR FROM THE users TABLE (profile_picture column)
if (!empty($user_id) && isset($pdo)) {
    try {
        $avatarStmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = :user_id LIMIT 1");
        $avatarStmt->execute([':user_id' => $user_id]);
        $db_avatar = $avatarStmt->fetchColumn();
        if (!empty($db_avatar)) {
            $avatar_url = $db_avatar;
        }
    } catch (\PDOException $e) {
        // Fallback silently to $avatar_url from session metadata on query error
    }
}

// Initials generation if avatar_url is missing
$initials = '';
if (empty($avatar_url)) {
    $clean_name = preg_replace('/^(dr\.|mr\.|ms\.|prof\.)\s+/i', '', trim($full_name));
    $words = explode(' ', $clean_name);
    if (count($words) >= 2) {
        $initials = strtoupper(substr($words[0], 0, 1) . substr($words[count($words) - 1], 0, 1));
    } elseif (count($words) == 1 && !empty($words[0])) {
        $initials = strtoupper(substr($words[0], 0, 2));
    }
    if (empty($initials)) { 
        $initials = 'UU'; 
    }
}

$today_date = date('Y-m-d');

// Fetch today's logged activities for permanent daily persistence
$today_records = [];
if (!empty($user_id) && isset($pdo)) {
    try {
        $recStmt = $pdo->prepare("
            SELECT id, transport_item, office_item, food_item, food_consumed_at, 
                   transportation, electricity, food, total_emission, created_at 
            FROM carbon_records 
            WHERE user_id = :user_id AND record_date = :record_date
            ORDER BY created_at DESC, id DESC
        ");
        $recStmt->execute([':user_id' => $user_id, ':record_date' => $today_date]);
        $today_records = $recStmt->fetchAll();
    } catch (\PDOException $e) {
        $today_records = [];
    }
}

// How many transport trips are already saved today (so trip numbers keep counting up)
$saved_transport_count = 0;
foreach ($today_records as $rec) {
    if (!empty($rec['transport_item'])) { $saved_transport_count++; }
}

// Save process block
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_emissions') {
    header('Content-Type: application/json');
    
    if (empty($user_id)) {
        echo json_encode([
            "status"  => "error", 
            "message" => "Your session has expired or is invalid. Please log in again."
        ]);
        exit;
    }

    // Build the list of rows to insert.
    // New page: every activity the user added is its own row (multiple trips per day).
    // Old clients that only send totals still work (single combined row).
    $rows = [];

    if (isset($_POST['entries'])) {
        $entries = json_decode((string)$_POST['entries'], true);
        if (!is_array($entries)) { $entries = []; }

        foreach ($entries as $e) {
            if (!is_array($e)) { continue; }
            $cat      = $e['category'] ?? '';
            $item     = trim((string)($e['item'] ?? ''));
            $emission = round(max(0, (float)($e['emission'] ?? 0)), 4);

            if (!in_array($cat, ['transport', 'office', 'food'], true) || $item === '') { continue; }
            $item = function_exists('mb_substr') ? mb_substr($item, 0, 255) : substr($item, 0, 255);

            $row = [
                'transportation'   => 0.0,
                'electricity'      => 0.0,
                'food'             => 0.0,
                'transport_item'   => null,
                'office_item'      => null,
                'food_item'        => null,
                'food_meal_period' => null,
                'food_consumed_at' => null,
            ];

            if ($cat === 'transport') {
                $row['transportation'] = $emission;
                $row['transport_item'] = $item;
            } elseif ($cat === 'office') {
                $row['electricity']  = $emission;
                $row['office_item']  = $item;
            } else {
                $row['food']             = $emission;
                $row['food_item']        = $item;
                $meal = trim((string)($e['meal'] ?? ''));
                $row['food_meal_period'] = $meal !== '' ? substr($meal, 0, 50) : null;
                $at = (string)($e['consumed_at'] ?? '');
                $row['food_consumed_at'] = ($at !== '' && strtotime($at)) ? date('Y-m-d H:i:s', strtotime($at)) : null;
            }
            $rows[] = $row;
        }

        if (empty($rows)) {
            echo json_encode(["status" => "error", "message" => "There are no activities to save."]);
            exit;
        }
    } else {
        $rows[] = [
            'transportation'   => (float)($_POST['total_transport'] ?? 0),
            'electricity'      => (float)($_POST['total_office'] ?? 0),
            'food'             => (float)($_POST['total_food'] ?? 0),
            'transport_item'   => !empty($_POST['transport_item']) ? $_POST['transport_item'] : null,
            'office_item'      => !empty($_POST['office_item']) ? $_POST['office_item'] : null,
            'food_item'        => !empty($_POST['food_item']) ? $_POST['food_item'] : null,
            'food_meal_period' => !empty($_POST['food_meal_period']) ? $_POST['food_meal_period'] : null,
            'food_consumed_at' => !empty($_POST['food_consumed_at']) ? $_POST['food_consumed_at'] : null,
        ];
    }

    $current_timestamp = date('Y-m-d H:i:s');

    try {
        $sql = "INSERT INTO carbon_records (
                    user_id, 
                    transportation, 
                    electricity, 
                    food, 
                    total_emission, 
                    record_date, 
                    created_at, 
                    updated_at, 
                    transport_item, 
                    office_item, 
                    food_item, 
                    food_meal_period, 
                    food_consumed_at
                ) VALUES (
                    :user_id, 
                    :transportation, 
                    :electricity, 
                    :food, 
                    :total_emission, 
                    :record_date, 
                    :created_at, 
                    :updated_at, 
                    :transport_item, 
                    :office_item, 
                    :food_item, 
                    :food_meal_period, 
                    :food_consumed_at
                )";

        $pdo->beginTransaction();
        $stmt = $pdo->prepare($sql);

        // Rows are inserted in the order the user added them (oldest first),
        // so the newest entry has the highest id and is listed first.
        foreach ($rows as $row) {
            $waste = 0.00;
            $total_emission = $row['transportation'] + $row['electricity'] + $row['food'] + $waste;

            $stmt->execute([
                ':user_id'           => $user_id,
                ':transportation'    => $row['transportation'],
                ':electricity'       => $row['electricity'],
                ':food'              => $row['food'],
                ':total_emission'    => $total_emission,
                ':record_date'       => $today_date,
                ':created_at'        => $current_timestamp,
                ':updated_at'        => $current_timestamp,
                ':transport_item'    => $row['transport_item'],
                ':office_item'       => $row['office_item'],
                ':food_item'         => $row['food_item'],
                ':food_meal_period'  => $row['food_meal_period'],
                ':food_consumed_at'  => $row['food_consumed_at']
            ]);
        }

        $pdo->commit();

        // Notify (shows in the bell on Dashboard, Reports, Mitigation Strategies and Profile too)
        cw_notify_after_activity($pdo, $user_id, $today_date);

        echo json_encode(["status" => "success"]);
        exit;

    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        echo json_encode([
            "status"  => "error", 
            "message" => "Database Error: " . $e->getMessage()
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise - Activity Input</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

    <style>
        :root {
            --bg-body: #F3F4F6;
            --bg-card: #ffffff;
            --bg-sidebar: #2D6A4F;
            --bg-sidebar-hover: #3F8A65;
            --text-main: #1F2937;
            --text-muted: #6B7280;
            --text-sidebar-menu: #A3D9C9;
            --border-color: #E5E7EB;
            --input-bg: #F9FAFB;
            --input-border: #E5E7EB;
            --accent-green: #2D6A4F;
            --accent-green-hover: #22513B;
            --text-white-fixed: #ffffff;
            --sidebar-divider: rgba(255, 255, 255, 0.15);
            --bell-bg: #e2f0d9;
            --text-metric-label: #1B4332;
        }

        [data-theme="dark"] {
            --bg-body: #0A0F0D;
            --bg-card: #121A16;
            --bg-sidebar: #070B09;
            --bg-sidebar-hover: #162E24;
            --text-main: #F3F4F6;
            --text-muted: #9CA3AF;
            --text-sidebar-menu: #76C893;
            --border-color: #1B3A2B;
            --input-bg: #18241F;
            --input-border: #2D6A4F;
            --accent-green: #52B788;
            --accent-green-hover: #74C69D;
            --text-white-fixed: #0A0F0D;
            --sidebar-divider: rgba(118, 200, 147, 0.2);
            --bell-bg: #162E24;
            --text-metric-label: #52B788;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; transition: background-color 0.3s, border-color 0.3s, color 0.3s; }
        body { display: flex; height: 100vh; width: 100vw; background-color: var(--bg-body); color: var(--text-main); overflow: hidden; }

        /* Mobile Sidebar Overlay Mask */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 99;
        }

        .sidebar { width: 260px; background-color: var(--bg-sidebar); color: white; display: flex; flex-direction: column; padding: 20px 0; flex-shrink: 0; border-right: 1px solid var(--border-color); z-index: 100; transition: transform 0.3s ease; }
        .logo-section { display: flex; align-items: center; padding: 10px 25px; margin-bottom: 30px; gap: 12px; }
        .brand-logo-container { width: 32px; height: 32px; border-radius: 50%; background-color: white; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
        .brand-logo-container img { width: 85%; height: 85%; object-fit: contain; }
        .logo-text { font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px; color: #ffffff; }
        
        .menu-items { flex: 1; display: flex; flex-direction: column; }
        .menu-item, .theme-toggle-item { display: flex; align-items: center; padding: 14px 25px; color: var(--text-sidebar-menu); text-decoration: none; font-size: 0.95rem; font-weight: 500; background: none; border: none; width: 100%; text-align: left; cursor: pointer; }
        .menu-item i, .theme-toggle-item i { margin-right: 15px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active, .theme-toggle-item:hover { background-color: var(--bg-sidebar-hover); color: #ffffff; }
        .menu-item.active { font-weight: 600; background-color: var(--bg-sidebar-hover); color: #ffffff; }
        .sidebar-footer { margin-top: auto; display: flex; flex-direction: column; }
        .sidebar-divider { height: 1px; background-color: var(--sidebar-divider); margin: 10px 25px 15px 25px; }
        
        .main-workspace { flex: 1; display: flex; flex-direction: column; height: 100%; overflow: hidden; width: 100%; }
        .top-navbar { height: 75px; background: var(--bg-card); display: flex; align-items: center; justify-content: space-between; padding: 0 40px; flex-shrink: 0; border-bottom: 1px solid var(--border-color); gap: 15px; }
        
        .mobile-toggle-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-main);
            font-size: 1.3rem;
            cursor: pointer;
            margin-right: 10px;
        }

        .header-title-area h2 { font-size: 1.4rem; font-weight: 700; color: var(--text-main); margin-bottom: 2px; }
        .header-title-area p { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
        
        .user-nav-profile { display: flex; align-items: center; gap: 25px; }
        .notification-container { position: relative; display: inline-block; }
        .notification-bell { background: var(--bell-bg); padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--accent-green); font-size: 18px; width: 40px; height: 40px; }
        .notification-badge { position: absolute; top: -2px; right: -2px; background-color: #BA181B; color: white; font-size: 10px; font-weight: 700; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 2px solid var(--bg-card); pointer-events: none; }
        
        .notification-dropdown { position: absolute; top: 50px; right: 0; width: 340px; background: var(--bg-card); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid var(--border-color); display: none; z-index: 1000; overflow: hidden; }
        .notification-dropdown.show { display: block; }
        .dropdown-header { padding: 15px; font-weight: 700; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); color: var(--text-main); background: var(--input-bg); }
        #notificationListWrapper { max-height: 360px; overflow-y: auto; display: flex; flex-direction: column; }
        #notificationList { display: flex; flex-direction: column; }
        
        /* Notification item hover/unread styles */
        .notif-item { transition: background-color 0.2s ease; }
        .notif-item:hover { background: var(--bg-sidebar-hover) !important; color: #ffffff !important; }
        .notif-item:hover p, .notif-item:hover div, .notif-item:hover span { color: #ffffff !important; }
        .notif-item.unread { background: var(--input-bg); font-weight: 600; }
        .notif-item.unread::before { content: ''; display: inline-block; width: 8px; height: 8px; background-color: var(--accent-green); border-radius: 50%; margin-right: 8px; }

        .profile-card { display: flex; align-items: center; gap: 12px; border-left: 1px solid var(--border-color); padding-left: 25px; }
        .avatar-circle-nav { width: 40px; height: 40px; background: var(--input-bg); color: var(--accent-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; overflow: hidden; border: 1px solid var(--accent-green); flex-shrink: 0; }
        .avatar-circle-nav img { width: 100%; height: 100%; object-fit: cover; }
        .user-info-text h4 { font-size: 0.95rem; color: var(--text-main); font-weight: 700; }
        .user-info-text p { font-size: 0.8rem; color: var(--text-muted); }

        .workspace-body { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .progress-wrapper { padding: 25px 40px 0 40px; text-align: center; }
        .progress-container { width: 100%; background-color: var(--border-color); height: 16px; border-radius: 8px; overflow: hidden; margin-bottom: 8px; }
        .progress-bar { width: 100%; background-color: var(--accent-green); height: 100%; border-radius: 8px; transition: width 0.4s ease; }
        .progress-text { font-size: 0.9rem; font-style: italic; font-weight: 700; color: var(--text-metric-label); }

        .content-container { padding: 25px 40px; display: flex; flex-direction: column; gap: 20px; }
        
        /* 2-Column Side-by-Side Transport Card Grid Layout */
        .transport-grid-layout {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 25px;
            align-items: stretch;
        }

        .transport-controls-col {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .transport-map-col {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .input-card { 
            background: var(--bg-card); 
            padding: 25px; 
            border-radius: 12px; 
            border: 1px solid var(--border-color); 
            position: relative;
            z-index: 10;
        }
        
        .form-row { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; position: relative; z-index: 15; }
        .form-row.vertical-stack { flex-direction: column; align-items: stretch; gap: 15px; }
        
        .form-group { 
            flex: 1; 
            min-width: 200px; 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
            position: relative;
            z-index: 20;
        }
        .form-group label { font-size: 0.85rem; font-weight: 700; color: var(--text-main); }
        
        .form-group select, .form-group input { 
            padding: 12px; 
            border: 1px solid var(--input-border); 
            border-radius: 8px; 
            font-size: 0.9rem; 
            width: 100%; 
            background-color: var(--input-bg); 
            color: var(--text-main); 
            outline: none; 
            cursor: pointer;
            position: relative;
            z-index: 30;
            appearance: auto;
            -webkit-appearance: select;
        }
        .form-group select:disabled { opacity: 0.65; cursor: not-allowed; }
        
        .route-labels-box { display: flex; gap: 15px; margin: 0; padding: 12px; border-radius: 8px; background: var(--input-bg); border: 1px solid var(--border-color); font-size: 0.9rem; position: relative; z-index: 5; flex-wrap: wrap; }
        .route-label-item { flex: 1; min-width: 180px; display: flex; flex-direction: column; gap: 4px; }
        .route-label-title { font-weight: 700; font-size: 0.8rem; color: var(--accent-green); text-transform: uppercase; }
        .route-label-value { font-weight: 600; color: var(--text-main); }
        .route-label-item.pin-target { cursor: pointer; padding: 8px 10px; border-radius: 10px; border: 2px solid transparent; }
        .route-label-item.pin-target.active { border-color: var(--accent-green); }
        .ride-clear-btn { background: none; border: none; color: var(--accent-green); font-weight: 600; font-size: 0.8rem; cursor: pointer; align-self: flex-start; padding: 2px 2px; }
        .ride-clear-btn:hover { text-decoration: underline; }
        .ride-name-input { display: none; width: 100%; margin-top: 6px; padding: 8px 10px; font-size: 0.8rem; border: 1px solid var(--input-border); border-radius: 8px; background-color: var(--input-bg); color: var(--text-main); outline: none; }

        .btn-add { background-color: var(--accent-green); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; height: 45px; position: relative; z-index: 20; }
        .btn-add.full-width { width: 100%; margin-top: 5px; }
        .btn-add:hover { background-color: var(--accent-green-hover); }
        .btn-add:disabled { background-color: #9CA3AF; cursor: not-allowed; }

        .map-section-wrapper { width: 100%; height: 100%; display: flex; flex-direction: column; position: relative; z-index: 1; }
        
        .leaflet-container { z-index: 1 !important; }
        #map { flex: 1; min-height: 320px; width: 100%; border-radius: 8px; border: 1px solid var(--border-color); z-index: 1; display: block; }
        .leaflet-routing-container { display: none !important; }

        .list-card { background: var(--bg-card); padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); }
        
        /* Single-column Activity Card Layout */
        .activity-card-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 25px;
        }

        .activity-card-item {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }

        .activity-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .activity-icon-box.transport {
            background-color: #E8F5E9;
            color: #2E7D32;
        }

        .activity-icon-box.office {
            background-color: #E8EAF6;
            color: #3F51B5;
        }

        .activity-icon-box.food {
            background-color: #FFF3E0;
            color: #E65100;
        }

        [data-theme="dark"] .activity-icon-box.transport { background-color: #1b382b; color: #74c69d; }
        [data-theme="dark"] .activity-icon-box.office { background-color: #1e2640; color: #8c9eff; }
        [data-theme="dark"] .activity-icon-box.food { background-color: #3a2512; color: #ffb74d; }

        .activity-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
            word-break: break-word;
        }

        .activity-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .activity-subtext {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .btn-delete-card {
            background: transparent;
            border: none;
            color: #BA181B;
            cursor: pointer;
            font-size: 1rem;
            padding: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .btn-delete-card:hover { background-color: rgba(186, 24, 27, 0.1); }
        
        .btn-calculate { width: 100%; background-color: var(--accent-green); color: white; border: none; padding: 16px; border-radius: 8px; font-size: 1.2rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-calculate:hover { background-color: var(--accent-green-hover); }
        
        .swal2-popup { font-family: 'Inter', sans-serif !important; border-radius: 12px !important; }
        
        .swal2-container.logout-swal-container {
            z-index: 99999 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
        }

        /* RESPONSIVE DISPLAY STYLES */
        @media (max-width: 992px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                transform: translateX(-100%);
                z-index: 1000;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .sidebar-overlay.active {
                display: block;
            }
            .mobile-toggle-btn {
                display: block;
            }
            .top-navbar {
                padding: 0 20px;
            }
            .progress-wrapper {
                padding: 20px 20px 0 20px;
            }
            .content-container {
                padding: 20px;
            }
            .transport-grid-layout {
                grid-template-columns: 1fr;
            }
            #map {
                height: 350px;
            }
        }

        @media (max-width: 768px) {
            .top-navbar {
                height: auto;
                padding: 15px;
            }
            .header-title-area h2 {
                font-size: 1.2rem;
            }
            .user-info-text p {
                display: none;
            }
            .profile-card {
                padding-left: 12px;
                gap: 8px;
            }
            .user-nav-profile {
                gap: 12px;
            }
            .notification-dropdown {
                width: 280px;
                right: -50px;
            }
            .form-row {
                flex-direction: column;
                align-items: stretch;
            }
            .btn-add {
                width: 100%;
            }
            #map {
                height: 280px;
            }
            .activity-card-item {
                padding: 12px 15px;
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .user-info-text h4 {
                display: none;
            }
            .notification-dropdown {
                position: fixed;
                left: 15px;
                right: 15px;
                width: auto;
                top: 65px;
            }
            .input-card, .list-card {
                padding: 15px;
            }
            .route-labels-box {
                flex-direction: column;
            }
        }

        /* ===== Activity cards (Office Resource / Food Consumption) – app style ===== */
        :root { --cw-green: #3DA66B; --cw-mint: #DDF1E4; --cw-mint-hover: #CFE9D9; --cw-mint-text: #1E5B3A; --cw-field-border: #D5DBD8; --cw-pill-bg: #F3F5F4; }
        [data-theme="dark"] { --cw-green: #52B788; --cw-mint: #16301F; --cw-mint-hover: #1C3B28; --cw-mint-text: #8FD9AE; --cw-field-border: #2D6A4F; --cw-pill-bg: #18241F; }

        .cw-card { border-radius: 20px; padding: 22px; }
        .cw-card-head { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
        .cw-card-icon { width: 46px; height: 46px; border-radius: 14px; background: var(--cw-mint); color: var(--cw-green); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0; }
        .cw-card-title { font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin: 0 0 2px; }
        .cw-card-sub { font-size: 0.8rem; color: var(--text-muted); margin: 0; }

        .cw-form { display: grid; grid-template-columns: 1fr; gap: 16px; align-items: end; }
        .cw-field { display: flex; flex-direction: column; gap: 8px; min-width: 0; position: relative; z-index: 20; }
        .cw-field > label:not(.cw-pill) { font-size: 0.8rem; font-weight: 700; color: var(--cw-green); }

        .cw-select, .cw-input {
            width: 100%; height: 50px; padding: 0 16px; font-size: 0.88rem; font-family: inherit;
            border: 1px solid var(--cw-field-border); border-radius: 10px;
            background-color: var(--bg-card); color: var(--text-main); outline: none;
            -webkit-appearance: none; appearance: none; cursor: pointer;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .cw-input { cursor: text; }
        .cw-select {
            padding-right: 44px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 16px center;
        }
        .cw-select:invalid { color: var(--text-muted); }
        .cw-select option, .cw-select optgroup { color: var(--text-main); background: var(--bg-card); }
        .cw-input::placeholder { color: var(--text-muted); opacity: 1; }
        .cw-select:focus, .cw-input:focus { border-color: var(--cw-green); box-shadow: 0 0 0 3px rgba(61, 166, 107, 0.15); }

        /* date + time pills */
        .cw-field-dt { flex-direction: row; gap: 12px; }
        .cw-pill { flex: 1; min-width: 0; height: 50px; border-radius: 999px; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 0.88rem; position: relative; white-space: nowrap; }
        .cw-pill-date { background: var(--cw-pill-bg); border: 1px solid var(--border-color); color: var(--text-muted); cursor: default; }
        .cw-pill-time { background: var(--bg-card); border: 1.5px solid var(--cw-green); color: var(--text-main); font-weight: 600; cursor: pointer; }
        .cw-pill-time i { color: var(--cw-green); }
        .cw-pill-time:focus-within { box-shadow: 0 0 0 3px rgba(61, 166, 107, 0.15); }
        .cw-pill-time input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; border: 0; }

        .cw-btn-add {
            grid-column: 1 / -1; width: 100%; height: 54px; border: none; border-radius: 14px;
            background: var(--cw-mint); color: var(--cw-mint-text);
            display: flex; align-items: center; justify-content: center; gap: 10px;
            font-size: 0.95rem; font-weight: 600; font-family: inherit; cursor: pointer;
            transition: background-color 0.2s, transform 0.1s; position: relative; z-index: 20;
        }
        .cw-btn-add:hover { background: var(--cw-mint-hover); }
        .cw-btn-add:active { transform: scale(0.99); }
        .cw-btn-add:focus-visible { outline: 2px solid var(--cw-green); outline-offset: 2px; }


        /* transport card – same look as Office / Food */
        .cw-form.cw-form-stack { grid-template-columns: 1fr; }
        .transport-controls-col { gap: 16px; }
        .transport-controls-col .cw-form { gap: 16px; }
        .transport-grid-layout .route-labels-box { background: var(--cw-pill-bg); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
        .transport-grid-layout .route-label-title { color: var(--cw-green); }
        .transport-grid-layout #mapInstructions strong { color: var(--text-main); }
        #map { border-radius: 16px; border: 1px solid var(--border-color); }
        .cw-btn-add:disabled { opacity: 0.5; cursor: not-allowed; }
        .cw-btn-add:disabled:hover { background: var(--cw-mint); }
        .cw-select:disabled { opacity: 0.6; cursor: not-allowed; }

        @media (min-width: 769px) {
            .cw-form { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .cw-card { padding: 18px; }
        }

        /* ===== CarbonWise unified notification dropdown (same on every page) ===== */
        .notification-container { position: relative; display: inline-block; }
        .notification-bell { cursor: pointer; user-select: none; }
        .notification-badge { z-index: 2; }
        .cwn-dropdown { position: absolute; top: calc(100% + 12px); right: 0; width: 380px; max-width: calc(100vw - 24px); background: var(--bg-card, #ffffff); border: 1px solid var(--border-color, #E2E8F0); border-radius: 16px; box-shadow: 0 12px 32px rgba(0,0,0,0.18); display: none; flex-direction: column; overflow: hidden; z-index: 2000; }
        .cwn-dropdown.show { display: flex; }
        .cwn-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px 8px; }
        .cwn-title { font-size: 1.05rem; font-weight: 700; color: var(--accent-green, #2D6A4F); }
        .cwn-markall { background: none; border: none; color: var(--accent-green, #2D6A4F); font-size: 0.75rem; font-weight: 600; cursor: pointer; padding: 5px 8px; border-radius: 6px; font-family: inherit; }
        .cwn-markall:hover { background: var(--input-bg, #F3F4F6); }
        .cwn-markall[hidden], .cwn-footer[hidden] { display: none; }
        .cwn-list { overflow-y: auto; padding: 0 20px; }
        .cwn-dropdown.expanded .cwn-list { max-height: 360px; }
        .cwn-item { display: flex; gap: 12px; align-items: flex-start; padding: 14px 0; border-bottom: 1px solid var(--border-color, #E2E8F0); cursor: pointer; }
        .cwn-item.read { cursor: default; }
        .cwn-icon { flex-shrink: 0; font-size: 1.35rem; margin-top: 1px; color: #B7DFC8; }
        .cwn-item.unread .cwn-icon { color: #2D6A4F; }
        .cwn-item.t-warning .cwn-icon { color: #F2C14E; }
        .cwn-item.t-warning.unread .cwn-icon { color: #E09F1F; }
        .cwn-item.t-error .cwn-icon { color: #F0A5A9; }
        .cwn-item.t-error.unread .cwn-icon { color: #BA181B; }
        .cwn-item.t-info .cwn-icon { color: #A9CDEB; }
        .cwn-item.t-info.unread .cwn-icon { color: #0074d9; }
        .cwn-body { flex: 1; min-width: 0; }
        .cwn-item-title { display: flex; align-items: center; gap: 6px; font-size: 0.95rem; font-weight: 700; color: var(--accent-green, #2D6A4F); }
        .cwn-dot { width: 8px; height: 8px; border-radius: 50%; background: #E63946; flex-shrink: 0; }
        .cwn-msg { font-size: 0.85rem; color: var(--text-muted, #8E8E93); line-height: 1.4; margin-top: 3px; word-wrap: break-word; }
        .cwn-time { font-size: 0.72rem; color: var(--text-muted, #8E8E93); margin-top: 5px; opacity: 0.85; }
        .cwn-empty { padding: 28px 0; text-align: center; font-size: 0.85rem; color: var(--text-muted, #8E8E93); }
        .cwn-footer { display: block; width: 100%; background: transparent; border: none; border-top: 1px solid var(--border-color, #E2E8F0); padding: 14px; text-align: center; font-size: 0.9rem; font-weight: 600; color: var(--accent-green, #2D6A4F); cursor: pointer; font-family: inherit; }
        .cwn-footer:hover { background: var(--input-bg, #F9FAFB); }
        @media (max-width: 600px) {
            .cwn-dropdown { position: fixed; top: 76px; left: 12px; right: 12px; width: auto; max-width: none; }
        }
    </style>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1B4332">
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <div class="logo-section">
            <div class="brand-logo-container">
                <img src="logo.png" alt="CarbonWise Logo">
            </div>
            <span class="logo-text">CARBONWISE</span>
        </div>
        <div class="menu-items">
            <a href="dashboard.php" class="menu-item"><i class="fa-solid fa-border-all"></i> Dashboard</a>
            <a href="activity_input.php" class="menu-item active"><i class="fa-solid fa-pen-to-square"></i> Activity Input</a>
            <a href="reports.php" class="menu-item"><i class="fa-solid fa-chart-simple"></i> Reports</a>
            <a href="mitigation_strategies.php" class="menu-item"><i class="fa-solid fa-lightbulb"></i> Mitigation Strategies</a>
            <a href="profile.php" class="menu-item"><i class="fa-solid fa-circle-user"></i> View Profile</a>
            
            <div class="sidebar-footer">
                <div class="sidebar-divider"></div>
                <button class="theme-toggle-item" id="themeToggle" title="Toggle Light/Dark Mode">
                    <i class="fa-solid fa-sun" id="themeIcon"></i> <span id="themeText">Dark Mode</span>
                </button>
                <button type="button" onclick="confirmLogout()" class="menu-item" style="cursor: pointer;"><i class="fa-solid fa-right-from-bracket"></i> Log Out</button>
            </div>
        </div>
    </div>

    <div class="main-workspace">
        <div class="top-navbar">
            <div style="display: flex; align-items: center;">
                <button class="mobile-toggle-btn" onclick="toggleMobileSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="header-title-area">
                    <h2>Activity Input</h2>
                    <p>Record and manage operational data</p>
                </div>
            </div>
            
            <div class="user-nav-profile">
                <div class="notification-container">
                    <div class="notification-bell" id="bellBtn" role="button" tabindex="0" aria-label="Notifications" aria-haspopup="true">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                    <span class="notification-badge" id="cwnBadge" style="display: none;">0</span>

                    <div class="cwn-dropdown" id="cwnMenu">
                        <div class="cwn-header">
                            <span class="cwn-title">Notifications</span>
                            <button type="button" class="cwn-markall" id="cwnMarkAll" hidden>Mark all as read</button>
                        </div>
                        <div class="cwn-list" id="cwnList">
                            <div class="cwn-empty">Loading notifications...</div>
                        </div>
                        <button type="button" class="cwn-footer" id="cwnFooter" hidden>Show all notifications</button>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="avatar-circle-nav">
                        <?php if (!empty($avatar_url)): ?>
                            <img src="<?= htmlspecialchars($avatar_url) ?>" alt="User Avatar">
                        <?php else: ?>
                            <?= htmlspecialchars($initials) ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-info-text">
                        <h4><?= htmlspecialchars($full_name) ?></h4>
                        <p><?= htmlspecialchars($role) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="workspace-body">
            <div class="progress-wrapper">
                <div class="progress-container">
                    <div id="dynamicProgressBar" class="progress-bar"></div>
                </div>
                <p class="progress-text">Green Points: <span id="greenPointsLabel">100</span></p>
            </div>

            <div class="content-container">
                <div class="input-card cw-card">
                    <div class="cw-card-head">
                        <span class="cw-card-icon"><i class="fa-solid fa-car"></i></span>
                        <div class="cw-card-titles">
                            <h3 id="transportCardTitle" class="cw-card-title">Ride 1: Transport Calculator</h3>
                            <p class="cw-card-sub">Record every ride you took today. Need 2 or more vehicles to get to school? Add one ride for each vehicle.</p>
                        </div>
                    </div>
                    
                    <div class="transport-grid-layout">
                        <!-- Left Column: Controls & Dynamic Route Labels -->
                        <div class="transport-controls-col">
                            <div class="cw-form cw-form-stack">
                                <div class="cw-field">
                                    <label for="vehicleType">Vehicle Used for this Ride (select first)</label>
                                    <select id="vehicleType" class="cw-select" required onchange="calculateRouteIfPossible()">
                                        <option value="" selected disabled>Select Vehicle Type</option>
                                        <option value="0.103">Motorcycle (0.103 kg CO2e/km)</option>
                                        <option value="0.095">Tricycle (0.095 kg CO2e/km)</option>
                                        <option value="0.035">Modern Jeepney (0.035 kg CO2e/km)</option>
                                        <option value="0.171">Private Car (Gasoline) (0.171 kg CO2e/km)</option>
                                        <option value="0.080">Public Jeepney (0.08 kg CO2e/km)</option>
                                        <option value="0.050">Public Bus (0.05 kg CO2e/km)</option>
                                    </select>
                                </div>
                                <div class="cw-field">
                                    <label for="startSelect">Starting Point of this Ride</label>
                                    <select id="startSelect" class="cw-select" onchange="handlePointSelect('start')"></select>
                                </div>
                                <div class="cw-field">
                                    <label for="endSelect">Destination of this Ride</label>
                                    <select id="endSelect" class="cw-select" onchange="handlePointSelect('end')"></select>
                                </div>
                                <div class="cw-field">
                                    <label for="transportDistance">Distance of this Ride (KM)</label>
                                    <input type="number" id="transportDistance" class="cw-input" step="0.01" placeholder="Select a vehicle first" disabled>
                                </div>
                            </div>

                            <div class="route-labels-box">
                                <div class="route-label-item pin-target active" id="startPinBox" onclick="setActivePoint('start')">
                                    <div class="route-label-title" id="startLabelTitle"><i class="fa-solid fa-location-dot"></i> Starting Point</div>
                                    <div id="startLabel" class="route-label-value">Click the map or choose a campus</div>
                                    <input type="text" id="startNameInput" class="ride-name-input" maxlength="40" placeholder="Name this place (e.g., Home, Palengke)" oninput="onPointNameInput()">
                                </div>
                                <div class="route-label-item pin-target" id="endPinBox" onclick="setActivePoint('end')">
                                    <div class="route-label-title" id="endLabelTitle"><i class="fa-solid fa-flag-checkered"></i> Destination</div>
                                    <div id="endLabel" class="route-label-value">Click the map or choose a campus</div>
                                    <input type="text" id="endNameInput" class="ride-name-input" maxlength="40" placeholder="Name this place (e.g., Home, Palengke)" oninput="onPointNameInput()">
                                </div>
                            </div>
                            <button type="button" class="ride-clear-btn" onclick="clearRidePoints()"><i class="fa-solid fa-rotate-left"></i> Clear points</button>

                            <button type="button" class="cw-btn-add" id="addTransportBtn" onclick="addTransport()"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg><span>Add Activity</span></button>
                        </div>

                        <!-- Right Column: Map & Instructions -->
                        <div class="transport-map-col">
                            <div class="map-section-wrapper">
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;" id="mapInstructions">
                                    <i class="fa-solid fa-map-location-dot" style="color: var(--accent-green);"></i> <strong>Ride 1:</strong> Set your starting point and destination.
                                </p>
                                <div id="map"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Office Resource Card -->
                <div class="input-card cw-card">
                    <div class="cw-card-head">
                        <span class="cw-card-icon"><i class="fa-solid fa-laptop"></i></span>
                        <div class="cw-card-titles">
                            <h3 class="cw-card-title">Office Resource</h3>
                            <p class="cw-card-sub">Record the office equipment you used today.</p>
                        </div>
                    </div>
                    <div class="cw-form">
                        <div class="cw-field">
                            <label for="officeType">Office Resource / Appliance</label>
                            <select id="officeType" class="cw-select" required>
                                <option value="" selected disabled>Select Appliance/Hardware</option>
                                <optgroup label="Laptops">
                                    <option value="40">Ultra-light / Netbook (40W)</option>
                                    <option value="60">Standard Business Laptop (60W)</option>
                                    <option value="120">Performance Laptop (120W)</option>
                                    <option value="300">Gaming / High-End Workstation Laptop (300W)</option>
                                </optgroup>
                                <optgroup label="Desktop Computers & Monitors">
                                    <option value="100">Standard Office PC CPU (100W)</option>
                                    <option value="200">Mid-Range Workstation CPU (200W)</option>
                                    <option value="600">High-End / Gaming PC CPU (600W)</option>
                                    <option value="40">Mini PC - NUC/Mac Mini (40W)</option>
                                    <option value="20">18.5" to 20" LED Monitor (20W)</option>
                                    <option value="30">22" to 24" LED Monitor (30W)</option>
                                    <option value="50">27" and larger LED Monitor (50W)</option>
                                    <option value="100">Old CRT Monitor (100W)</option>
                                </optgroup>
                                <optgroup label="Air Conditioners (Window & Split)">
                                    <option value="500">Window Type AC - 0.5 HP (500W)</option>
                                    <option value="1000">Window Type AC - 1.0 HP (1000W)</option>
                                    <option value="1500">Window Type AC - 1.5 HP (1500W)</option>
                                    <option value="2000">Window Type AC - 2.0 HP (2000W)</option>
                                    <option value="900">Inverter Split Type AC - 1.0 HP (900W)</option>
                                    <option value="1300">Inverter Split Type AC - 1.5 HP (1300W)</option>
                                    <option value="1800">Inverter Split Type AC - 2.0 HP (1800W)</option>
                                    <option value="2300">Inverter Split Type AC - 2.5 HP (2300W)</option>
                                    <option value="3300">Floor Standing AC - 3.0 HP (3300W)</option>
                                    <option value="5300">Floor Standing AC - 5.0 HP (5300W)</option>
                                </optgroup>
                                <optgroup label="Smart Displays & Projectors">
                                    <option value="250">Viewboard Smart Screen 55" to 65" (250W)</option>
                                    <option value="350">Viewboard Smart Screen 75" (350W)</option>
                                    <option value="500">Viewboard Smart Screen 86" (500W)</option>
                                    <option value="800">Viewboard Smart Screen 98" and above (800W)</option>
                                    <option value="300">Standard DLP/LCD Projector (300W)</option>
                                    <option value="200">Projector - Eco-Mode (200W)</option>
                                    <option value="500">Large Venue Projector (500W)</option>
                                </optgroup>
                                <optgroup label="Electric Fans">
                                    <option value="65">AC Motor Fan (65W)</option>
                                    <option value="30">DC Motor Fan (30W)</option>
                                    <option value="80">Ceiling Fan (80W)</option>
                                    <option value="60">Stand Fan (60W)</option>
                                    <option value="55">Wall Fan (55W)</option>
                                    <option value="30">Exhaust Fan (30W)</option>
                                    <option value="50">Tower Fan (50W)</option>
                                    <option value="40">Desk Fan (40W)</option>
                                    <option value="55">Bladeless Fan (55W)</option>
                                    <option value="130">Misting Fan (130W)</option>
                                    <option value="200">Industrial Fan (200W)</option>
                                </optgroup>
                                <optgroup label="Lights">
                                    <option value="10">Standard LED Bulb (10W)</option>
                                    <option value="15">LED Tube T8/T5 (15W)</option>
                                    <option value="12">LED Downlight/Panel (12W)</option>
                                    <option value="100">High-bay Gym/Halls LED (100W)</option>
                                    <option value="18">CFL Compact Fluorescent (18W)</option>
                                </optgroup>
                                <optgroup label="Printers & Scanners">
                                    <option value="100">Scanner - Ready/Sleep Mode (100W)</option>
                                    <option value="20">Flatbed Scanner (20W)</option>
                                    <option value="50">High-speed Document Scanner (50W)</option>
                                    <option value="30">Inkjet Printer - Active (30W)</option>
                                    <option value="400">Laser Printer B&W - Active (400W)</option>
                                    <option value="500">Color Laser Printer - Active (500W)</option>
                                    <option value="1000">Mid-size Office MFP Copier (1000W)</option>
                                    <option value="2000">High-volume Photocopier (2000W)</option>
                                </optgroup>
                                <optgroup label="Audio Systems">
                                    <option value="20">Desktop/PC Speakers (20W)</option>
                                    <option value="60">Wall-mounted Classroom Speakers (60W)</option>
                                    <option value="1000">Large PA System Events/Gym (1000W)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="cw-field">
                            <label for="officeUsageUnits">Duration of Usage (Hours)</label>
                            <input type="number" id="officeUsageUnits" class="cw-input" placeholder="e.g., 2.5" step="0.1" min="0.1">
                        </div>
                        <button type="button" class="cw-btn-add" onclick="addOffice()"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg><span>Add Activity</span></button>
                    </div>
                </div>

                <!-- Food Consumption Card -->
                <div class="input-card cw-card">
                    <div class="cw-card-head">
                        <span class="cw-card-icon"><i class="fa-solid fa-utensils"></i></span>
                        <div class="cw-card-titles">
                            <h3 class="cw-card-title">Food Consumption</h3>
                            <p class="cw-card-sub">Record what you consumed today.</p>
                        </div>
                    </div>
                    <div class="cw-form">
                        <div class="cw-field">
                            <label for="foodMealPeriodSelect">Meal Period</label>
                            <select id="foodMealPeriodSelect" class="cw-select" required>
                                <option value="" selected disabled>Select meal period</option>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Snack">Snack</option>
                            </select>
                        </div>
                        <div class="cw-field">
                            <label for="foodType">Food Type</label>
                            <select id="foodType" class="cw-select" required>
                                <option value="" selected disabled>Select Food Item</option>
                                
                                <optgroup label="1. High-Impact Proteins (Red Meats)">
                                    <option value="9.0" data-unit="cup">Beef (Beef Herd) (9.0 kg CO2e/cup)</option>
                                    <option value="3.68" data-unit="cup">Lamb & Mutton (3.68 kg CO2e/cup)</option>
                                    <option value="3.17" data-unit="cup">Beef (Dairy Herd) (3.17 kg CO2e/cup)</option>
                                </optgroup>

                                <optgroup label="2. Moderate-Impact Proteins (Dairy & Poultry)">
                                    <option value="2.31" data-unit="cup">Cheese (2.31 kg CO2e/cup)</option>
                                    <option value="1.05" data-unit="cup">Pork (1.05 kg CO2e/cup)</option>
                                    <option value="0.84" data-unit="cup">Poultry (Chicken / Turkey) (0.84 kg CO2e/cup)</option>
                                    <option value="1.08" data-unit="cup">Eggs (1.08 kg CO2e/cup)</option>
                                    <option value="0.75" data-unit="cup">Fish (Farmed) (0.75 kg CO2e/cup)</option>
                                </optgroup>

                                <optgroup label="3. Staples and Plant-Based Proteins">
                                    <option value="0.70" data-unit="cup">Rice (Flooded) (0.70 kg CO2e/cup)</option>
                                    <option value="0.75" data-unit="cup">Tofu (Soy-based) (0.75 kg CO2e/cup)</option>
                                    <option value="0.38" data-unit="cup">Groundnuts / Peanuts (0.38 kg CO2e/cup)</option>
                                    <option value="0.30" data-unit="cup">Pulses (Beans / Peas) (0.30 kg CO2e/cup)</option>
                                </optgroup>

                                <optgroup label="4. Grains, Vegetables, and Fruits">
                                    <option value="0.17" data-unit="cup">Wheat & Rye (Bread) (0.17 kg CO2e/cup)</option>
                                    <option value="0.16" data-unit="cup">Maize (Corn) (0.16 kg CO2e/cup)</option>
                                    <option value="0.08" data-unit="cup">Potatoes (0.08 kg CO2e/cup)</option>
                                    <option value="0.06" data-unit="cup">Apples / Bananas (0.06 kg CO2e/cup)</option>
                                    <option value="0.06" data-unit="cup">Root Vegetables (0.06 kg CO2e/cup)</option>
                                </optgroup>

                                <optgroup label="5. Beverages and Discretionary Items">
                                    <option value="2.64" data-unit="cup">Coffee (2.64 kg CO2e/cup)</option>
                                    <option value="2.85" data-unit="cup">Dark Chocolate (2.85 kg CO2e/cup)</option>
                                    <option value="0.77" data-unit="cup">Milk (Bovine) (0.77 kg CO2e/cup)</option>
                                    <option value="0.24" data-unit="cup">Soy Milk (0.24 kg CO2e/cup)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="cw-field cw-field-dt">
                            <div class="cw-pill cw-pill-date" title="Entries are recorded for today">
                                <i class="fa-regular fa-calendar"></i>
                                <span><?= htmlspecialchars($today_date) ?></span>
                            </div>
                            <label class="cw-pill cw-pill-time" for="foodTime">
                                <i class="fa-regular fa-clock"></i>
                                <span id="foodTimeText">--:--</span>
                                <input type="time" id="foodTime" aria-label="Time consumed">
                            </label>
                        </div>
                        <div class="cw-field">
                            <label for="foodServings">Amount / Serving (Cups)</label>
                            <input type="number" id="foodServings" class="cw-input" placeholder="e.g., 1 cup" step="0.1" min="0.1">
                        </div>
                        <button type="button" class="cw-btn-add" onclick="addFood()"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg><span>Add Activity</span></button>
                    </div>
                </div>

                <form id="supabaseMasterForm" method="POST" action="activity_input.php" onsubmit="handleFormSubmission(event)">
                    <input type="hidden" name="action" value="save_emissions">
                    <input type="hidden" id="hiddenTransport" name="total_transport" value="0">
                    <input type="hidden" id="hiddenOffice" name="total_office" value="0">
                    <input type="hidden" id="hiddenFood" name="total_food" value="0">
                    
                    <!-- Every activity the user adds (any number of trips per day) is sent here as JSON -->
                    <input type="hidden" id="entriesPayload" name="entries" value="[]">

                    <div class="list-card">
                        <h3 style="color: var(--accent-green); margin-bottom: 4px;">Your Added Activities</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Review the activities you have recorded for today.</p>
                        
                        <div id="activityCardContainer" class="activity-card-list">
                            <?php if (!empty($today_records)): ?>
                                <?php foreach ($today_records as $rec): ?>
                                    <?php if (!empty($rec['transport_item'])): ?>
                                        <div class="activity-card-item">
                                            <div class="activity-icon-box transport"><i class="fa-solid fa-car"></i></div>
                                            <div class="activity-details">
                                                <div class="activity-title"><?= htmlspecialchars($rec['transport_item']) ?></div>
                                                <div class="activity-subtext">Transportation · <?= number_format($rec['transportation'], 2) ?> kg CO₂e</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($rec['office_item'])): ?>
                                        <div class="activity-card-item">
                                            <div class="activity-icon-box office"><i class="fa-solid fa-laptop"></i></div>
                                            <div class="activity-details">
                                                <div class="activity-title"><?= htmlspecialchars($rec['office_item']) ?></div>
                                                <div class="activity-subtext">Office Resource · <?= number_format($rec['electricity'], 2) ?> kg CO₂e</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($rec['food_item'])): ?>
                                        <div class="activity-card-item">
                                            <div class="activity-icon-box food"><i class="fa-solid fa-utensils"></i></div>
                                            <div class="activity-details">
                                                <div class="activity-title"><?= htmlspecialchars($rec['food_item']) ?></div>
                                                <div class="activity-subtext">Food Consumption<?= !empty($rec['food_consumed_at']) && strtotime($rec['food_consumed_at']) ? ' · ' . date('g:i A', strtotime($rec['food_consumed_at'])) : '' ?> · <?= number_format($rec['food'], 2) ?> kg CO₂e</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn-calculate">Calculate my Carbon Emissions</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleMobileSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        const themeToggleBtn = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateToggleElement(currentTheme);

        themeToggleBtn.addEventListener('click', () => {
            let theme = document.documentElement.getAttribute('data-theme');
            let newTheme = theme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateToggleElement(newTheme);
        });

        function updateToggleElement(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'fa-solid fa-sun';
                themeText.textContent = 'Light Mode';
            } else {
                themeIcon.className = 'fa-solid fa-moon';
                themeText.textContent = 'Dark Mode';
            }
        }

        function confirmLogout() {
            let activeTheme = localStorage.getItem('theme') || 'light';
            let popupBg = activeTheme === 'dark' ? '#121A16' : '#ffffff';
            let popupText = activeTheme === 'dark' ? '#F3F4F6' : '#333333';

            Swal.fire({
                title: 'Log Out',
                text: "Are you sure you want to exit your session?",
                icon: 'warning',
                iconColor: '#f42828',
                showCancelButton: true,
                confirmButtonColor: '#2D6A4F',
                cancelButtonColor: '#BA181B',
                confirmButtonText: 'Yes, log out',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false,
                allowEscapeKey: false,
                heightAuto: false,
                background: popupBg,
                color: popupText,
                backdrop: `rgba(0, 0, 0, 0.5)`,
                customClass: {
                    container: 'logout-swal-container'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Logging out...',
                        text: 'Redirecting to login screen...',
                        allowOutsideClick: false,
                        heightAuto: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    window.location.replace('logout.php?t=' + new Date().getTime());
                }
            });
        }

        let totals = { transport: 0, office: 0, food: 0 };
        let entries = [];   // every activity added on this page, one item per card
        const SAVED_TRANSPORT_COUNT = <?= (int)$saved_transport_count ?>;
        let points = 100;
        const GHG_ELECTRICITY_FACTOR = 0.7122; 
        
        const map = L.map('map').setView([13.7565, 121.0583], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let routingControl = null;
        let routeToken = 0;

        // Every vehicle used is its own "ride". If a student needs 2 or more vehicles to reach school,
        // they add one ride per vehicle (e.g. Home -> terminal by tricycle, terminal -> campus by jeepney).
        // The next ride automatically starts where the previous ride ended.
        const CAMPUSES = [
            { name: 'Lipa Campus',           coords: '13.9575,121.1610' },
            { name: 'Pablo Borbon Campus',   coords: '13.7542,121.0547' },
            { name: 'Alangilan Campus',      coords: '13.7745,121.0772' },
            { name: 'LIMA Campus',           coords: '14.0498,121.1292' },
            { name: 'ARASOF Nasugbu Campus', coords: '14.0670,120.6260' },
            { name: 'JPLPC Malvar Campus',   coords: '14.0450,121.1575' },
            { name: 'Lemery Campus',         coords: '13.8821,120.9168' },
            { name: 'Rosario Campus',        coords: '13.8456,121.2038' },
            { name: 'San Juan Campus',       coords: '13.8248,121.3283' },
            { name: 'Balayan Campus',        coords: '13.9372,120.7325' },
            { name: 'Lobo Campus',           coords: '13.6415,121.1922' },
            { name: 'Mabini Campus',         coords: '13.7502,120.9405' }
        ];
        const DEFAULT_POINT_TEXT = 'Click the map or choose a campus';

        // Ride numbers continue from the rides already saved today
        const nextTripNumber = () => SAVED_TRANSPORT_COUNT + entries.filter(e => e.category === 'transport').length + 1;

        const ridePts = { start: null, end: null };   // { latlng, name, selectValue, marker }
        let activePoint = 'start';                   // which point the next map click will set

        function pointIcon(color) {
            return L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-' + color + '.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/libs/leaflet/1.7.1/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });
        }

        // Name shown for a point: the student's own name for a pinned place, otherwise the default name
        function pointDisplayName(which) {
            const pt = ridePts[which];
            if (!pt) return '';
            if (pt.selectValue !== 'pin') return pt.name;
            const custom = document.getElementById(which + 'NameInput').value.trim();
            return custom || pt.name;
        }

        function escapeHtml(text) {
            return String(text == null ? '' : text).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function refreshPointLabel(which) {
            document.getElementById(which + 'Label').innerText = ridePts[which] ? pointDisplayName(which) : DEFAULT_POINT_TEXT;
        }

        function onPointNameInput() {
            refreshPointLabel('start');
            refreshPointLabel('end');
        }

        function buildPointSelects() {
            ['startSelect', 'endSelect'].forEach(function (id) {
                const sel = document.getElementById(id);
                sel.innerHTML = '<option value="pin">Pin on the map</option>' +
                    '<optgroup label="BSU Campuses">' +
                    CAMPUSES.map(function (c) { return '<option value="' + c.coords + '">' + c.name + '</option>'; }).join('') +
                    '</optgroup>';
                sel.value = 'pin';
            });
        }

        function clearRoute() {
            routeToken++;
            if (routingControl) { map.removeControl(routingControl); routingControl = null; }
        }

        function updateRideHeader() {
            const n = nextTripNumber();
            document.getElementById('transportCardTitle').innerText = 'Ride ' + n + ': Transport Calculator';
            const which = activePoint === 'start' ? 'starting point' : 'destination';
            document.getElementById('mapInstructions').innerHTML =
                '<i class="fa-solid fa-map-location-dot" style="color: var(--accent-green);"></i> ' +
                '<strong>Ride ' + n + ':</strong> Select the vehicle first, then set your <strong>' + which + '</strong> by clicking the map or choosing a campus. Name a pinned place if you like (e.g., Home, Palengke). ' +
                'Using 2 or more vehicles? Add one ride for each vehicle &mdash; the next ride starts where this one ends.';
        }

        function setActivePoint(which) {
            activePoint = which;
            syncDistanceField();
            document.getElementById('startPinBox').classList.toggle('active', which === 'start');
            document.getElementById('endPinBox').classList.toggle('active', which === 'end');
            updateRideHeader();
        }

        function clearPoint(which) {
            if (ridePts[which] && ridePts[which].marker) map.removeLayer(ridePts[which].marker);
            ridePts[which] = null;
            const nameInput = document.getElementById(which + 'NameInput');
            nameInput.value = '';
            nameInput.style.display = 'none';
            document.getElementById(which + 'Label').innerText = DEFAULT_POINT_TEXT;
            document.getElementById(which + 'Select').value = 'pin';
        }

        function setPoint(which, latlng, name, selectValue) {
            clearRoute();
            const prevWasPin = !!(ridePts[which] && ridePts[which].selectValue === 'pin');
            if (ridePts[which] && ridePts[which].marker) map.removeLayer(ridePts[which].marker);

            const isStart = (which === 'start');
            const marker = L.marker(latlng, { icon: pointIcon(isStart ? 'blue' : 'red') }).addTo(map);
            marker.bindPopup('<b>' + (isStart ? 'Starting Point' : 'Destination') + ': ' + name + '</b>', { closeButton: false }).openPopup();

            ridePts[which] = { latlng: L.latLng(latlng), name: name, selectValue: selectValue, marker: marker };
            document.getElementById(which + 'Select').value = selectValue;

            const nameInput = document.getElementById(which + 'NameInput');
            if (selectValue === 'pin') {
                if (!prevWasPin) nameInput.value = '';
                nameInput.style.display = 'block';
            } else {
                nameInput.value = '';
                nameInput.style.display = 'none';
            }
            refreshPointLabel(which);
            document.getElementById('transportDistance').value = '';

            // once one point is set, move on to the other one if it is still empty
            const other = isStart ? 'end' : 'start';
            setActivePoint(ridePts[other] ? which : other);
            calculateRouteIfPossible();
        }

        function handlePointSelect(which) {
            const sel = document.getElementById(which + 'Select');
            clearRoute();
            document.getElementById('transportDistance').value = '';

            if (sel.value === 'pin') {
                clearPoint(which);
                setActivePoint(which);
                return;
            }
            const parts = sel.value.split(',');
            const coords = [parseFloat(parts[0]), parseFloat(parts[1])];
            setPoint(which, coords, sel.options[sel.selectedIndex].text, sel.value);
            map.setView(coords, 13);
        }

        map.on('click', function (e) {
            const name = 'Pin (' + e.latlng.lat.toFixed(4) + ', ' + e.latlng.lng.toFixed(4) + ')';
            setPoint(activePoint, e.latlng, name, 'pin');
        });

        function clearRidePoints() {
            clearRoute();
            clearPoint('start');
            clearPoint('end');
            document.getElementById('transportDistance').value = '';
            setActivePoint('start');
        }

        // After a ride is added: the next ride starts at the previous destination
        function startNextRide() {
            const prevEnd = ridePts.end;
            const prevName = prevEnd ? pointDisplayName('end') : '';
            clearRoute();
            clearPoint('start');
            clearPoint('end');
            document.getElementById('transportDistance').value = '';
            if (prevEnd) {
                setPoint('start', prevEnd.latlng, prevEnd.name, prevEnd.selectValue);
                if (prevEnd.selectValue === 'pin' && prevName !== prevEnd.name) {
                    document.getElementById('startNameInput').value = prevName;   // keep the place name (e.g., Palengke)
                    refreshPointLabel('start');
                }
            } else {
                setActivePoint('start');
            }
        }

        // The distance is only calculated after the student has selected the vehicle for this ride
        function syncDistanceField() {
            const hasVehicle = !!document.getElementById('vehicleType').value;
            const inp = document.getElementById('transportDistance');
            inp.disabled = !hasVehicle;
            inp.placeholder = hasVehicle ? 'Auto-calculates or enter manually' : 'Select a vehicle first';
            if (!hasVehicle) inp.value = '';
        }

        function calculateRouteIfPossible() {
            syncDistanceField();
            if (!document.getElementById('vehicleType').value) { clearRoute(); return; }
            if (!(ridePts.start && ridePts.end)) return;

            const startNode = ridePts.start.latlng;
            const endNode = ridePts.end.latlng;

            clearRoute();
            const token = routeToken;

            routingControl = L.Routing.control({
                waypoints: [startNode, endNode],
                router: L.Routing.osrmv1({
                    serviceUrl: 'https://router.project-osrm.org/route/v1',
                    profile: 'driving'
                }),
                lineOptions: {
                    styles: [{ color: '#2D6A4F', opacity: 0.8, weight: 6 }]
                },
                createMarker: function() { return null; },
                show: false,
                addWaypoints: false,
                routeWhileDragging: false
            }).addTo(map);

            routingControl.on('routesfound', function(e) {
                if (token !== routeToken) return;   // points changed meanwhile
                const routes = e.routes;
                if (routes && routes.length > 0) {
                    document.getElementById('transportDistance').value = (routes[0].summary.totalDistance / 1000).toFixed(2);
                }
            });

            routingControl.on('routingerror', function() {
                if (token !== routeToken) return;
                document.getElementById('transportDistance').value = (startNode.distanceTo(endNode) / 1000).toFixed(2);
            });
        }

        function updateGreenPointsUI() {
            let collectiveFootprint = totals.transport + totals.office + totals.food;
            let deduction = Math.min(collectiveFootprint * 4, 90); 
            points = Math.round(100 - deduction);
            document.getElementById('greenPointsLabel').innerText = points;
            document.getElementById('dynamicProgressBar').style.width = points + '%';
        }

        function addTransport() {
            let activeTheme = localStorage.getItem('theme') || 'light';

            const vehicleSel = document.getElementById('vehicleType');
            const distInp = document.getElementById('transportDistance');

            if(!vehicleSel.value) {
                Swal.fire({
                    title: 'Vehicle Type Needed',
                    text: 'Please select what vehicle you used for your trip.',
                    icon: 'warning',
                    confirmButtonColor: '#52B788',
                    background: activeTheme === 'dark' ? '#121A16' : '#ffffff',
                    color: activeTheme === 'dark' ? '#F3F4F6' : '#333333'
                });
                return;
            }
            
            if(!distInp.value || parseFloat(distInp.value) <= 0) {
                Swal.fire({
                    title: 'Route Distance Missing',
                    text: 'Please ensure your start and destination points are fully set on the map to calculate your distance.',
                    icon: 'warning',
                    confirmButtonColor: '#52B788',
                    background: activeTheme === 'dark' ? '#121A16' : '#ffffff',
                    color: activeTheme === 'dark' ? '#F3F4F6' : '#333333'
                });
                return;
            }
            
            let kmDistance = parseFloat(distInp.value);
            let vehicleFactor = parseFloat(vehicleSel.value);
            let vehicleLabel = vehicleSel.options[vehicleSel.selectedIndex].text;
            
            let calculatedEmission = kmDistance * vehicleFactor;

            const rideNo = nextTripNumber();
            const routeSummary = (ridePts.start && ridePts.end) ? `${escapeHtml(pointDisplayName('start'))} → ${escapeHtml(pointDisplayName('end'))}` : 'Manual distance entry';
            const vehicleShort = vehicleLabel.split(' (')[0];
            createListItem('activityCardContainer',
                { category: 'transport', item: vehicleLabel, emission: calculatedEmission },
                `Ride ${rideNo}: ${routeSummary} · ${vehicleShort}`, `${calculatedEmission.toFixed(2)} kg CO₂e (${kmDistance} km)`);

            distInp.value = "";
            vehicleSel.value = "";

            // next ride starts where this one ended (for 2 or more vehicles to reach school)
            startNextRide();

            Swal.fire({
                title: 'Ride Added!',
                text: 'Riding another vehicle? Add the next ride — it starts where this one ended. When you are done, click "Calculate my Carbon Emissions" to save.',
                icon: 'success',
                confirmButtonColor: '#2D6A4F',
                background: activeTheme === 'dark' ? '#121A16' : '#ffffff',
                color: activeTheme === 'dark' ? '#F3F4F6' : '#333333'
            });

            updateGreenPointsUI();
        }

        function addOffice() {
            const typeSel = document.getElementById('officeType');
            const hoursInp = document.getElementById('officeUsageUnits');
            let activeTheme = localStorage.getItem('theme') || 'light';

            if(!typeSel.value || !hoursInp.value || parseFloat(hoursInp.value) <= 0) {
                Swal.fire({
                    title: 'Missing Details',
                    text: 'Please select an appliance and enter how many hours it was used.',
                    icon: 'warning',
                    confirmButtonColor: '#52B788',
                    background: activeTheme === 'dark' ? '#121A16' : '#ffffff',
                    color: activeTheme === 'dark' ? '#F3F4F6' : '#333333'
                });
                return;
            }
            let itemText = typeSel.options[typeSel.selectedIndex].text;
            let calculatedValue = (parseFloat(typeSel.value) * parseFloat(hoursInp.value) / 1000) * GHG_ELECTRICITY_FACTOR;
            createListItem('activityCardContainer',
                { category: 'office', item: itemText, emission: calculatedValue },
                `${itemText} (${hoursInp.value}h)`, `${calculatedValue.toFixed(4)} kg CO₂e`);

            hoursInp.value = "";
            typeSel.value = "";
            updateGreenPointsUI();
        }

        // JavaScript Function for Food Emissions
        function addFood() {
            const mealPeriodSel = document.getElementById('foodMealPeriodSelect');
            const typeSel = document.getElementById('foodType');
            const servingsInp = document.getElementById('foodServings');
            let activeTheme = localStorage.getItem('theme') || 'light';

            if (!mealPeriodSel.value) {
                Swal.fire({
                    title: 'Meal Period Needed',
                    text: 'Please choose whether this entry is for Breakfast, Lunch, Dinner, or a Snack.',
                    icon: 'warning',
                    confirmButtonColor: '#52B788',
                    background: activeTheme === 'dark' ? '#121A16' : '#ffffff',
                    color: activeTheme === 'dark' ? '#F3F4F6' : '#333333'
                });
                return;
            }

            if (!typeSel.value || !servingsInp.value || parseFloat(servingsInp.value) <= 0) {
                Swal.fire({
                    title: 'Missing Details',
                    text: 'Please pick a food type and enter the number of cups consumed.',
                    icon: 'warning',
                    confirmButtonColor: '#52B788',
                    background: activeTheme === 'dark' ? '#121A16' : '#ffffff',
                    color: activeTheme === 'dark' ? '#F3F4F6' : '#333333'
                });
                return;
            }

            const timeInp = document.getElementById('foodTime');
            if (!timeInp.value) {
                Swal.fire({
                    title: 'Time Needed',
                    text: 'Please set the time you consumed this food.',
                    icon: 'warning',
                    confirmButtonColor: '#52B788',
                    background: activeTheme === 'dark' ? '#121A16' : '#ffffff',
                    color: activeTheme === 'dark' ? '#F3F4F6' : '#333333'
                });
                return;
            }
            const timeLabel = formatTime12(timeInp.value);

            let mealText = mealPeriodSel.value;
            let foodText = typeSel.options[typeSel.selectedIndex].text.split(' (')[0];
            
            let cupsCount = parseFloat(servingsInp.value);
            let emissionFactorPerCup = parseFloat(typeSel.value);
            
            // Direct computation: Cups x Emission Factor per Cup
            let calculatedValue = cupsCount * emissionFactorPerCup;
            let unitDisplay = `${cupsCount} cup${cupsCount > 1 ? 's' : ''}`;

            createListItem('activityCardContainer',
                { category: 'food', item: foodText, emission: calculatedValue, meal: mealText, consumed_at: FOOD_DATE + ' ' + timeInp.value + ':00' },
                `[${mealText} · ${timeLabel}] ${foodText} (${unitDisplay})`, `${calculatedValue.toFixed(2)} kg CO₂e`);

            servingsInp.value = "";
            mealPeriodSel.value = "";
            typeSel.value = "";
            setFoodTimeNow();
            updateGreenPointsUI();
        }

        // ----- Food time picker helpers -----
        const FOOD_DATE = <?= json_encode($today_date) ?>;

        function formatTime12(hhmm) {
            const parts = (hhmm || '').split(':');
            if (parts.length < 2) return '--:--';
            let h = parseInt(parts[0], 10);
            const m = parts[1].slice(0, 2);
            const suffix = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return h + ':' + m + ' ' + suffix;
        }

        // The food time follows the real clock until the student picks a time manually.
        // It goes back to the live clock after an activity is added.
        let foodTimeAuto = true;

        function updateFoodTimeFromClock() {
            const d = new Date();
            const hh = String(d.getHours()).padStart(2, '0');
            const mm = String(d.getMinutes()).padStart(2, '0');
            const inp = document.getElementById('foodTime');
            if (inp.value !== hh + ':' + mm) {
                inp.value = hh + ':' + mm;
                document.getElementById('foodTimeText').textContent = formatTime12(inp.value);
            }
        }

        function setFoodTimeNow() {
            foodTimeAuto = true;
            updateFoodTimeFromClock();
        }

        (function initFoodTime() {
            const inp = document.getElementById('foodTime');

            // A manual change stops the live clock
            const onManualChange = () => {
                foodTimeAuto = false;
                document.getElementById('foodTimeText').textContent = inp.value ? formatTime12(inp.value) : '--:--';
            };
            inp.addEventListener('input', onManualChange);
            inp.addEventListener('change', onManualChange);

            // Clicking anywhere on the time pill opens the time picker
            inp.addEventListener('click', () => {
                if (typeof inp.showPicker === 'function') {
                    try { inp.showPicker(); } catch (err) {}
                }
            });

            setFoodTimeNow();

            // Keep the clock moving (paused while the student is choosing a time)
            setInterval(() => {
                if (foodTimeAuto && !document.hidden && document.activeElement !== inp) {
                    updateFoodTimeFromClock();
                }
            }, 1000);
        })();

        // Rebuilds the totals and the hidden JSON payload from the entries list
        function syncEntries() {
            totals = { transport: 0, office: 0, food: 0 };
            entries.forEach(e => { totals[e.category] += e.emission; });
            document.getElementById('hiddenTransport').value = totals.transport.toFixed(4);
            document.getElementById('hiddenOffice').value = totals.office.toFixed(4);
            document.getElementById('hiddenFood').value = totals.food.toFixed(4);
            document.getElementById('entriesPayload').value = JSON.stringify(entries);
        }

        // Single-Column Activity Card Renderer Function
        function createListItem(containerId, entry, labelText, numericalText) {
            const container = document.getElementById(containerId);
            const category = entry.category;
            
            let iconClass = 'fa-car';
            let categoryTitle = 'Transportation';
            
            if (category === 'office') {
                iconClass = 'fa-laptop';
                categoryTitle = 'Office Resource';
            } else if (category === 'food') {
                iconClass = 'fa-utensils';
                categoryTitle = 'Food Consumption';
            }

            entries.push(entry);
            syncEntries();

            const cardDiv = document.createElement('div');
            cardDiv.className = 'activity-card-item';
            cardDiv.innerHTML = `
                <div class="activity-icon-box ${category}">
                    <i class="fa-solid ${iconClass}"></i>
                </div>
                <div class="activity-details">
                    <div class="activity-title">${labelText}</div>
                    <div class="activity-subtext">${categoryTitle} · ${numericalText}</div>
                </div>
                <button type="button" class="btn-delete-card"><i class="fa-solid fa-xmark"></i></button>
            `;

            cardDiv.querySelector('.btn-delete-card').addEventListener('click', function() {
                entries = entries.filter(e => e !== entry);
                syncEntries();
                
                if (category === 'transport') {
                    updateRideHeader();
                }

                cardDiv.remove();
                updateGreenPointsUI();
            });

            container.prepend(cardDiv);
        }

        function handleFormSubmission(event) {
            event.preventDefault();
            let activeTheme = localStorage.getItem('theme') || 'light';

            if (entries.length === 0) {
                Swal.fire({
                    title: 'No Items Added Yet',
                    text: "Please add at least one travel, office, or food entry before calculating your emissions.",
                    icon: 'warning',
                    confirmButtonColor: '#2D6A4F',
                    background: activeTheme === 'dark' ? '#121A16' : '#ffffff',
                    color: activeTheme === 'dark' ? '#F3F4F6' : '#333333'
                });
                return; 
            }

            Swal.fire({
                title: 'Calculating footprints...',
                text: 'Please wait a moment while we save your records.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            let formData = new FormData(document.getElementById('supabaseMasterForm'));
            
            fetch('activity_input.php', { method: 'POST', body: formData })
            .then(async res => {
                const rawText = await res.text();
                try {
                    return JSON.parse(rawText);
                } catch (err) {
                    console.error("Server raw output:", rawText);
                    throw new Error("Invalid server response format.");
                }
            })
            .then(data => {
                if(data.status === 'success') {
                    if (window.cwnRefresh) { window.cwnRefresh(); }
                    Swal.fire({
                        title: 'Success!',
                        text: 'Your carbon emission records have been saved.',
                        icon: 'success',
                        confirmButtonColor: '#2D6A4F',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'dashboard.php';
                    });
                } else {
                    Swal.fire({
                        title: 'Something went wrong',
                        text: data.message || 'We could not save your submission. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#2D6A4F'
                    });
                }
            })
            .catch((error) => {
                Swal.fire({
                    title: 'Submission Error',
                    text: error.message || 'Please check your connection and try submitting again.',
                    icon: 'error',
                    confirmButtonColor: '#2D6A4F'
                });
            });
        }

        // ===== CarbonWise unified notification dropdown (same on every page) =====
        (function () {
            var bell = document.getElementById('bellBtn');
            var menu = document.getElementById('cwnMenu');
            var badge = document.getElementById('cwnBadge');
            var list = document.getElementById('cwnList');
            var footer = document.getElementById('cwnFooter');
            var markAllBtn = document.getElementById('cwnMarkAll');
            if (!bell || !menu || !badge || !list || !footer || !markAllBtn) return;

            var PREVIEW_COUNT = 3;
            var POLL_MS = 15000;
            var state = { items: [], unread: 0, expanded: false, loaded: false };
            var channel = null;
            try { channel = ('BroadcastChannel' in window) ? new BroadcastChannel('carbonwise-notifications') : null; } catch (e) { channel = null; }

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function typeInfo(type) {
                type = String(type || 'info').toLowerCase();
                if (type === 'success' || type === 'achievement' || type === 'progress') return { cls: 't-success', icon: 'fa-circle-check' };
                if (type === 'warning' || type === 'alert') return { cls: 't-warning', icon: 'fa-triangle-exclamation' };
                if (type === 'error' || type === 'danger') return { cls: 't-error', icon: 'fa-circle-xmark' };
                return { cls: 't-info', icon: 'fa-circle-info' };
            }

            function renderBadge() {
                if (state.unread > 0) {
                    badge.textContent = state.unread > 99 ? '99+' : state.unread;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }

            function render() {
                renderBadge();
                menu.classList.toggle('expanded', state.expanded);

                if (!state.items.length) {
                    list.innerHTML = '<div class="cwn-empty">No notifications yet.</div>';
                    footer.hidden = true;
                    markAllBtn.hidden = true;
                    return;
                }

                var visible = state.expanded ? state.items : state.items.slice(0, PREVIEW_COUNT);
                list.innerHTML = visible.map(function (n) {
                    var t = typeInfo(n.type);
                    return '<div class="cwn-item ' + t.cls + ' ' + (n.is_read ? 'read' : 'unread') + '" data-id="' + parseInt(n.id, 10) + '" role="button" tabindex="0">' +
                        '<i class="fa-solid ' + t.icon + ' cwn-icon"></i>' +
                        '<div class="cwn-body">' +
                            '<div class="cwn-item-title">' + esc(n.title || 'Notification') + (n.is_read ? '' : '<span class="cwn-dot"></span>') + '</div>' +
                            '<div class="cwn-msg">' + esc(n.message) + '</div>' +
                            '<div class="cwn-time">' + esc(n.time_ago || n.created_at || '') + '</div>' +
                        '</div>' +
                    '</div>';
                }).join('');

                if (state.items.length > PREVIEW_COUNT) {
                    footer.hidden = false;
                    footer.textContent = state.expanded ? 'Show less' : 'Show all notifications';
                } else {
                    footer.hidden = true;
                }
                markAllBtn.hidden = (state.unread === 0);
            }

            function load() {
                return fetch('get_notifications.php', { credentials: 'same-origin', cache: 'no-store' })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.status === 'success') {
                            state.items = Array.isArray(data.notifications) ? data.notifications : [];
                            state.unread = parseInt(data.unread_count, 10) || 0;
                            state.loaded = true;
                            render();
                        } else if (!state.loaded) {
                            list.innerHTML = '<div class="cwn-empty">Notifications are unavailable right now.</div>';
                        }
                    })
                    .catch(function () {
                        if (!state.loaded) list.innerHTML = '<div class="cwn-empty">Notifications are unavailable right now.</div>';
                    });
            }

            function post(formData) {
                return fetch('update_notifications.php', { method: 'POST', body: formData, credentials: 'same-origin' })
                    .then(function (res) { return res.json(); });
            }

            function tellOtherTabs() {
                try { if (channel) channel.postMessage('changed'); } catch (e) {}
            }

            function markOne(id) {
                var item = state.items.find(function (n) { return parseInt(n.id, 10) === id; });
                if (!item || item.is_read) return;
                item.is_read = true;
                state.unread = Math.max(0, state.unread - 1);
                render();
                var fd = new FormData();
                fd.append('id', id);
                post(fd).then(function (data) {
                    if (data && data.status === 'success') { state.unread = parseInt(data.unread_count, 10) || 0; render(); tellOtherTabs(); }
                    else { load(); }
                }).catch(load);
            }

            function markAll() {
                state.items.forEach(function (n) { n.is_read = true; });
                state.unread = 0;
                render();
                var fd = new FormData();
                fd.append('action', 'mark_all');
                post(fd).then(function (data) {
                    if (data && data.status === 'success') { tellOtherTabs(); }
                    else { load(); }
                }).catch(load);
            }

            function openMenu() { menu.classList.add('show'); load(); }
            function closeMenu() { menu.classList.remove('show'); }

            bell.addEventListener('click', function (e) {
                e.stopPropagation();
                if (menu.classList.contains('show')) closeMenu(); else openMenu();
            });
            bell.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); bell.click(); }
            });
            menu.addEventListener('click', function (e) { e.stopPropagation(); });
            document.addEventListener('click', closeMenu);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenu(); });

            list.addEventListener('click', function (e) {
                var el = e.target.closest('.cwn-item');
                if (el) markOne(parseInt(el.getAttribute('data-id'), 10));
            });
            list.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                var el = e.target.closest('.cwn-item');
                if (el) { e.preventDefault(); markOne(parseInt(el.getAttribute('data-id'), 10)); }
            });
            footer.addEventListener('click', function () { state.expanded = !state.expanded; render(); });
            markAllBtn.addEventListener('click', markAll);

            // Keep every open tab/page in sync
            if (channel) channel.onmessage = function () { load(); };
            document.addEventListener('visibilitychange', function () { if (!document.hidden) load(); });
            setInterval(function () { if (!document.hidden) load(); }, POLL_MS);
            window.cwnRefresh = load;

            load();
        })();

        document.addEventListener("DOMContentLoaded", function() {
            buildPointSelects();
            setActivePoint('start');

            setTimeout(function() {
                map.invalidateSize();
            }, 200);
        });

        window.addEventListener('resize', function() {
            setTimeout(function() {
                map.invalidateSize();
            }, 200);
        });
    </script>
    <script src="pwa-register.js"></script>
</body>
</html>