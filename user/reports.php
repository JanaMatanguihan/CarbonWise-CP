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
$one_week_ago      = date('Y-m-d', strtotime('-7 days'));
$current_month_key = date('Y-m');
$prev_month_key    = date('Y-m', strtotime('-1 month'));

$total_all_time   = 0.0;
$total_this_week  = 0.0;
$total_this_month = 0.0;
$total_prev_month = 0.0;

// Datasets grouped by timeframe keys
$timeframe_over_time = []; 
$timeframe_by_source = []; 

// Default timeframe options
$timeframe_options = [
    'this_week'  => 'This Week',
    'this_month' => 'This Month',
    'last_month' => 'Last Month'
];

// Initialize default timeframe structure
foreach (array_keys($timeframe_options) as $tf_key) {
    $timeframe_over_time[$tf_key] = [];
    $timeframe_by_source[$tf_key] = ['transportation' => 0.0, 'electricity' => 0.0, 'food' => 0.0];
}

if ($user_id) {
    try {
        // 1. Lifetime total emissions
        $stmt_life = $pdo->prepare("SELECT SUM(total_emission) AS total FROM carbon_records WHERE user_id = :user_id");
        $stmt_life->execute([':user_id' => $user_id]);
        $life_row = $stmt_life->fetch();
        $total_all_time = (float)($life_row['total'] ?? 0.0);

        // 2. Fetch all user records ordered by date
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
                $month_key = date('Y-m', strtotime($r_date));
                $day_label = date('M d', strtotime($r_date));

                // A. Check if within "This Week" (last 7 days)
                if ($r_date >= $one_week_ago) {
                    $total_this_week += $total_rec;

                    if (!isset($timeframe_over_time['this_week'][$day_label])) {
                        $timeframe_over_time['this_week'][$day_label] = 0.0;
                    }
                    $timeframe_over_time['this_week'][$day_label] += $total_rec;
                    $timeframe_by_source['this_week']['transportation'] += $t;
                    $timeframe_by_source['this_week']['electricity']    += $e;
                    $timeframe_by_source['this_week']['food']           += $f;
                }

                // B. Check if within "This Month"
                if ($month_key === $current_month_key) {
                    $total_this_month += $total_rec;

                    if (!isset($timeframe_over_time['this_month'][$day_label])) {
                        $timeframe_over_time['this_month'][$day_label] = 0.0;
                    }
                    $timeframe_over_time['this_month'][$day_label] += $total_rec;
                    $timeframe_by_source['this_month']['transportation'] += $t;
                    $timeframe_by_source['this_month']['electricity']    += $e;
                    $timeframe_by_source['this_month']['food']           += $f;
                }

                // C. Check if within "Last Month"
                if ($month_key === $prev_month_key) {
                    $total_prev_month += $total_rec;

                    if (!isset($timeframe_over_time['last_month'][$day_label])) {
                        $timeframe_over_time['last_month'][$day_label] = 0.0;
                    }
                    $timeframe_over_time['last_month'][$day_label] += $total_rec;
                    $timeframe_by_source['last_month']['transportation'] += $t;
                    $timeframe_by_source['last_month']['electricity']    += $e;
                    $timeframe_by_source['last_month']['food']           += $f;
                }

                // D. Aggregate older dynamic months if needed
                if ($month_key !== $current_month_key && $month_key !== $prev_month_key) {
                    if (!isset($timeframe_options[$month_key])) {
                        $timeframe_options[$month_key] = date('M Y', strtotime($r_date));
                        $timeframe_over_time[$month_key] = [];
                        $timeframe_by_source[$month_key] = ['transportation' => 0.0, 'electricity' => 0.0, 'food' => 0.0];
                    }

                    if (!isset($timeframe_over_time[$month_key][$day_label])) {
                        $timeframe_over_time[$month_key][$day_label] = 0.0;
                    }
                    $timeframe_over_time[$month_key][$day_label] += $total_rec;
                    $timeframe_by_source[$month_key]['transportation'] += $t;
                    $timeframe_by_source[$month_key]['electricity']    += $e;
                    $timeframe_by_source[$month_key]['food']           += $f;
                }
            }
        }
    } catch (PDOException $e) {
        // Query error handling
    }
}

// Format time datasets for JavaScript consumption
$formatted_time_datasets = [];
foreach ($timeframe_over_time as $tf_key => $dates) {
    $formatted_time_datasets[$tf_key] = [
        'labels' => array_keys($dates),
        'data'   => array_values($dates)
    ];
}

// ==========================================================================
// 3. FETCH OR GENERATE FORECAST RECORDS
// ==========================================================================
$forecast_labels = [];
$forecast_data   = [];

try {
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

if (empty($forecast_data)) {
    $base_emission = ($total_this_week > 0) ? ($total_this_week / 7) : 12.5;
    
    for ($i = 1; $i <= 30; $i++) {
        $future_date = date('Y-m-d', strtotime("+$i days"));
        $forecast_labels[] = date('n/j', strtotime($future_date));
        
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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

        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
            font-family: 'Inter', sans-serif; 
            transition: background-color 0.3s, border-color 0.3s, color 0.3s; 
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow-x: hidden;
            background-color: var(--bg-body);
            color: var(--text-main);
        }

        body { 
            display: flex; 
            position: relative; 
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }

        .sidebar { 
            width: 260px; 
            min-width: 260px;
            background-color: var(--bg-sidebar); 
            color: white; 
            display: flex; 
            flex-direction: column; 
            padding: 20px 0; 
            flex-shrink: 0; 
            height: 100vh; 
            border-right: 1px solid var(--border-color); 
            z-index: 999;
            transition: transform 0.3s ease, background-color 0.3s, border-color 0.3s;
            position: sticky;
            top: 0;
        }

        .logo-section { display: flex; align-items: center; padding: 10px 25px; margin-bottom: 30px; gap: 12px; }
        .brand-logo-container { width: 32px; height: 32px; border-radius: 50%; background-color: white; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
        .brand-logo-container img { width: 85%; height: 85%; object-fit: contain; }
        .logo-text { font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px; color: #ffffff; white-space: nowrap; }
        
        .menu-items { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .menu-item, .theme-toggle-item { display: flex; align-items: center; padding: 14px 25px; color: var(--text-sidebar-menu); text-decoration: none; font-size: 0.95rem; font-weight: 500; background: none; border: none; width: 100%; text-align: left; cursor: pointer; white-space: nowrap; }
        .menu-item i, .theme-toggle-item i { margin-right: 15px; width: 20px; text-align: center; flex-shrink: 0; }
        .menu-item:hover, .menu-item.active, .theme-toggle-item:hover { background-color: var(--bg-sidebar-hover); color: #ffffff; }
        .menu-item.active { font-weight: 600; background-color: var(--bg-sidebar-hover); color: #ffffff; }
        
        .sidebar-footer { margin-top: auto; display: flex; flex-direction: column; }
        .sidebar-divider { height: 1px; background-color: var(--sidebar-divider); margin: 10px 25px; }
        
        .main-workspace { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            min-width: 0; 
            height: 100vh;
            overflow-y: auto; 
        }

        .top-navbar { 
            height: 75px; 
            background: var(--bg-card); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 0 40px; 
            flex-shrink: 0; 
            border-bottom: 1px solid var(--border-color); 
            gap: 15px; 
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .mobile-nav-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-main);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 8px;
            margin-right: 10px;
            border-radius: 6px;
        }
        .mobile-nav-toggle:hover {
            background-color: var(--input-bg);
        }

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
        .avatar-circle-nav { width: 40px; height: 40px; background: var(--input-bg); color: var(--accent-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; overflow: hidden; border: 1px solid var(--accent-green); flex-shrink: 0; }
        .avatar-circle-nav img { width: 100%; height: 100%; object-fit: cover; }
        .user-info-text h4 { font-size: 0.95rem; color: var(--text-main); font-weight: 700; }
        .user-info-text p { font-size: 0.8rem; color: var(--text-muted); }

        .reports-content { padding: 35px; flex: 1; display: flex; flex-direction: column; gap: 25px; }
        
        .progress-card { background: var(--bg-card); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.01); }
        .progress-header { font-weight: 700; font-size: 0.95rem; margin-bottom: 12px; font-style: italic; color: var(--text-main); }
        .progress-bar-wrapper { width: 100%; background: var(--input-bg); height: 16px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color); }
        .progress-bar-fill { height: 100%; background: <?= $progress_bar_color ?>; width: <?= (int)$green_points ?>%; transition: width 0.5s ease, background-color 0.5s ease; }

        .metrics-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 20px; 
        }
        .metric-card { background: var(--bg-card); padding: 22px; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 4px 10px rgba(0,0,0,0.01); }
        .metric-card h3 { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; margin-bottom: 10px; }
        .metric-value { font-size: 2.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 5px; word-break: break-word; }
        .metric-subtext { font-size: 0.78rem; color: var(--text-muted); font-weight: 500; }
        .text-green { color: var(--accent-green) !important; font-weight: 600; }

        .charts-grid { display: grid; grid-template-columns: 6fr 4fr; gap: 25px; }
        .chart-box { background: var(--bg-card); padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); position: relative; box-shadow: 0 4px 10px rgba(0,0,0,0.01); width: 100%; }
        .chart-title-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 10px; flex-wrap: wrap; }
        .chart-title-bar h3 { font-size: 0.95rem; font-weight: 700; color: var(--text-main); }
        .chart-title-bar select { padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-main); font-size: 0.85rem; cursor: pointer; outline: none; }
        .canvas-container { position: relative; width: 100%; height: 280px; }

        .chart-empty-overlay {
            position: absolute; top: 80px; left: 0; right: 0; bottom: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; color: var(--text-muted); font-weight: 500;
            pointer-events: none; display: none;
        }

        @media (max-width: 1200px) {
            .charts-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 992px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                transform: translateX(-100%);
                box-shadow: 5px 0 15px rgba(0,0,0,0.2);
            }
            .sidebar.show { transform: translateX(0); }
            .mobile-nav-toggle { display: block; }
            .top-navbar { padding: 0 20px; }
            .reports-content { padding: 20px; }
        }

        @media (max-width: 640px) {
            .top-navbar { height: auto; padding: 12px 16px; }
            .header-title-area h2 { font-size: 1.15rem; }
            .header-title-area p { font-size: 0.75rem; }
            .user-nav-profile { gap: 12px; }
            .profile-card { padding-left: 12px; }
            .user-info-text { display: none; }
            .notification-dropdown { right: -40px; width: calc(100vw - 32px); max-width: 320px; }
            .metric-value { font-size: 1.75rem; }
            .chart-box { padding: 15px; }
            .canvas-container { height: 220px; }
            .reports-content { padding: 15px; gap: 15px; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
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
            <div style="display: flex; align-items: center;">
                <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle Sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="header-title-area">
                    <h2>Reports</h2>
                    <p>View your summaries, trends, and historical performance</p>
                </div>
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
                            <?php foreach ($timeframe_options as $tf_code => $tf_label): ?>
                                <option value="<?= htmlspecialchars($tf_code) ?>" <?= $tf_code === 'this_month' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tf_label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="canvas-container">
                        <canvas id="overTimeChart"></canvas>
                    </div>
                    <div class="chart-empty-overlay" id="overTimeChartEmpty">No logs recorded for this timeframe</div>
                </div>

                <div class="chart-box" id="sourceChartContainer">
                    <div class="chart-title-bar">
                        <h3>Emissions by Source</h3>
                        <select id="sourceTimeframeSelect">
                            <?php foreach ($timeframe_options as $tf_code => $tf_label): ?>
                                <option value="<?= htmlspecialchars($tf_code) ?>" <?= $tf_code === 'this_month' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tf_label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="canvas-container">
                        <canvas id="bySourceChart"></canvas>
                    </div>
                    <div class="chart-empty-overlay" id="sourceChartEmpty">No logs recorded for this timeframe</div>
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
                <div class="canvas-container">
                    <canvas id="forecastChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        const mobileNavToggle = document.getElementById('mobileNavToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        }

        if (mobileNavToggle) {
            mobileNavToggle.addEventListener('click', toggleSidebar);
        }
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleSidebar);
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
                text: "You want to log out of your CarbonWise session?",
                icon: 'warning',
                iconColor: '#f42828',
                showCancelButton: true,
                confirmButtonColor: '#2D6A4F',
                cancelButtonColor: '#BA181B',
                confirmButtonText: 'Yes, log me out',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false,
                allowEscapeKey: false,
                background: popupBg,
                color: popupText,
                backdrop: `rgba(0, 0, 0, 0.4)`,
                scrollbarPadding: false,
                heightAuto: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Logging out...',
                        text: 'Please wait a moment.',
                        allowOutsideClick: false,
                        scrollbarPadding: false,
                        heightAuto: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
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

        const timeDatasets = <?= json_encode($formatted_time_datasets); ?>;
        const sourceDatasets = <?= json_encode($timeframe_by_source); ?>;

        const forecastLabels = <?= json_encode($forecast_labels); ?>;
        const forecastData   = <?= json_encode($forecast_data); ?>;

        const getGridColor = (t) => t === 'dark' ? '#1B3A2B' : '#E5E7EB';
        const getLabelColor = (t) => t === 'dark' ? '#9CA3AF' : '#6B7280';

        const initialKey = 'this_month';
        const initialTimeData = timeDatasets[initialKey] || { labels: [], data: [] };
        const initialSourceObj = sourceDatasets[initialKey] || { transportation: 0, electricity: 0, food: 0 };
        const initialSourceData = [initialSourceObj.transportation, initialSourceObj.electricity, initialSourceObj.food];

        const ctxTime = document.getElementById('overTimeChart').getContext('2d');
        const overTimeChart = new Chart(ctxTime, {
            type: 'line',
            data: {
                labels: initialTimeData.labels,
                datasets: [{
                    label: 'Emissions (kg CO2)', 
                    data: initialTimeData.data,
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
                    data: initialSourceData,
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

        function checkOverTimeDataEmpty(key) {
            const timeObj = timeDatasets[key] || { data: [] };
            const sum = timeObj.data.reduce((a, b) => a + b, 0);
            const overlay = document.getElementById('overTimeChartEmpty');
            const canvas = document.getElementById('overTimeChart');

            if (sum === 0 || timeObj.data.length === 0) {
                overlay.style.display = 'flex';
                canvas.style.opacity = '0.05';
            } else {
                overlay.style.display = 'none';
                canvas.style.opacity = '1';
            }
        }

        function checkSourceDataEmpty(key) {
            const srcObj = sourceDatasets[key] || { transportation: 0, electricity: 0, food: 0 };
            const sum = (srcObj.transportation || 0) + (srcObj.electricity || 0) + (srcObj.food || 0);
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
        
        checkOverTimeDataEmpty(initialKey);
        checkSourceDataEmpty(initialKey);

        document.getElementById('timeframeSelect').addEventListener('change', function() {
            const selectedKey = this.value;
            const tfObj = timeDatasets[selectedKey] || { labels: [], data: [] };
            overTimeChart.data.labels = tfObj.labels;
            overTimeChart.data.datasets[0].data = tfObj.data;
            overTimeChart.update();
            checkOverTimeDataEmpty(selectedKey);
        });

        document.getElementById('sourceTimeframeSelect').addEventListener('change', function() {
            const selectedKey = this.value;
            const srcObj = sourceDatasets[selectedKey] || { transportation: 0, electricity: 0, food: 0 };
            bySourceChart.data.datasets[0].data = [srcObj.transportation, srcObj.electricity, srcObj.food];
            bySourceChart.update();
            checkSourceDataEmpty(selectedKey);
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

        window.addEventListener('resize', () => {
            overTimeChart.resize();
            bySourceChart.resize();
            forecastChart.resize();
        });
    </script>
</body>
</html>