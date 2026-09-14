<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode([
            "status"  => "error",
            "message" => "Database connection failed. Please try again in a moment."
        ]);
        exit;
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

// Fetch existing daily transportation submissions for today
$today_date = date('Y-m-d');
$today_transport_count = 0;

if (!empty($user_id) && isset($pdo)) {
    try {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM carbon_records WHERE user_id = :user_id AND record_date = :record_date AND transportation > 0");
        $countStmt->execute([':user_id' => $user_id, ':record_date' => $today_date]);
        $today_transport_count = (int)$countStmt->fetchColumn();
    } catch (\PDOException $e) {
        $today_transport_count = 0;
    }
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

    $transportation = (float)($_POST['total_transport'] ?? 0); 
    $electricity    = (float)($_POST['total_office'] ?? 0);
    $food           = (float)($_POST['total_food'] ?? 0);
    $waste          = 0.00;
    $total_emission = $transportation + $electricity + $food + $waste;

    if ($transportation > 0 && $today_transport_count >= 2) {
        echo json_encode([
            "status"  => "error",
            "message" => "Daily limit reached. You can only record up to 2 transportation trips per day."
        ]);
        exit;
    }

    $transport_item   = !empty($_POST['transport_item']) ? $_POST['transport_item'] : null;
    $office_item      = !empty($_POST['office_item']) ? $_POST['office_item'] : null;
    $food_item        = !empty($_POST['food_item']) ? $_POST['food_item'] : null;
    $food_meal_period = !empty($_POST['food_meal_period']) ? $_POST['food_meal_period'] : null;
    $food_consumed_at = !empty($_POST['food_consumed_at']) ? $_POST['food_consumed_at'] : null;

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

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id'           => $user_id,
            ':transportation'    => $transportation,
            ':electricity'       => $electricity,
            ':food'              => $food,
            ':total_emission'    => $total_emission,
            ':record_date'       => $today_date,
            ':created_at'        => $current_timestamp,
            ':updated_at'        => $current_timestamp,
            ':transport_item'    => $transport_item,
            ':office_item'       => $office_item,
            ':food_item'         => $food_item,
            ':food_meal_period'  => $food_meal_period,
            ':food_consumed_at'  => $food_consumed_at
        ]);

        echo json_encode(["status" => "success"]);
        exit;

    } catch (\PDOException $e) {
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

        .sidebar { width: 260px; background-color: var(--bg-sidebar); color: white; display: flex; flex-direction: column; padding: 20px 0; flex-shrink: 0; border-right: 1px solid var(--border-color); }
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
        
        .main-workspace { flex: 1; display: flex; flex-direction: column; height: 100%; overflow: hidden; }
        .top-navbar { height: 75px; background: var(--bg-card); display: flex; align-items: center; justify-content: space-between; padding: 0 40px; flex-shrink: 0; border-bottom: 1px solid var(--border-color); }
        
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
        
        .profile-card { display: flex; align-items: center; gap: 12px; border-left: 1px solid var(--border-color); padding-left: 25px; }
        .avatar-circle-nav { width: 40px; height: 40px; background: var(--input-bg); color: var(--accent-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; overflow: hidden; border: 1px solid var(--accent-green); }
        .avatar-circle-nav img { width: 100%; height: 100%; object-fit: cover; }
        .user-info-text h4 { font-size: 0.95rem; color: var(--text-main); font-weight: 700; }
        .user-info-text p { font-size: 0.8rem; color: var(--text-muted); }

        .workspace-body { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }
        .progress-wrapper { padding: 25px 40px 0 40px; text-align: center; }
        .progress-container { width: 100%; background-color: var(--border-color); height: 16px; border-radius: 8px; overflow: hidden; margin-bottom: 8px; }
        .progress-bar { width: 100%; background-color: var(--accent-green); height: 100%; border-radius: 8px; transition: width 0.4s ease; }
        .progress-text { font-size: 0.9rem; font-style: italic; font-weight: 700; color: var(--text-metric-label); }

        .content-container { padding: 25px 40px; display: flex; flex-direction: column; gap: 20px; }
        
        .input-card { 
            background: var(--bg-card); 
            padding: 25px; 
            border-radius: 12px; 
            border: 1px solid var(--border-color); 
            position: relative;
            z-index: 10;
        }
        .input-card h3 { font-size: 1.3rem; font-weight: 700; color: #BA181B; margin-bottom: 15px; }
        
        .form-row { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; position: relative; z-index: 15; }
        
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
        
        .route-labels-box { display: flex; gap: 15px; margin: 15px 0; padding: 12px; border-radius: 8px; background: var(--input-bg); border: 1px solid var(--border-color); font-size: 0.9rem; position: relative; z-index: 5; }
        .route-label-item { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        .route-label-title { font-weight: 700; font-size: 0.8rem; color: var(--accent-green); text-transform: uppercase; }
        .route-label-value { font-weight: 600; color: var(--text-main); }

        .btn-add { background-color: var(--accent-green); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; height: 45px; position: relative; z-index: 20; }
        .btn-add:hover { background-color: var(--accent-green-hover); }
        .btn-add:disabled { background-color: #9CA3AF; cursor: not-allowed; }

        .map-section-wrapper { width: 100%; display: block; clear: both; margin-top: 15px; position: relative; z-index: 1; }
        
        .leaflet-container { z-index: 1 !important; }
        #map { height: 350px; width: 100%; border-radius: 8px; border: 1px solid var(--border-color); z-index: 1; display: block; }
        .leaflet-routing-container { display: none !important; }

        .list-card { background: var(--bg-card); padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); }
        .list-card h3 { font-size: 1.2rem; font-weight: 700; color: #BA181B; margin-bottom: 20px; }
        .list-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        @media(max-width: 900px) { .list-grid { grid-template-columns: 1fr; } }
        .list-column { background-color: var(--input-bg); border-radius: 8px; min-height: 250px; padding: 20px; border: 1px solid var(--border-color); }
        .list-column h4 { font-size: 1rem; font-weight: 700; color: #BA181B; text-align: center; margin-bottom: 15px; border-bottom: 2px dashed #BA181B; padding-bottom: 5px; }
        
        .logged-item { font-size: 0.85rem; background: var(--bg-card); color: var(--text-main); padding: 8px 12px; margin-bottom: 8px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; font-weight: 500; gap: 10px; border: 1px solid var(--border-color); }
        .logged-item span:first-child { word-break: break-word; flex: 1; }
        .logged-item-value { font-weight: 600; margin-right: 5px; }
        .btn-delete-item { background: transparent; border: none; color: #BA181B; cursor: pointer; font-size: 0.95rem; padding: 2px 6px; border-radius: 4px; transition: 0.2s; }
        .btn-delete-item:hover { background-color: #fee2e2; }
        
        .btn-calculate { width: 100%; background-color: var(--accent-green); color: white; border: none; padding: 16px; border-radius: 8px; font-size: 1.2rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-calculate:hover { background-color: var(--accent-green-hover); }
        
        .swal2-popup { font-family: 'Inter', sans-serif !important; border-radius: 12px !important; }
    </style>
</head>
<body>

    <div class="sidebar">
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
                <a href="javascript:void(0);" onclick="confirmLogout();" class="menu-item"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
            </div>
        </div>
    </div>

    <div class="main-workspace">
        <div class="top-navbar">
            <div class="header-title-area">
                <h2>Activity Input</h2>
                <p>Record and manage operational data</p>
            </div>
            
            <div class="user-nav-profile">
                <div class="notification-container">
                    <div class="notification-bell" id="bellBtn">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                    <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>

                    <div class="notification-dropdown" id="notificationMenu">
                        <div class="dropdown-header">Notifications</div>
                        <div id="notificationListWrapper">
                            <div id="notificationList">
                                <div style="padding: 20px; text-align: center; color: var(--text-muted);">Loading updates...</div>
                            </div>
                        </div>
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
                <div class="input-card">
                    <h3 id="transportCardTitle">Trip 1: Transport Calculator (Home to BSU Campus)</h3>
                    <div class="form-row">
                        <div class="form-group" id="primarySelectorGroup">
                            <label id="campusLabel" for="campusSelect">BSU Campus (Destination)</label>
                            <select id="campusSelect" name="campus_select" onchange="handleCampusSelection()">
                                <option value="" selected disabled>Select Campus</option>
                                <option value="13.9575,121.1610">Lipa Campus</option>
                                <option value="13.7542,121.0547">Pablo Borbon Campus</option>
                                <option value="13.7745,121.0772">Alangilan Campus</option>
                                <option value="14.0498,121.1292">LIMA Campus</option>
                                <option value="14.0670,120.6260">ARASOF Nasugbu Campus</option>
                                <option value="14.0450,121.1575">JPLPC Malvar Campus</option>
                                <option value="13.8821,120.9168">Lemery Campus</option>
                                <option value="13.8456,121.2038">Rosario Campus</option>
                                <option value="13.8248,121.3283">San Juan Campus</option>
                                <option value="13.9372,120.7325">Balayan Campus</option>
                                <option value="13.6415,121.1922">Lobo Campus</option>
                                <option value="13.7502,120.9405">Mabini Campus</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="vehicleType">Vehicle Used</label>
                            <select id="vehicleType" onchange="calculateRouteIfPossible()">
                                <option value="" selected disabled>Select Vehicle Type</option>
                                <option value="0.103">Motorcycle (0.103 kg CO2e/km)</option>
                                <option value="0.095">Tricycle (0.095 kg CO2e/km)</option>
                                <option value="0.035">Modern Jeepney (0.035 kg CO2e/km)</option>
                                <option value="0.171">Private Car (Gasoline) (0.171 kg CO2e/km)</option>
                                <option value="0.080">Public Jeepney (0.08 kg CO2e/km)</option>
                                <option value="0.050">Public Bus (0.05 kg CO2e/km)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="transportDistance">Point-to-Point Distance (KM)</label>
                            <input type="number" id="transportDistance" step="0.01" placeholder="Auto-calculates or enter manually">
                        </div>
                        <button type="button" class="btn-add" id="addTransportBtn" onclick="addTransport()">Add Route</button>
                    </div>

                    <div class="route-labels-box">
                        <div class="route-label-item">
                            <div class="route-label-title" id="startLabelTitle"><i class="fa-solid fa-location-dot"></i> Starting Point (Home)</div>
                            <div id="startLabel" class="route-label-value">Click Map to Pin Home</div>
                        </div>
                        <div class="route-label-item">
                            <div class="route-label-title" id="endLabelTitle"><i class="fa-solid fa-building-flag"></i> Destination Campus</div>
                            <div id="endLabel" class="route-label-value">Select Campus Dropdown</div>
                        </div>
                    </div>
                    
                    <div class="map-section-wrapper">
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;" id="mapInstructions">
                            <i class="fa-solid fa-map-location-dot" style="color: var(--accent-green);"></i> <strong>Trip 1 Setup:</strong> Select your destination BSU Campus from the dropdown, then click your Home position on the map.
                        </p>
                        <div id="map"></div>
                    </div>
                </div>

                <div class="input-card">
                    <h3>Office Resource</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="officeType">Office Resource / Appliance</label>
                            <select id="officeType">
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
                        <div class="form-group">
                            <label for="officeUsageUnits">Duration of Usage (Hours)</label>
                            <input type="number" id="officeUsageUnits" placeholder="Input total active hours" step="0.1">
                        </div>
                        <button type="button" class="btn-add" onclick="addOffice()">Add Emission</button>
                    </div>
                </div>

                <div class="input-card">
                    <h3>Food Consumption</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="foodMealPeriodSelect">Meal Period</label>
                            <select id="foodMealPeriodSelect">
                                <option value="" selected disabled>Select Meal Time</option>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Lunch">Lunch</option>
                                <option value="Dinner">Dinner</option>
                                <option value="Snack">Snack</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="foodType">Food Type</label>
                            <select id="foodType">
                                <option value="" selected disabled>Select food type</option>
                                <option value="2.5">Pork / Beef Meal (2.5 kg CO2e)</option>
                                <option value="1.2">Chicken / Poultry (1.2 kg CO2e)</option>
                                <option value="0.4">Vegetarian Meal (0.4 kg CO2e)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="foodServings">Portions Serving Count</label>
                            <input type="number" id="foodServings" placeholder="Input total servings consumed">
                        </div>
                        <button type="button" class="btn-add" onclick="addFood()">Add Emission</button>
                    </div>
                </div>

                <form id="supabaseMasterForm" method="POST" action="activity_input.php" onsubmit="handleFormSubmission(event)">
                    <input type="hidden" name="action" value="save_emissions">
                    <input type="hidden" id="hiddenTransport" name="total_transport" value="0">
                    <input type="hidden" id="hiddenOffice" name="total_office" value="0">
                    <input type="hidden" id="hiddenFood" name="total_food" value="0">
                    
                    <input type="hidden" id="transportItem" name="transport_item" value="">
                    <input type="hidden" id="officeItem" name="office_item" value="">
                    <input type="hidden" id="foodItem" name="food_item" value="">
                    <input type="hidden" id="foodMealPeriod" name="food_meal_period" value="">
                    <input type="hidden" id="foodConsumedAt" name="food_consumed_at" value="">

                    <div class="list-card">
                        <h3>Your Carbon Emission & Activity List</h3>
                        <div class="list-grid">
                            <div class="list-column" id="colTransport">
                                <h4>Transportation (CO2e Emissions)</h4>
                            </div>
                            <div class="list-column" id="colOffice">
                                <h4>Office Resource</h4>
                            </div>
                            <div class="list-column" id="colFood">
                                <h4>Food Consumption</h4>
                            </div>
                        </div>
                        <button type="submit" class="btn-calculate">Calculate my emission</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const existingDatabaseCount = <?= (int)$today_transport_count ?>;
        let selectedCampusCoords = null; 
        let selectedCampusName = "";

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
                title: 'Log out?',
                text: "Are you sure you want to end your session?",
                icon: 'warning',
                iconColor: '#f42828',
                showCancelButton: true,
                confirmButtonColor: '#2D6A4F',
                cancelButtonColor: '#BA181B',
                confirmButtonText: 'Yes, log out',
                cancelButtonText: 'Stay',
                allowOutsideClick: false,
                allowEscapeKey: false,
                background: popupBg,
                color: popupText,
                backdrop: `rgba(0, 0, 0, 0.4)`
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Logging out...',
                        text: 'Taking you safely back to the login screen.',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    window.location.href = 'logout.php?t=' + new Date().getTime();
                }
            });
        }

        let totals = { transport: 0, office: 0, food: 0 };
        let points = 100;
        const GHG_ELECTRICITY_FACTOR = 0.7122; 
        
        const map = L.map('map').setView([13.7565, 121.0583], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let mapMarkers = [];
        let routingControl = null;
        
        let localTransportCount = 0; 
        let transportAttemptCount = existingDatabaseCount + 1; 

        function applyAttemptLayout() {
            const titleEl = document.getElementById('transportCardTitle');
            const campusLabelEl = document.getElementById('campusLabel');
            const mapInstEl = document.getElementById('mapInstructions');
            const startLblTitle = document.getElementById('startLabelTitle');
            const endLblTitle = document.getElementById('endLabelTitle');
            const campusSelect = document.getElementById('campusSelect');
            const addBtn = document.getElementById('addTransportBtn');

            const totalCurrentAttempts = existingDatabaseCount + localTransportCount;

            if (totalCurrentAttempts >= 2) {
                titleEl.innerText = "Transport Distance Calculator (Daily Limit Reached)";
                campusSelect.disabled = true;
                addBtn.disabled = true;
                mapInstEl.innerHTML = '<i class="fa-solid fa-circle-exclamation" style="color: #BA181B;"></i> <strong>Limit Reached:</strong> You have submitted the maximum allowed 2 transportation entries for today.';
                return;
            }

            if (transportAttemptCount === 1) {
                titleEl.innerText = "Trip 1: Transport Calculator (Home to BSU Campus)";
                campusLabelEl.innerText = "BSU Campus (Destination)";
                campusSelect.disabled = false;
                
                startLblTitle.innerHTML = '<i class="fa-solid fa-location-dot"></i> Starting Point (Home)';
                endLblTitle.innerHTML = '<i class="fa-solid fa-building-flag"></i> Destination Campus';
                mapInstEl.innerHTML = '<i class="fa-solid fa-map-location-dot" style="color: var(--accent-green);"></i> <strong>Trip 1 Setup:</strong> Select your destination BSU Campus from the dropdown, then click your Home location on the map.';
                document.getElementById('startLabel').innerText = "Click Map to Pin Home";
                document.getElementById('endLabel').innerText = selectedCampusName || "Select Campus Dropdown";
            } else if (transportAttemptCount === 2) {
                titleEl.innerText = "Trip 2: Transport Calculator (BSU Campus to Home)";
                campusLabelEl.innerText = "BSU Campus (Starting Point)";
                
                campusSelect.disabled = false; 
                campusSelect.value = ""; 

                startLblTitle.innerHTML = '<i class="fa-solid fa-building-flag"></i> Starting Point (BSU Campus)';
                endLblTitle.innerHTML = '<i class="fa-solid fa-location-dot"></i> Destination (Pin Home on Map)';
                mapInstEl.innerHTML = '<i class="fa-solid fa-map-location-dot" style="color: var(--accent-green);"></i> <strong>Trip 2 Setup:</strong> Select your starting BSU Campus from the dropdown, then click your Home location on the map.';
                
                document.getElementById('startLabel').innerText = "Select Campus Dropdown";
                document.getElementById('endLabel').innerText = "Click Map to Pin Home";

                if (mapMarkers[0]) map.removeLayer(mapMarkers[0]);
                if (mapMarkers[1]) map.removeLayer(mapMarkers[1]);
                mapMarkers = [];
                selectedCampusCoords = null;
                selectedCampusName = "";
            }
        }

        function handleCampusSelection() {
            const selectEl = document.getElementById('campusSelect');
            if (!selectEl || !selectEl.value) return;

            const parts = selectEl.value.split(',');
            selectedCampusCoords = [parseFloat(parts[0]), parseFloat(parts[1])];
            selectedCampusName = selectEl.options[selectEl.selectedIndex].text;

            document.getElementById('transportDistance').value = '';

            if (routingControl) {
                map.removeControl(routingControl);
                routingControl = null;
            }

            if (transportAttemptCount === 1) {
                if (mapMarkers[1]) map.removeLayer(mapMarkers[1]);

                let campusMarker = L.marker(selectedCampusCoords, {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/libs/leaflet/1.7.1/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map);

                campusMarker.bindPopup(`<b>Destination Campus: ${selectedCampusName}</b>`).openPopup();
                mapMarkers[1] = campusMarker; 
                document.getElementById('endLabel').innerText = selectedCampusName;

            } else if (transportAttemptCount === 2) {
                if (mapMarkers[0]) map.removeLayer(mapMarkers[0]);

                let campusMarker = L.marker(selectedCampusCoords, {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/libs/leaflet/1.7.1/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map);

                campusMarker.bindPopup(`<b>Starting Point: ${selectedCampusName}</b>`).openPopup();
                mapMarkers[0] = campusMarker; 
                document.getElementById('startLabel').innerText = selectedCampusName;
            }

            map.setView(selectedCampusCoords, 13);
            calculateRouteIfPossible();
        }

        map.on('click', function(e) {
            if ((existingDatabaseCount + localTransportCount) >= 2) return;

            const latLngText = `${e.latlng.lat.toFixed(4)}, ${e.latlng.lng.toFixed(4)}`;

            if (transportAttemptCount === 1) {
                if (mapMarkers[0]) map.removeLayer(mapMarkers[0]);
                if (routingControl) { map.removeControl(routingControl); routingControl = null; }
                document.getElementById('transportDistance').value = '';

                let homeMarker = L.marker(e.latlng, {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/libs/leaflet/1.7.1/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map);
                homeMarker.bindPopup("<b>Starting Point (Home)</b>").openPopup();
                mapMarkers[0] = homeMarker; 
                document.getElementById('startLabel').innerText = `Home (${latLngText})`;
            } else if (transportAttemptCount === 2) {
                if (mapMarkers[1]) map.removeLayer(mapMarkers[1]);
                if (routingControl) { map.removeControl(routingControl); routingControl = null; }
                document.getElementById('transportDistance').value = '';

                let destMarker = L.marker(e.latlng, {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/libs/leaflet/1.7.1/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map);
                destMarker.bindPopup("<b>Destination (Home)</b>").openPopup();
                mapMarkers[1] = destMarker;
                document.getElementById('endLabel').innerText = `Home (${latLngText})`;
            }

            calculateRouteIfPossible();
        });

        function calculateRouteIfPossible() {
            if (mapMarkers[0] && mapMarkers[1]) {
                const startNode = mapMarkers[0].getLatLng();
                const endNode = mapMarkers[1].getLatLng();

                if (routingControl) {
                    map.removeControl(routingControl);
                    routingControl = null;
                }

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
                    const routes = e.routes;
                    if (routes && routes.length > 0) {
                        const distanceInMeters = routes[0].summary.totalDistance;
                        const distanceInKm = (distanceInMeters / 1000).toFixed(2);
                        document.getElementById('transportDistance').value = distanceInKm;
                    }
                });

                routingControl.on('routingerror', function() {
                    const straightDistanceKm = (startNode.distanceTo(endNode) / 1000).toFixed(2);
                    document.getElementById('transportDistance').value = straightDistanceKm;
                });
            }
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

            if ((existingDatabaseCount + localTransportCount) >= 2) {
                Swal.fire({
                    title: 'Limit Reached',
                    text: 'You have reached the maximum daily limit of 2 transportation entries.',
                    icon: 'warning',
                    confirmButtonColor: '#52B788',
                    background: activeTheme === 'dark' ? '#121A16' : '#ffffff',
                    color: activeTheme === 'dark' ? '#F3F4F6' : '#333333'
                });
                return;
            }

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
            
            let startText = document.getElementById('startLabel').innerText;
            let endText = document.getElementById('endLabel').innerText;
            
            let calculatedEmission = kmDistance * vehicleFactor;
            totals.transport += calculatedEmission;
            document.getElementById('hiddenTransport').value = totals.transport.toFixed(4);

            let routeSummary = `${startText.split(' (')[0]} → ${endText.split(' (')[0]}`;
            createListItem('colTransport', 'transport', calculatedEmission, routeSummary, `${calculatedEmission.toFixed(2)} kg CO2e (${kmDistance} km)`);
            
            document.getElementById('transportItem').value = vehicleLabel;

            distInp.value = "";
            vehicleSel.value = "";

            if (routingControl) {
                map.removeControl(routingControl);
                routingControl = null;
            }

            localTransportCount++;
            transportAttemptCount++;

            applyAttemptLayout();

            Swal.fire({
                title: 'Route Added!',
                text: 'Your trip route has been added to your emission summary. Click "Calculate my emission" to save and finish.',
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
            totals.office += calculatedValue;
            document.getElementById('hiddenOffice').value = totals.office.toFixed(4);
            createListItem('colOffice', 'office', calculatedValue, `${itemText} (${hoursInp.value}h)`, `${calculatedValue.toFixed(4)} kg`);
            
            document.getElementById('officeItem').value = itemText;

            hoursInp.value = "";
            updateGreenPointsUI();
        }

        function addFood() {
            const mealPeriodSel = document.getElementById('foodMealPeriodSelect');
            const typeSel = document.getElementById('foodType');
            const servingsInp = document.getElementById('foodServings');
            let activeTheme = localStorage.getItem('theme') || 'light';

            if(!mealPeriodSel.value) {
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

            if(!typeSel.value || !servingsInp.value || parseFloat(servingsInp.value) <= 0) {
                Swal.fire({
                    title: 'Missing Details',
                    text: 'Please pick a food type and enter how many servings you had.',
                    icon: 'warning',
                    confirmButtonColor: '#52B788',
                    background: activeTheme === 'dark' ? '#121A16' : '#ffffff',
                    color: activeTheme === 'dark' ? '#F3F4F6' : '#333333'
                });
                return;
            }
            let mealText = mealPeriodSel.value;
            let foodText = typeSel.options[typeSel.selectedIndex].text.split(' (')[0];
            let calculatedValue = parseFloat(typeSel.value) * parseFloat(servingsInp.value);
            totals.food += calculatedValue;
            document.getElementById('hiddenFood').value = totals.food.toFixed(4);
            
            createListItem('colFood', 'food', calculatedValue, `[${mealText}] ${foodText}`, `${calculatedValue.toFixed(2)} kg`);
            
            document.getElementById('foodItem').value = foodText;
            document.getElementById('foodMealPeriod').value = mealText;

            servingsInp.value = "";
            mealPeriodSel.value = "";
            typeSel.value = "";
            updateGreenPointsUI();
        }

        function createListItem(columnId, category, value, labelText, numericalText) {
            const container = document.getElementById(columnId);
            const wrapperDiv = document.createElement('div');
            wrapperDiv.className = 'logged-item';
            wrapperDiv.innerHTML = `<span>${labelText}</span><span class="logged-item-value">${numericalText}</span><button type="button" class="btn-delete-item"><i class="fa-solid fa-xmark"></i></button>`;
            
            wrapperDiv.querySelector('.btn-delete-item').addEventListener('click', function() {
                totals[category] = Math.max(0, totals[category] - value);
                document.getElementById('hidden' + category.charAt(0).toUpperCase() + category.slice(1)).value = totals[category].toFixed(4);
                
                if (category === 'transport') {
                    localTransportCount = Math.max(0, localTransportCount - 1);
                    transportAttemptCount = Math.max(1, transportAttemptCount - 1);
                    applyAttemptLayout();
                }

                wrapperDiv.remove();
                updateGreenPointsUI();
            });
            container.appendChild(wrapperDiv);
        }

        function handleFormSubmission(event) {
            event.preventDefault();
            let activeTheme = localStorage.getItem('theme') || 'light';

            if (totals.transport === 0 && totals.office === 0 && totals.food === 0) {
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

        const bellBtn = document.getElementById('bellBtn');
        const notificationMenu = document.getElementById('notificationMenu');
        const badge = document.getElementById('notificationBadge');
        const list = document.getElementById('notificationList');

        if (bellBtn && notificationMenu) {
            bellBtn.addEventListener('click', (e) => { e.stopPropagation(); notificationMenu.classList.toggle('show'); });
            document.addEventListener('click', () => notificationMenu.classList.remove('show'));
        }

        function loadNotifications() {
            if (!list || !badge) return;
            fetch('get_notifications.php')
                .then(res => res.json())
                .then(data => {
                    if (!data || data.length === 0) {
                        list.innerHTML = `<div style="padding: 20px; text-align: center; color: var(--text-muted);">No updates found</div>`;
                        badge.style.display = 'none';
                        return;
                    }
                    let unread = data.filter(n => !n.is_read).length;
                    badge.style.display = unread > 0 ? 'block' : 'none';
                    badge.innerText = unread;

                    list.innerHTML = data.map(n => `
                        <div class="notif-item" data-id="${n.id}" onclick="markAsRead(${n.id}, this)" style="padding: 12px 15px; border-bottom: 1px solid var(--border-color); background: ${n.is_read ? 'transparent' : 'var(--input-bg)'}; cursor: pointer;">
                            <span style="font-weight: 700; color: var(--text-main);">${n.title || 'Notification'}</span>
                            <p style="margin: 0; color: var(--text-muted); font-size: 0.8rem;">${n.message}</p>
                        </div>
                    `).join('');
                })
                .catch(() => {
                    list.innerHTML = `<div style="padding: 20px; text-align: center; color: var(--text-muted);">Updates temporarily unavailable</div>`;
                });
        }

        function markAsRead(id, element) {
            let formData = new FormData();
            formData.append('id', id);
            fetch('update_notification.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        element.style.background = 'transparent';
                        loadNotifications();
                    }
                });
        }

        document.addEventListener("DOMContentLoaded", function() {
            applyAttemptLayout();
            loadNotifications();
            setInterval(loadNotifications, 60000);

            setTimeout(function() {
                map.invalidateSize();
            }, 200);
        });
    </script>
</body>
</html>