<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Guard check: If the user isn't logged in, send them back to login
if (!isset($_SESSION['user_token'])) {
    header('Location: login.php');
    exit;
}

// 2. Extract session variables safely
$user_data = $_SESSION['user_profile'] ?? ($_SESSION['user_data'] ?? []);
$user_id   = $_SESSION['user_id'] ?? ($user_data['id'] ?? null); 

// 3. Name & Role extraction
$raw_name = $_SESSION['user_name'] ?? ($user_data['name'] ?? ($user_data['full_name'] ?? ''));
$raw_role = $_SESSION['user_role'] ?? ($user_data['role'] ?? '');

$full_name = !empty($raw_name) ? ucwords(strtolower(trim($raw_name))) : 'Unknown User'; 
$role      = (!empty($raw_role) && strtolower($raw_role) !== 'authenticated') ? ucwords(strtolower(trim($raw_role))) : 'User';

// --- NEON POSTGRESQL CONFIGURATION ---
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
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// User department and campus context
$user_department = $_SESSION['user_department'] ?? ($user_data['department'] ?? 'Unassigned');
$user_campus     = $_SESSION['user_campus'] ?? ($user_data['campus'] ?? 'Unassigned');
$avatar_url      = $user_data['avatar_url'] ?? ($user_data['profile_picture'] ?? null);

// Fetch user profile info and profile_picture from database if needed/missing
if ($user_id) {
    try {
        $stmt_u = $pdo->prepare("SELECT department, campus, profile_picture FROM users WHERE id = :id LIMIT 1");
        $stmt_u->execute([':id' => $user_id]);
        $u_info = $stmt_u->fetch();
        if ($u_info) {
            $user_department = !empty($u_info['department']) ? $u_info['department'] : $user_department;
            $user_campus     = !empty($u_info['campus']) ? $u_info['campus'] : $user_campus;
            if (!empty($u_info['profile_picture'])) {
                $avatar_url = $u_info['profile_picture'];
            }
        }
    } catch (PDOException $e) {
        // Query error handling fallback
    }
}

// Generate initials fallback if profile picture is empty
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

// Fetch notifications from Neon database
$dynamic_notifications = [];
try {
    $stmt_notif = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10");
    $stmt_notif->execute([':user_id' => $user_id]);
    $dynamic_notifications = $stmt_notif->fetchAll();
} catch (PDOException $e) {
    $dynamic_notifications = [];
}

$unread_count = 0;
if (is_array($dynamic_notifications)) {
    foreach ($dynamic_notifications as $notification) {
        if (isset($notification['is_read']) && $notification['is_read'] == false) {
            $unread_count++;
        }
    }
}

// ==========================================================================
// METRIC & CHART CALCULATIONS FROM NEON DB (`carbon_records`)
// ==========================================================================
$one_week_ago   = date('Y-m-d', strtotime('-7 days'));
$one_month_ago  = date('Y-m-d', strtotime('-30 days'));
$prev_month_ago = date('Y-m-d', strtotime('-60 days'));

$total_all_time   = 0.0;
$total_this_week  = 0.0;
$total_this_month = 0.0;
$total_prev_month = 0.0;

$weekly_trend_data  = ['Mon' => 0.0, 'Tue' => 0.0, 'Wed' => 0.0, 'Thu' => 0.0, 'Fri' => 0.0, 'Sat' => 0.0, 'Sun' => 0.0];
$source_totals_week = ['transportation' => 0.0, 'electricity' => 0.0, 'food' => 0.0];

$monthly_trend_data  = [];
$source_totals_month = ['transportation' => 0.0, 'electricity' => 0.0, 'food' => 0.0];

if ($user_id) {
    try {
        // 1. Lifetime total emissions
        $stmt_life = $pdo->prepare("SELECT SUM(total_emission) AS total FROM carbon_records WHERE user_id = :user_id");
        $stmt_life->execute([':user_id' => $user_id]);
        $life_row = $stmt_life->fetch();
        $total_all_time = (float)($life_row['total'] ?? 0.0);

        // 2. Fetch all user records
        $stmt_rec = $pdo->prepare("
            SELECT transportation, electricity, food, total_emission, record_date 
            FROM carbon_records 
            WHERE user_id = :user_id 
            ORDER BY record_date ASC
        ");
        $stmt_rec->execute([':user_id' => $user_id]);
        $user_activities = $stmt_rec->fetchAll();

        foreach ($user_activities as $activity) {
            $t = (float)($activity['transportation'] ?? 0.0);
            $e = (float)($activity['electricity'] ?? 0.0);
            $f = (float)($activity['food'] ?? 0.0);
            $total_rec = (float)($activity['total_emission'] ?? ($t + $e + $f));

            $r_date_raw = $activity['record_date'] ?? null;
            $r_date     = $r_date_raw ? substr($r_date_raw, 0, 10) : null;

            if ($r_date) {
                // Growth comparison data
                if ($r_date >= $one_month_ago) {
                    $total_this_month += $total_rec;
                    $source_totals_month['transportation'] += $t;
                    $source_totals_month['electricity']    += $e;
                    $source_totals_month['food']           += $f;

                    $month_label = date('M d', strtotime($r_date));
                    if (!isset($monthly_trend_data[$month_label])) {
                        $monthly_trend_data[$month_label] = 0.0;
                    }
                    $monthly_trend_data[$month_label] += $total_rec;
                } elseif ($r_date >= $prev_month_ago) {
                    $total_prev_month += $total_rec;
                }

                // Weekly trend data
                if ($r_date >= $one_week_ago) {
                    $total_this_week += $total_rec;
                    $source_totals_week['transportation'] += $t;
                    $source_totals_week['electricity']    += $e;
                    $source_totals_week['food']           += $f;

                    $day_name = date('D', strtotime($r_date));
                    if (array_key_exists($day_name, $weekly_trend_data)) {
                        $weekly_trend_data[$day_name] += $total_rec;
                    }
                }
            }
        }
    } catch (PDOException $e) {
        // Query error handling
    }
}

// ==========================================================================
// 3. FETCH OR GENERATE FORECAST RECORDS
// ==========================================================================
$forecast_labels = [];
$forecast_data   = [];

try {
    // Attempt 1: Fetch from database (user-specific or recent)
    $stmt_fore = $pdo->prepare("
        SELECT forecast_date, predicted_emission 
        FROM forecast_records 
        ORDER BY forecast_date ASC 
        LIMIT 30
    ");
    $stmt_fore->execute();
    $forecast_rows = $stmt_fore->fetchAll();

    foreach ($forecast_rows as $row) {
        $forecast_labels[] = date('n/j', strtotime($row['forecast_date']));
        $forecast_data[]   = (float)$row['predicted_emission'];
    }
} catch (PDOException $e) {
    $forecast_rows = [];
}

// Attempt 2: Dynamic Forecast Fallback if table is empty
if (empty($forecast_data)) {
    $base_emission = ($total_this_week > 0) ? ($total_this_week / 7) : 12.5;
    
    for ($i = 1; $i <= 30; $i++) {
        $future_date = date('Y-m-d', strtotime("+$i days"));
        $forecast_labels[] = date('n/j', strtotime($future_date));
        
        // Dynamic curve based on recent activity baseline
        $variation = sin($i / 3) * 2.5 + (rand(-10, 10) / 10.0);
        $predicted_val = max(1.5, round($base_emission + $variation, 2));
        $forecast_data[] = $predicted_val;
    }
}

// Compute Growth Rate Delta
if ($total_prev_month > 0) {
    $pct_change = (($total_this_month - $total_prev_month) / $total_prev_month) * 100;
    $growth_string = ($pct_change >= 0 ? '+' : '') . number_format($pct_change, 1) . '%';
} else {
    $growth_string = ($total_this_month > 0) ? '+100%' : '0%';
}

// Calculate Green Points
$green_points = 100 - min(100, round($total_this_week));

if ($green_points >= 70) {
    $progress_bar_color = 'linear-gradient(90deg, #52B788, #2D6A4F)';
} elseif ($green_points >= 40) {
    $progress_bar_color = 'linear-gradient(90deg, #F4A261, #E76F51)';
} else {
    $progress_bar_color = 'linear-gradient(90deg, #E63946, #BA181B)';
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function time_elapsed_string($datetime, $full = false) {
    $now  = new DateTime;
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);
    $weeks = floor($diff->d / 7);
    $days  = $diff->d - ($weeks * 7);
    $string = array('y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second');
    foreach ($string as $k => &$v) {
        $value = match ($k) { 'w' => $weeks, 'd' => $days, default => $diff->$k };
        if ($value) { $v = $value . ' ' . $v . ($value > 1 ? 's' : ''); } else { unset($string[$k]); }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise - Reports</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            --accent-green: #2D6A4F;
            --accent-green-hover: #22513B;
            --bell-bg: #e2f0d9;
            --sidebar-divider: rgba(255, 255, 255, 0.15);
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
            --accent-green: #52B788;
            --accent-green-hover: #74C69D;
            --bell-bg: #162E24;
            --sidebar-divider: rgba(118, 200, 147, 0.2);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; transition: background-color 0.3s, border-color 0.3s, color 0.3s; }
        body { display: flex; height: 100vh; width: 100vw; background-color: var(--bg-body); color: var(--text-main); overflow: hidden; }

        .sidebar { width: 260px; background-color: var(--bg-sidebar); color: white; display: flex; flex-direction: column; padding: 20px 0; flex-shrink: 0; height: 100%; border-right: 1px solid var(--border-color); }
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
        .sidebar-divider { height: 1px; background-color: var(--sidebar-divider); margin: 10px 25px; }
        
        .main-workspace { flex: 1; display: flex; flex-direction: column; height: 100%; overflow: hidden; }
        .top-navbar { height: 75px; background: var(--bg-card); display: flex; align-items: center; justify-content: space-between; padding: 0 40px; flex-shrink: 0; border-bottom: 1px solid var(--border-color); }
        
        .header-title-area h2 { font-size: 1.4rem; font-weight: 700; color: var(--text-main); margin-bottom: 2px; }
        .header-title-area p { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
        
        .user-nav-profile { display: flex; align-items: center; gap: 25px; }
        
        .notification-container { position: relative; display: inline-block; }
        .notification-bell { background: var(--bell-bg); padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--accent-green); font-size: 18px; width: 40px; height: 40px; }
        .notification-bell:hover { opacity: 0.85; }
        .notification-badge { position: absolute; top: -2px; right: -2px; background-color: #BA181B; color: white; font-size: 10px; font-weight: 700; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 2px solid var(--bg-card); pointer-events: none; }
        
        .notification-dropdown { position: absolute; top: 50px; right: 0; width: 340px; background: var(--bg-card); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid var(--border-color); display: none; z-index: 1000; overflow: hidden; }
        .notification-dropdown.show { display: block; }
        .dropdown-header { padding: 15px; font-weight: 700; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); color: var(--text-main); background: var(--input-bg); }
        
        .notification-item { padding: 14px 16px; border-bottom: 1px solid var(--border-color); display: flex; gap: 12px; align-items: flex-start; cursor: pointer; transition: background-color 0.2s; }
        .notification-item:hover { background-color: var(--input-bg); }
        .notification-item.unread { background-color: var(--input-bg); border-left: 4px solid var(--accent-green); }
        .notification-item span.time { display: block; font-size: 0.7rem; color: var(--text-muted); margin-top: 5px; }
        .no-notifications { padding: 20px; text-align: center; color: var(--text-muted); font-size: 0.85rem; }

        .profile-card { display: flex; align-items: center; gap: 12px; border-left: 1px solid var(--border-color); padding-left: 25px; }
        .avatar-circle-nav { width: 40px; height: 40px; background: var(--input-bg); color: var(--accent-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; overflow: hidden; border: 1px solid var(--accent-green); }
        .avatar-circle-nav img { width: 100%; height: 100%; object-fit: cover; }
        .user-info-text h4 { font-size: 0.95rem; color: var(--text-main); font-weight: 700; }
        .user-info-text p { font-size: 0.8rem; color: var(--text-muted); }

        .reports-content { padding: 35px; flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 25px; }
        
        .progress-card { background: var(--bg-card); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.01); }
        .progress-header { font-weight: 700; font-size: 0.95rem; margin-bottom: 12px; font-style: italic; color: var(--text-main); }
        .progress-bar-wrapper { width: 100%; background: var(--input-bg); height: 16px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color); }
        .progress-bar-fill { height: 100%; background: <?= $progress_bar_color ?>; width: <?= (int)$green_points ?>%; transition: width 0.5s ease, background-color 0.5s ease; }

        .metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .metric-card { background: var(--bg-card); padding: 22px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 4px 10px rgba(0,0,0,0.01); }
        .metric-card h3 { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; margin-bottom: 10px; }
        .metric-value { font-size: 2.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 5px; }
        .metric-subtext { font-size: 0.78rem; color: var(--text-muted); font-weight: 500; }
        .text-green { color: var(--accent-green) !important; font-weight: 600; }

        .charts-grid { display: grid; grid-template-columns: 6fr 4fr; gap: 25px; }
        .chart-box { background: var(--bg-card); padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); position: relative; box-shadow: 0 4px 10px rgba(0,0,0,0.01); }
        .chart-title-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .chart-title-bar h3 { font-size: 0.95rem; font-weight: 700; color: var(--text-main); }
        .chart-title-bar select { padding: 5px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main); font-size: 0.85rem; cursor: pointer; outline: none; }
        .canvas-container { position: relative; width: 100%; height: 280px; }

        .chart-empty-overlay {
            position: absolute; top: 80px; left: 0; right: 0; bottom: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; color: var(--text-muted); font-weight: 500;
            pointer-events: none; display: none;
        }
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
            <a href="activity_input.php" class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Activity Input</a>
            <a href="reports.php" class="menu-item active"><i class="fa-solid fa-chart-simple"></i> Reports</a>
            <a href="mitigation_strategies.php" class="menu-item"><i class="fa-solid fa-lightbulb"></i> Mitigation Strategies</a>
            <a href="profile.php" class="menu-item"><i class="fa-solid fa-circle-user"></i> View Profile</a>
            
            <div class="sidebar-footer">
                <div class="sidebar-divider"></div>
                <button class="theme-toggle-item" id="themeToggle" title="Toggle Light/Dark Mode">
                    <i class="fa-solid fa-moon" id="themeIcon"></i> <span id="themeText">Dark Mode</span>
                </button>
                <a href="javascript:void(0);" onclick="confirmLogout();" class="menu-item"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
            </div>
        </div>
    </div>

    <div class="main-workspace">
        <div class="top-navbar">
            <div class="header-title-area">
                <h2>Reports</h2>
                <p>View your summaries, trends, and historical performance</p>
            </div>
            
            <div class="user-nav-profile">
                <div class="notification-container">
                    <div class="notification-bell" id="bellBtn">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge" id="navBadgeCount"><?= $unread_count ?></span>
                    <?php endif; ?>

                    <div class="notification-dropdown" id="notificationMenu">
                        <div class="dropdown-header">Notifications</div>
                        <div id="reportsNotifContainer" style="max-height: 360px; overflow-y: auto;">
                        <?php if (empty($dynamic_notifications) || !is_array($dynamic_notifications)): ?>
                            <div class="no-notifications">No new updates at this time.</div>
                        <?php else: ?>
                            <?php foreach ($dynamic_notifications as $notif): 
                                if (!isset($notif['id'])) continue;
                                $is_unread = !($notif['is_read'] ?? true);
                                $type = strtolower($notif['type'] ?? 'info');
                                $icon = ($type === 'success') ? 'fa-circle-check' : 'fa-circle-info';
                                $icon_color = ($type === 'success') ? '#2D6A4F' : '#0074d9';
                            ?>
                                <div class="notification-item <?= $is_unread ? 'unread' : '' ?>" onclick="markAsReadReports(<?= intval($notif['id']) ?>, this)">
                                    <i class="fa-solid <?= $icon ?>" style="color: <?= $icon_color ?>; margin-top: 3px; font-size: 1.1rem; flex-shrink: 0;"></i>
                                    <div style="flex: 1;">
                                        <strong style="display: block; font-size: 0.85rem; margin-bottom: 2px; color: var(--text-main);">
                                            <?= htmlspecialchars($notif['title'] ?? 'System Update') ?>
                                        </strong>
                                        <span style="display: block; font-size: 0.8rem; color: var(--text-muted); line-height: 1.35;">
                                            <?= htmlspecialchars($notif['message'] ?? '') ?>
                                        </span>
                                        <span class="time"><i class="fa-regular fa-clock" style="font-size: 0.65rem; margin-right: 4px;"></i><?= isset($notif['created_at']) ? time_elapsed_string($notif['created_at']) : 'just now' ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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

        <div class="reports-content">
            <div class="progress-card">
                <div class="progress-header">Green Points (<?= (int)$green_points ?> / 100 pts)</div>
                <div class="progress-bar-wrapper">
                    <div class="progress-bar-fill"></div>
                </div>
            </div>

            <div class="metrics-grid">
                <div class="metric-card">
                    <h3>Total CO2 Emissions</h3>
                    <div class="metric-value"><?= number_format($total_all_time, 2) ?></div>
                    <div class="metric-subtext text-green">since instantiation</div>
                </div>
                <div class="metric-card">
                    <h3>This Week</h3>
                    <div class="metric-value"><?= number_format($total_this_week, 2) ?></div>
                    <div class="metric-subtext">Total recorded emissions</div>
                </div>
                <div class="metric-card">
                    <h3>This Month</h3>
                    <div class="metric-value"><?= number_format($total_this_month, 2) ?></div>
                    <div class="metric-subtext">Total recorded emissions</div>
                </div>
                <div class="metric-card">
                    <h3>Emissions Growth Rate</h3>
                    <div class="metric-value"><?= htmlspecialchars($growth_string) ?></div>
                    <div class="metric-subtext">vs previous month window</div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="chart-box">
                    <div class="chart-title-bar">
                        <h3>Emissions Over Time</h3>
                        <select id="timeframeSelect">
                            <option value="week">By Day (Mon-Sun)</option>
                            <option value="month">By Entry Date</option>
                        </select>
                    </div>
                    <div class="canvas-container">
                        <canvas id="overTimeChart"></canvas>
                    </div>
                </div>

                <div class="chart-box" id="sourceChartContainer">
                    <div class="chart-title-bar">
                        <h3>Emissions by Source</h3>
                        <select id="sourceTimeframeSelect">
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                    <div class="canvas-container">
                        <canvas id="bySourceChart"></canvas>
                    </div>
                    <div class="chart-empty-overlay" id="sourceChartEmpty">No logs recorded for this period</div>
                </div>
            </div>

            <!-- 30-Day Emissions Forecast Card -->
            <div class="chart-box" style="margin-top: 5px;">
                <div class="chart-title-bar">
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 700;">30-Day Emissions Forecast</h3>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 3px;">
                            Predicted carbon emissions for the next 30 days
                        </p>
                    </div>
                </div>
                <div class="canvas-container" style="height: 280px; position: relative;">
                    <canvas id="forecastChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
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
            updateChartThemeColors(newTheme);
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
            let popupText = activeTheme === 'dark' ? '#333333' : '#333333';

            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to exit your dashboard report session?",
                icon: 'warning',
                iconColor: '#f42828',
                showCancelButton: true,
                confirmButtonColor: '#2D6A4F',
                cancelButtonColor: '#BA181B',
                confirmButtonText: 'Yes, log me out',
                cancelButtonText: 'Cancel',
                background: popupBg,
                color: popupText
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php?t=' + new Date().getTime();
                }
            });
        }

        const bellBtn = document.getElementById('bellBtn');
        const notificationMenu = document.getElementById('notificationMenu');
        bellBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationMenu.classList.toggle('show');
        });
        document.addEventListener('click', (e) => {
            if (!notificationMenu.contains(e.target) && e.target !== bellBtn) {
                notificationMenu.classList.remove('show');
            }
        });

        function markAsReadReports(id, element) {
            let formData = new FormData();
            formData.append('id', id);
            
            fetch('update_notifications.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        element.classList.remove('unread');
                        let badge = document.getElementById('navBadgeCount');
                        if (badge) {
                            let count = parseInt(badge.innerText) - 1;
                            if (count <= 0) badge.remove();
                            else badge.innerText = count;
                        }
                    }
                });
        }

        const timeDatasets = {
            week: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                data: <?= json_encode(array_values($weekly_trend_data)); ?>
            },
            month: {
                labels: <?= json_encode(array_keys($monthly_trend_data)); ?>,
                data: <?= json_encode(array_values($monthly_trend_data)); ?>
            }
        };

        const sourceDatasets = {
            week: [
                <?= (float)$source_totals_week['transportation'] ?>, 
                <?= (float)$source_totals_week['electricity'] ?>, 
                <?= (float)$source_totals_week['food'] ?>
            ],
            month: [
                <?= (float)$source_totals_month['transportation'] ?>, 
                <?= (float)$source_totals_month['electricity'] ?>, 
                <?= (float)$source_totals_month['food'] ?>
            ]
        };

        const forecastLabels = <?= json_encode($forecast_labels); ?>;
        const forecastData   = <?= json_encode($forecast_data); ?>;

        const getGridColor = (t) => t === 'dark' ? '#1B3A2B' : '#E5E7EB';
        const getLabelColor = (t) => t === 'dark' ? '#9CA3AF' : '#6B7280';

        const ctxTime = document.getElementById('overTimeChart').getContext('2d');
        const overTimeChart = new Chart(ctxTime, {
            type: 'line',
            data: {
                labels: timeDatasets.week.labels,
                datasets: [{
                    label: 'Emissions (kg CO2)', 
                    data: timeDatasets.week.data,
                    borderColor: '#2D6A4F', 
                    backgroundColor: 'rgba(45, 106, 79, 0.1)',
                    tension: 0.25, 
                    fill: true, 
                    borderWidth: 2.5
                }]
            },
            options: {
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: getGridColor(currentTheme) }, ticks: { color: getLabelColor(currentTheme) } },
                    x: { grid: { display: false }, ticks: { color: getLabelColor(currentTheme) } }
                }
            }
        });

        const ctxSource = document.getElementById('bySourceChart').getContext('2d');
        const bySourceChart = new Chart(ctxSource, {
            type: 'doughnut',
            plugins: [ChartDataLabels], 
            data: {
                labels: ['Transportation', 'Electricity Usage', 'Food Consumption'],
                datasets: [{
                    data: sourceDatasets.week,
                    backgroundColor: ['#5ea3e3', '#e9c46a', '#76d7b6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, 
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            color: getLabelColor(currentTheme), 
                            boxWidth: 12 
                        } 
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: { weight: 'bold', size: 12 },
                        formatter: (value, ctx) => {
                            let sum = 0;
                            let dataArr = ctx.chart.data.datasets[0].data;
                            dataArr.map(data => { sum += data; });
                            if (sum === 0) return null; 
                            return (value * 100 / sum).toFixed(1) + "%";
                        }
                    }
                }
            }
        });

        // Instantiate Forecast Chart
        const ctxForecast = document.getElementById('forecastChart').getContext('2d');
        const forecastGradient = ctxForecast.createLinearGradient(0, 0, 0, 280);
        forecastGradient.addColorStop(0, 'rgba(82, 183, 136, 0.35)');
        forecastGradient.addColorStop(1, 'rgba(82, 183, 136, 0.01)');

        const forecastChart = new Chart(ctxForecast, {
            type: 'line',
            data: {
                labels: forecastLabels,
                datasets: [{
                    label: 'Predicted CO2 (kg)',
                    data: forecastData,
                    borderColor: '#52B788',
                    borderWidth: 2.5,
                    pointRadius: 2,
                    pointHoverRadius: 6,
                    fill: true,
                    backgroundColor: forecastGradient,
                    tension: 0.35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: getGridColor(currentTheme) },
                        ticks: { color: getLabelColor(currentTheme) }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: getLabelColor(currentTheme),
                            maxTicksLimit: 10
                        }
                    }
                }
            }
        });

        function checkSourceDataEmpty(timeframe) {
            const data = sourceDatasets[timeframe];
            const sum = data.reduce((a, b) => a + b, 0);
            const overlay = document.getElementById('sourceChartEmpty');
            const canvas = document.getElementById('bySourceChart');
            
            if (sum === 0) {
                overlay.style.display = 'flex';
                canvas.style.opacity = '0.05';
            } else {
                overlay.style.display = 'none';
                canvas.style.opacity = '1';
            }
        }
        
        checkSourceDataEmpty('week');

        document.getElementById('timeframeSelect').addEventListener('change', function() {
            overTimeChart.data.labels = timeDatasets[this.value].labels;
            overTimeChart.data.datasets[0].data = timeDatasets[this.value].data;
            overTimeChart.update();
        });

        document.getElementById('sourceTimeframeSelect').addEventListener('change', function() {
            bySourceChart.data.datasets[0].data = sourceDatasets[this.value];
            bySourceChart.update();
            checkSourceDataEmpty(this.value);
        });

        function updateChartThemeColors(theme) {
            const grid = getGridColor(theme);
            const label = getLabelColor(theme);

            overTimeChart.options.scales.y.grid.color = grid;
            overTimeChart.options.scales.y.ticks.color = label;
            overTimeChart.options.scales.x.ticks.color = label;
            overTimeChart.update();

            bySourceChart.options.plugins.legend.labels.color = label;
            bySourceChart.update();

            forecastChart.options.scales.y.grid.color = grid;
            forecastChart.options.scales.y.ticks.color = label;
            forecastChart.options.scales.x.ticks.color = label;
            forecastChart.update();
        }
        updateChartThemeColors(currentTheme);
    </script>
</body>
</html>