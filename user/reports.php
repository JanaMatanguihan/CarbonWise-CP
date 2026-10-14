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

        /* =====================================================================
           REPORTS – app-style redesign (score card, 30-day forecast, mobile shell)
           ===================================================================== */
        :root {
            --fc-line: #3DA66B;
            --mint-card: #E6F4EA;
            --mint-soft: #F1F8F3;
            --mint-border: #A9D8BB;
            --accent-dark: #1E5B3A;
            --fc-grid: #ECEFED;
            --nav-idle: #7A807D;
        }
        [data-theme="dark"] {
            --fc-line: #52B788;
            --mint-card: #14261D;
            --mint-soft: #18241F;
            --mint-border: #2A5A41;
            --accent-dark: #8FD9AE;
            --fc-grid: #1B2B23;
            --nav-idle: #8A948E;
        }

        .t-mob { display: none; }
        .sticky-head { display: contents; }
        .score-wrap { padding: 35px 35px 0; }
        .reports-content { padding-top: 25px; }
        .avatar-icon { display: none; }
        .avatar-circle-nav { text-decoration: none; }
        .bottom-nav { display: none; }

        /* ---- CarbonWise score card ---- */
        .progress-card { text-align: left; background: var(--mint-card); border: none; border-radius: 20px; padding: 18px 22px; box-shadow: none; }
        .progress-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; font-style: normal; font-size: 1rem; margin-bottom: 14px; }
        .ph-left { display: flex; align-items: center; gap: 12px; font-weight: 600; color: var(--text-main); }
        .ph-icon { color: var(--fc-line); font-size: 1.15rem; }
        .ph-score { font-weight: 800; color: var(--accent-dark); }
        .progress-bar-wrapper { height: 12px; border-radius: 999px; background: rgba(61, 166, 107, 0.2); border: none; }
        .progress-bar-fill { border-radius: 999px; }

        /* ---- 30-day forecast card ---- */
        .forecast-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 24px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.01); width: 100%; }
        .fc-head { all: unset; box-sizing: border-box; display: flex; align-items: center; gap: 14px; width: 100%; cursor: pointer; border-radius: 14px; -webkit-tap-highlight-color: transparent; }
        .fc-head:focus-visible { outline: 2px solid var(--fc-line); outline-offset: 4px; }
        .fc-icon { width: 46px; height: 46px; border-radius: 14px; background: var(--mint-card); color: var(--fc-line); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .fc-titles { display: flex; flex-direction: column; gap: 3px; flex: 1; min-width: 0; }
        .fc-title { font-size: 1.05rem; font-weight: 700; color: var(--text-main); }
        .fc-sub { font-size: 0.8rem; color: var(--text-muted); }
        .fc-chevron { color: var(--text-muted); font-size: 0.95rem; transition: transform 0.3s ease; flex-shrink: 0; padding: 6px; }
        .forecast-card.collapsed .fc-chevron { transform: rotate(180deg); }

        .fc-body { display: grid; grid-template-rows: 1fr; transition: grid-template-rows 0.3s ease; }
        .forecast-card.collapsed .fc-body { grid-template-rows: 0fr; }
        .fc-body-inner { overflow: hidden; min-height: 0; }
        .fc-desc { margin: 18px 0 8px; font-size: 0.85rem; line-height: 1.55; color: var(--text-muted); }

        .fc-chart { display: flex; align-items: flex-start; }
        .fc-yaxis { position: relative; width: 34px; flex-shrink: 0; }
        .fc-ylabel { position: absolute; left: 0; font-size: 0.72rem; line-height: 16px; color: var(--text-muted); }
        .fc-scroll { flex: 1; min-width: 0; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; scrollbar-width: thin; cursor: grab; user-select: none; -webkit-user-select: none; }
        .fc-scroll.dragging { cursor: grabbing; }
        .fc-scroll svg { display: block; touch-action: pan-x pan-y; }
        .fc-grid { stroke: var(--fc-grid); stroke-width: 1.5; }
        .fc-line { fill: none; stroke: var(--fc-line); stroke-width: 3.5; stroke-linecap: round; stroke-linejoin: round; }
        .fc-dot { fill: var(--bg-card); stroke: var(--fc-line); stroke-width: 2.5; }
        .fc-dot-sel { fill: var(--fc-line); stroke: var(--fc-line); stroke-width: 2.5; }
        .fc-halo { fill: var(--fc-line); opacity: 0.18; }
        .fc-xlabel { font-size: 11.5px; fill: var(--text-muted); }
        .fc-hit { cursor: pointer; }

        .fc-selected { display: flex; align-items: center; gap: 14px; margin-top: 18px; padding: 14px 18px; border: 1.5px solid var(--mint-border); background: var(--mint-card); border-radius: 18px; animation: fcIn 0.25s ease; }
        .fc-selected[hidden] { display: none; }
        @keyframes fcIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        .fc-sel-ico { width: 44px; height: 44px; border-radius: 50%; background: var(--bg-card); color: var(--fc-line); display: flex; align-items: center; justify-content: center; font-size: 1.05rem; flex-shrink: 0; }
        .fc-sel-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .fc-sel-date { font-size: 0.82rem; color: var(--text-muted); }
        .fc-sel-val { font-size: 1.08rem; font-weight: 700; color: var(--accent-dark); }
        .fc-sel-val sub { font-size: 0.65em; }
        .fc-sel-close { all: unset; margin-left: auto; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); cursor: pointer; border-radius: 50%; flex-shrink: 0; }
        .fc-sel-close:hover { background: rgba(0,0,0,0.05); }
        .fc-sel-close:focus-visible { outline: 2px solid var(--fc-line); }

        .fc-stats { display: grid; grid-template-columns: 1fr 1fr; margin-top: 16px; padding: 16px 6px; background: var(--mint-soft); border-radius: 16px; }
        .fc-stat { display: flex; flex-direction: column; gap: 8px; padding: 0 16px; min-width: 0; }
        .fc-stat + .fc-stat { border-left: 1px solid var(--border-color); }
        .fc-stat-label { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; color: var(--text-muted); white-space: nowrap; }
        .fc-stat-label i { color: var(--fc-line); }
        .fc-stat-val { font-size: 1.2rem; font-weight: 700; color: var(--text-main); }

        @media (max-width: 992px) {
            .score-wrap { padding: 20px 20px 0; }
            .reports-content { padding-top: 20px; }
        }

        /* ---- Phone layout: matches the CarbonWise mobile app ---- */
        @media (max-width: 768px) {
            :root:not([data-theme="dark"]) { --bg-body: #F2F6F3; }

            .sidebar, .sidebar-overlay, .mobile-nav-toggle { display: none !important; }
            .t-desk { display: none; }
            .t-mob { display: inline; }

            .main-workspace { height: 100vh; height: 100dvh; }

            .sticky-head { display: block; position: sticky; top: 0; z-index: 100; background: var(--bg-body); padding-bottom: 14px; }
            .top-navbar { position: static; height: auto; background: transparent; border: none; padding: 20px 18px 14px; }
            .header-title-area h2 { font-size: 1.45rem; font-weight: 700; letter-spacing: -0.01em; margin-bottom: 4px; }
            .header-title-area p { font-size: 0.88rem; }

            .user-nav-profile { gap: 10px; }
            .notification-bell, .avatar-circle-nav { width: 46px; height: 46px; background: var(--bg-card); color: var(--accent-green); border: none; box-shadow: 0 1px 6px rgba(20, 60, 40, 0.06); font-size: 1.15rem; }
            .profile-card { border-left: none; padding-left: 0; }
            .avatar-initials { display: none; }
            .avatar-icon { display: inline; }
            .notification-badge { top: 0; right: 0; }

            .score-wrap { padding: 0 18px; }
            .reports-content { padding: 4px 18px calc(120px + env(safe-area-inset-bottom)); gap: 16px; }

            .metrics-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
            .metric-card, .chart-box, .forecast-card { border: none; border-radius: 24px; box-shadow: 0 2px 14px rgba(20, 60, 40, 0.05); }
            .metric-card { padding: 18px; }
            .metric-value { font-size: 1.5rem; }
            .chart-box, .forecast-card { padding: 18px; }
            .fc-scroll { scrollbar-width: none; }
            .fc-selected { gap: 12px; padding: 12px 14px; }
            .fc-sel-ico { width: 40px; height: 40px; }
            .fc-sel-val { font-size: 0.98rem; }
            .fc-sel-close { width: 32px; height: 32px; }
            .fc-sub { font-size: 0.78rem; }
            .fc-scroll::-webkit-scrollbar { display: none; }

            /* bottom tab bar */
            .bottom-nav { display: grid; grid-template-columns: repeat(5, 1fr); align-items: end; position: fixed; left: 0; right: 0; bottom: 0; z-index: 500; background: var(--bg-card); padding: 10px 6px calc(10px + env(safe-area-inset-bottom)); box-shadow: 0 -2px 18px rgba(0, 0, 0, 0.07); }
            .bn-item { display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 4px 0; text-decoration: none; color: var(--nav-idle); font-size: 0.7rem; font-weight: 500; -webkit-tap-highlight-color: transparent; }
            .bn-item i { font-size: 1.45rem; }
            .bn-item.active { color: var(--fc-line); font-weight: 600; }
            .bn-fab { display: flex; flex-direction: column; align-items: center; gap: 4px; text-decoration: none; color: var(--nav-idle); font-size: 0.7rem; font-weight: 500; margin-top: -40px; -webkit-tap-highlight-color: transparent; }
            .bn-fab-btn { width: 62px; height: 62px; border-radius: 50%; background: var(--fc-line); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; border: 5px solid var(--bg-card); box-shadow: 0 4px 14px rgba(61, 166, 107, 0.35); }
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
        <div class="sticky-head">
        <div class="top-navbar">
            <div style="display: flex; align-items: center;">
                <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle Sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="header-title-area">
                    <h2><span class="t-desk">Reports</span><span class="t-mob">View your Reports</span></h2>
                    <p><span class="t-desk">View your summaries, trends, and historical performance</span><span class="t-mob">Manage your carbon footprint.</span></p>
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
                    <a href="profile.php" class="avatar-circle-nav" aria-label="View profile">
                        <?php if (!empty($avatar_url)): ?>
                            <img src="<?= htmlspecialchars($avatar_url) ?>" alt="User Avatar">
                        <?php else: ?>
                            <span class="avatar-initials"><?= htmlspecialchars($initials) ?></span>
                            <i class="fa-regular fa-user avatar-icon"></i>
                        <?php endif; ?>
                    </a>
                    <div class="user-info-text">
                        <h4><?= htmlspecialchars($full_name) ?></h4>
                        <p><?= htmlspecialchars($role) ?></p>
                    </div>
                </div>
            </div>
        </div>

            <div class="score-wrap">
                <div class="progress-card">
                    <div class="progress-header">
                        <span class="ph-left"><i class="fa-solid fa-leaf ph-icon"></i><span>CarbonWise Score</span></span>
                        <span class="ph-score"><?= (int)$green_points ?>/100</span>
                    </div>
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar-fill"></div>
                    </div>
                </div>
            </div>
        </div><!-- /.sticky-head -->

        <div class="reports-content">

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
            <div class="forecast-card" id="forecastCard">
                <button type="button" class="fc-head" id="fcToggle" aria-expanded="true" aria-controls="fcBody">
                    <span class="fc-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="3 18 9 12 13 16 21 8"></polyline>
                            <circle cx="3" cy="18" r="1.2" fill="currentColor"></circle>
                            <circle cx="9" cy="12" r="1.2" fill="currentColor"></circle>
                            <circle cx="13" cy="16" r="1.2" fill="currentColor"></circle>
                            <circle cx="21" cy="8" r="1.2" fill="currentColor"></circle>
                            <path d="M15 3v3M13.5 4.5h3" stroke-width="1.6"></path>
                            <path d="M5 5v2M4 6h2" stroke-width="1.4"></path>
                        </svg>
                    </span>
                    <span class="fc-titles">
                        <span class="fc-title">30-Day Emissions Forecast</span>
                        <span class="fc-sub">Tap to see your projected carbon footprint.</span>
                    </span>
                    <i class="fa-solid fa-chevron-up fc-chevron" aria-hidden="true"></i>
                </button>

                <div class="fc-body" id="fcBody">
                    <div class="fc-body-inner">
                        <p class="fc-desc">Projected carbon footprint for the upcoming month. Swipe horizontally to see all 30 days.</p>

                        <div class="fc-chart">
                            <div class="fc-yaxis" id="fcY" aria-hidden="true"></div>
                            <div class="fc-scroll" id="fcScroll"><svg id="fcSvg" role="img" aria-label="30-day emissions forecast line chart"></svg></div>
                        </div>

                        <div class="fc-selected" id="fcSelected" hidden>
                            <span class="fc-sel-ico"><i class="fa-regular fa-calendar"></i></span>
                            <span class="fc-sel-text">
                                <span class="fc-sel-date" id="fcSelDate"></span>
                                <span class="fc-sel-val" id="fcSelVal"></span>
                            </span>
                            <button type="button" class="fc-sel-close" id="fcSelClose" aria-label="Clear selection"><i class="fa-solid fa-xmark"></i></button>
                        </div>

                        <div class="fc-stats">
                            <div class="fc-stat">
                                <span class="fc-stat-label"><i class="fa-solid fa-arrow-trend-up"></i> Daily Average</span>
                                <span class="fc-stat-val" id="fcAvg">–</span>
                            </div>
                            <div class="fc-stat">
                                <span class="fc-stat-label"><i class="fa-solid fa-arrow-trend-up"></i> <span id="fcPeakLabel">Peak Day</span></span>
                                <span class="fc-stat-val" id="fcPeak">–</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <nav class="bottom-nav" aria-label="Primary">
        <a href="dashboard.php" class="bn-item"><i class="fa-solid fa-house"></i><span>Home</span></a>
        <a href="reports.php" class="bn-item active" aria-current="page"><i class="fa-solid fa-chart-simple"></i><span>Reports</span></a>
        <a href="activity_input.php" class="bn-fab" aria-label="Input activity">
            <span class="bn-fab-btn"><i class="fa-solid fa-plus"></i></span>
            <span class="bn-fab-label">Input</span>
        </a>
        <a href="mitigation_strategies.php" class="bn-item"><i class="fa-regular fa-lightbulb"></i><span>Strategies</span></a>
        <a href="profile.php" class="bn-item"><i class="fa-regular fa-user"></i><span>Profile</span></a>
    </nav>

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

        // ===== 30-Day Forecast (horizontally scrollable, tap-to-inspect) =====
        (function () {
            var labels = forecastLabels.slice();
            var vals = forecastData.map(Number);
            var n = vals.length;

            var card = document.getElementById('forecastCard');
            var toggle = document.getElementById('fcToggle');
            var scroll = document.getElementById('fcScroll');
            var svg = document.getElementById('fcSvg');
            var yAxis = document.getElementById('fcY');
            var selBox = document.getElementById('fcSelected');
            var selDate = document.getElementById('fcSelDate');
            var selVal = document.getElementById('fcSelVal');
            var selClose = document.getElementById('fcSelClose');
            if (!card || !svg || !n) { if (card) card.style.display = 'none'; return; }

            // summary stats
            var sum = vals.reduce(function (a, b) { return a + b; }, 0);
            var peakIdx = 0;
            vals.forEach(function (v, i) { if (v > vals[peakIdx]) peakIdx = i; });
            document.getElementById('fcAvg').textContent = (sum / n).toFixed(2) + ' kg';
            document.getElementById('fcPeak').textContent = vals[peakIdx].toFixed(2) + ' kg';
            document.getElementById('fcPeakLabel').textContent = 'Peak Day (' + labels[peakIdx] + ')';

            // geometry
            var H = 190, TOP = 14, BOT = 34, PAD_L = 24, PAD_R = 24, MIN_STEP = 48;
            var TOTAL_H = TOP + H + BOT;
            yAxis.style.height = TOTAL_H + 'px';

            var maxVal = Math.max.apply(null, vals.concat([1]));
            var rough = (maxVal * 1.15) / 4;
            var mag = Math.pow(10, Math.floor(Math.log10(rough)));
            var norm = rough / mag;
            var stepY = (norm <= 1 ? 1 : norm <= 2 ? 2 : norm <= 2.5 ? 2.5 : norm <= 5 ? 5 : 10) * mag;
            var topVal = stepY * 4;

            // y labels (fixed, outside the scroller)
            var yHtml = '';
            for (var k = 1; k <= 4; k++) {
                var yy = TOP + H - (k * stepY / topVal) * H;
                yHtml += '<span class="fc-ylabel" style="top:' + (yy - 8) + 'px">' + (k * stepY).toFixed(1) + '</span>';
            }
            yAxis.innerHTML = yHtml;

            // monotone cubic path (no overshoot below/above data)
            function smooth(xs, ys) {
                var m = [], t = [], i;
                for (i = 0; i < xs.length - 1; i++) m[i] = (ys[i + 1] - ys[i]) / (xs[i + 1] - xs[i]);
                t[0] = m[0]; t[xs.length - 1] = m[xs.length - 2];
                for (i = 1; i < xs.length - 1; i++) t[i] = (m[i - 1] * m[i] <= 0) ? 0 : 2 * m[i - 1] * m[i] / (m[i - 1] + m[i]);
                var d = 'M' + xs[0] + ',' + ys[0];
                for (i = 0; i < xs.length - 1; i++) {
                    var dx = (xs[i + 1] - xs[i]) / 3;
                    d += ' C' + (xs[i] + dx) + ',' + (ys[i] + t[i] * dx) + ' ' + (xs[i + 1] - dx) + ',' + (ys[i + 1] - t[i + 1] * dx) + ' ' + xs[i + 1] + ',' + ys[i + 1];
                }
                return d;
            }

            var selected = null;
            var xs = [], ys = [], stepX = MIN_STEP;

            function render() {
                var cw = scroll.clientWidth || 300;
                stepX = Math.max(MIN_STEP, (cw - PAD_L - PAD_R) / Math.max(1, n - 1));
                var W = PAD_L + PAD_R + stepX * (n - 1);
                xs = vals.map(function (_, i) { return PAD_L + i * stepX; });
                ys = vals.map(function (v) { return TOP + H - (v / topVal) * H; });
                var base = TOP + H;

                var out = '<defs><linearGradient id="fcFill" x1="0" y1="0" x2="0" y2="1">' +
                    '<stop offset="0%" stop-color="var(--fc-line)" stop-opacity="0.28"/>' +
                    '<stop offset="100%" stop-color="var(--fc-line)" stop-opacity="0.02"/></linearGradient></defs>';

                for (var g = 1; g <= 4; g++) {
                    var gy = TOP + H - (g * stepY / topVal) * H;
                    out += '<line x1="0" x2="' + W + '" y1="' + gy + '" y2="' + gy + '" class="fc-grid"/>';
                }

                var line = smooth(xs, ys);
                out += '<path d="' + line + ' L' + xs[n - 1] + ',' + base + ' L' + xs[0] + ',' + base + ' Z" fill="url(#fcFill)"/>';
                out += '<path d="' + line + '" class="fc-line"/>';

                for (var i = 0; i < n; i++) {
                    if (i % 2 === 0) out += '<text x="' + xs[i] + '" y="' + (base + 26) + '" text-anchor="middle" class="fc-xlabel">' + labels[i] + '</text>';
                }
                for (var j = 0; j < n; j++) {
                    if (j === selected) {
                        out += '<circle cx="' + xs[j] + '" cy="' + ys[j] + '" r="12" class="fc-halo"/>' +
                               '<circle cx="' + xs[j] + '" cy="' + ys[j] + '" r="7.5" class="fc-dot-sel"/>';
                    } else {
                        out += '<circle cx="' + xs[j] + '" cy="' + ys[j] + '" r="5.5" class="fc-dot"/>';
                    }
                }
                for (var h = 0; h < n; h++) {
                    out += '<rect x="' + (xs[h] - stepX / 2) + '" y="0" width="' + stepX + '" height="' + TOTAL_H + '" fill="transparent" class="fc-hit" data-i="' + h + '"/>';
                }

                svg.setAttribute('width', W);
                svg.setAttribute('height', TOTAL_H);
                svg.setAttribute('viewBox', '0 0 ' + W + ' ' + TOTAL_H);
                svg.innerHTML = out;
            }

            function showSelection() {
                if (selected === null) { selBox.hidden = true; return; }
                selDate.textContent = labels[selected];
                selVal.innerHTML = vals[selected].toFixed(2) + ' kg CO<sub>2</sub>e predicted';
                selBox.hidden = false;
                selBox.style.animation = 'none'; void selBox.offsetWidth; selBox.style.animation = '';
            }

            function select(i) {
                selected = (selected === i) ? null : i;
                render();
                showSelection();
                if (selected !== null) {
                    var cw = scroll.clientWidth, x = xs[selected];
                    if (x < scroll.scrollLeft + 30 || x > scroll.scrollLeft + cw - 30) {
                        scroll.scrollTo({ left: Math.max(0, x - cw / 2), behavior: 'smooth' });
                    }
                }
            }

            // click / tap on a day
            var dragged = false;
            svg.addEventListener('click', function (e) {
                if (dragged) { dragged = false; return; }
                var hit = e.target.closest('[data-i]');
                if (hit) select(parseInt(hit.getAttribute('data-i'), 10));
            });
            selClose.addEventListener('click', function () { selected = null; render(); showSelection(); });

            // mouse drag-to-scroll (touch uses native swipe)
            var down = false, startX = 0, startL = 0;
            scroll.addEventListener('pointerdown', function (e) {
                if (e.pointerType !== 'mouse') return;
                down = true; dragged = false; startX = e.clientX; startL = scroll.scrollLeft;
            });
            window.addEventListener('pointermove', function (e) {
                if (!down) return;
                var dx = e.clientX - startX;
                if (Math.abs(dx) > 4) { dragged = true; scroll.classList.add('dragging'); }
                if (dragged) scroll.scrollLeft = startL - dx;
            });
            window.addEventListener('pointerup', function () { down = false; scroll.classList.remove('dragging'); });

            // collapse / expand
            toggle.addEventListener('click', function () {
                var collapsed = card.classList.toggle('collapsed');
                toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                if (!collapsed) render();
            });

            var rt;
            window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(render, 120); });

            render();
        })();

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

        }
        updateChartThemeColors(currentTheme);

        window.addEventListener('resize', () => {
            overTimeChart.resize();
            bySourceChart.resize();
        });
    </script>
</body>
</html>