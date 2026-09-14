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

// User department, campus, and profile picture context
$user_department = $_SESSION['user_department'] ?? ($user_data['department'] ?? 'Unassigned');
$user_campus     = $_SESSION['user_campus'] ?? ($user_data['campus'] ?? 'Unassigned');
$avatar_url      = $user_data['avatar_url'] ?? null;

// Fetch missing department, campus, OR profile_picture from DB
if ($user_id) {
    $stmt_u = $pdo->prepare("SELECT department, campus, profile_picture FROM users WHERE id = :id LIMIT 1");
    $stmt_u->execute([':id' => $user_id]);
    $u_info = $stmt_u->fetch();
    if ($u_info) {
        if ($user_department === 'Unassigned') {
            $user_department = !empty($u_info['department']) ? $u_info['department'] : $user_department;
        }
        if ($user_campus === 'Unassigned') {
            $user_campus = !empty($u_info['campus']) ? $u_info['campus'] : $user_campus;
        }
        if (empty($avatar_url) && !empty($u_info['profile_picture'])) {
            $avatar_url = $u_info['profile_picture'];
        }
    }
}

// Dynamic Profile Picture Logic / Initials Fallback
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

$user_email = $_SESSION['user_email'] ?? ($user_data['email'] ?? 'unknown@g.batstate-u.edu.ph');

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
// 1. DYNAMIC INDIVIDUAL STATUS FROM NEON (`carbon_records`)
// ==========================================================================
$week_data  = ['transportation' => 0.0, 'electricity' => 0.0, 'food' => 0.0];
$month_data = ['transportation' => 0.0, 'electricity' => 0.0, 'food' => 0.0];

if ($user_id) {
    // Exact 7 days aggregation
    $stmt_ind_w = $pdo->prepare("
        SELECT 
            COALESCE(SUM(transportation), 0) as total_trans,
            COALESCE(SUM(electricity), 0) as total_elec,
            COALESCE(SUM(food), 0) as total_food
        FROM carbon_records
        WHERE user_id = :user_id 
          AND record_date >= CURRENT_DATE - INTERVAL '6 days'
    ");
    $stmt_ind_w->execute([':user_id' => $user_id]);
    $res_w = $stmt_ind_w->fetch();
    if ($res_w) {
        $week_data['transportation'] = (float)$res_w['total_trans'];
        $week_data['electricity']    = (float)$res_w['total_elec'];
        $week_data['food']           = (float)$res_w['total_food'];
    }

    // Current Calendar Month aggregation
    $stmt_ind_m = $pdo->prepare("
        SELECT 
            COALESCE(SUM(transportation), 0) as total_trans,
            COALESCE(SUM(electricity), 0) as total_elec,
            COALESCE(SUM(food), 0) as total_food
        FROM carbon_records
        WHERE user_id = :user_id 
          AND record_date >= DATE_TRUNC('month', CURRENT_DATE)
    ");
    $stmt_ind_m->execute([':user_id' => $user_id]);
    $res_m = $stmt_ind_m->fetch();
    if ($res_m) {
        $month_data['transportation'] = (float)$res_m['total_trans'];
        $month_data['electricity']    = (float)$res_m['total_elec'];
        $month_data['food']           = (float)$res_m['total_food'];
    }
}

// ==========================================================================
// 2. MASTER USER RELATIONS & DEPT/CAMPUS EMISSIONS
// ==========================================================================
$dept_week_emissions   = [];
$dept_month_emissions  = [];
$campus_week_emissions = [];

try {
    // Department & Campus Emissions (This Week - Last 7 Days)
    $stmt_dept_w = $pdo->prepare("
        SELECT COALESCE(u.department, 'Unassigned') as department, 
               COALESCE(u.campus, 'Unassigned') as campus, 
               SUM(cr.total_emission) as total_emission
        FROM carbon_records cr
        JOIN users u ON cr.user_id = u.id
        WHERE cr.record_date >= CURRENT_DATE - INTERVAL '6 days'
        GROUP BY u.department, u.campus
    ");
    $stmt_dept_w->execute();
    $records_week = $stmt_dept_w->fetchAll();

    foreach ($records_week as $rec) {
        $dept = $rec['department'];
        $camp = $rec['campus'];
        $emissions = (float)$rec['total_emission'];

        if (!isset($dept_week_emissions[$dept])) { $dept_week_emissions[$dept] = 0.0; }
        $dept_week_emissions[$dept] += $emissions;

        if (!isset($campus_week_emissions[$camp])) { $campus_week_emissions[$camp] = 0.0; }
        $campus_week_emissions[$camp] += $emissions;
    }

    // Department Emissions (This Month - Calendar Month)
    $stmt_dept_m = $pdo->prepare("
        SELECT COALESCE(u.department, 'Unassigned') as department, 
               SUM(cr.total_emission) as total_emission
        FROM carbon_records cr
        JOIN users u ON cr.user_id = u.id
        WHERE cr.record_date >= DATE_TRUNC('month', CURRENT_DATE)
        GROUP BY u.department
    ");
    $stmt_dept_m->execute();
    $records_month = $stmt_dept_m->fetchAll();

    foreach ($records_month as $rec) {
        $dept = $rec['department'];
        $emissions = (float)$rec['total_emission'];

        if (!isset($dept_month_emissions[$dept])) { $dept_month_emissions[$dept] = 0.0; }
        $dept_month_emissions[$dept] += $emissions;
    }
} catch (PDOException $e) {
    // Exception logged quietly
}

arsort($dept_week_emissions);
arsort($dept_month_emissions);
unset($dept_week_emissions['Others'], $dept_month_emissions['Others'], $dept_week_emissions['Unassigned'], $dept_month_emissions['Unassigned']);

// ==========================================================================
// 3. INDIVIDUAL PERCENTILE RANKING CALCULATION
// ==========================================================================
$user_percentile_ranking = "N/A";
$user_total_week_emissions = array_sum($week_data);

$stmt_user_totals = $pdo->prepare("
    SELECT user_id, SUM(total_emission) as total
    FROM carbon_records
    WHERE record_date >= CURRENT_DATE - INTERVAL '6 days'
    GROUP BY user_id
");
$stmt_user_totals->execute();
$all_user_totals = $stmt_user_totals->fetchAll();

if (!empty($all_user_totals)) {
    $individual_totals = [];
    foreach ($all_user_totals as $row) {
        $individual_totals[$row['user_id']] = (float)$row['total'];
    }

    if (!isset($individual_totals[$user_id])) { 
        $individual_totals[$user_id] = $user_total_week_emissions; 
    }
    asort($individual_totals);

    $total_tracked_users = count($individual_totals);
    $higher_footprint_count = 0;
    foreach ($individual_totals as $uid => $emissions) {
        if ($uid != $user_id && $emissions > $user_total_week_emissions) { 
            $higher_footprint_count++; 
        }
    }

    if ($total_tracked_users > 1) {
        $percentile = ($higher_footprint_count / ($total_tracked_users - 1)) * 100;
        if ($percentile >= 90) { $user_percentile_ranking = "Top 10%"; }
        elseif ($percentile >= 75) { $user_percentile_ranking = "Top 25%"; }
        elseif ($percentile >= 50) { $user_percentile_ranking = "Top 50%"; }
        else { $user_percentile_ranking = "Top 75%"; }
    } else {
        $user_percentile_ranking = "Top 10%";
    }
}

// ==========================================================================
// 4. CAMPUS & DEPARTMENT RANKINGS
// ==========================================================================
$campus_rank = "N/A";
$total_campuses = 0;

if (!empty($user_campus) && $user_campus !== 'Unassigned') {
    unset($campus_week_emissions['Others'], $campus_week_emissions['Unassigned']);
    asort($campus_week_emissions);
    $total_campuses = count($campus_week_emissions);

    if ($total_campuses > 0) {
        $c_pos = 1;
        $found_campus = false;
        foreach ($campus_week_emissions as $c_name => $em) {
            if (strtoupper(trim($c_name)) === strtoupper(trim($user_campus))) {
                $found_campus = true;
                break;
            }
            $c_pos++;
        }

        if ($found_campus) {
            $ends = array('th','st','nd','rd','th','th','th','th','th','th');
            if ((($c_pos % 100) >= 11) && (($c_pos % 100) <= 13)) {
                $campus_rank = $c_pos . 'th';
            } else {
                $campus_rank = $c_pos . $ends[$c_pos % 10];
            }
        }
    }
}

$department_rank = "N/A";
if (!empty($user_department) && $user_department !== 'Unassigned') {
    $cleanest_depts = $dept_week_emissions;
    asort($cleanest_depts); 

    $rank_position = 1;
    $found_dept_in_records = false;
    foreach ($cleanest_depts as $dept_name => $emissions) {
        if (strtoupper(trim($dept_name)) === strtoupper(trim($user_department))) {
            $found_dept_in_records = true;
            break;
        }
        $rank_position++;
    }

    if ($found_dept_in_records) {
        $ends = array('th','st','nd','rd','th','th','th','th','th','th');
        if ((($rank_position % 100) >= 11) && (($rank_position % 100) <= 13)) {
            $department_rank = $rank_position . 'th';
        } else {
            $department_rank = $rank_position . $ends[$rank_position % 10];
        }
    }
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
    <title>CarbonWise - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .dashboard-content { padding: 35px; flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 25px; }
        .cards-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .ranking-card { background: var(--bg-card); padding: 25px; border-radius: 12px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid var(--border-color); }
        .ranking-card h3 { font-size: 0.95rem; color: var(--text-main); text-align: left; margin-bottom: 10px; font-weight: 700; }
        .card-icon { font-size: 2.8rem; color: var(--accent-green); margin: 15px 0 10px 0; }
        .sub-text { font-size: 0.75rem; color: var(--accent-green); margin-bottom: 5px; font-style: italic; font-weight: 500; }
        .metric-value { font-size: 2.8rem; font-weight: 800; color: var(--text-metric-label); margin-bottom: 10px; }
        .desc-text { font-size: 0.75rem; color: var(--text-muted); line-height: 1.4; padding: 0 5px; }

        .charts-row { display: grid; grid-template-columns: 4fr 6fr; gap: 25px; margin-bottom: 20px; }
        .chart-card { background: var(--bg-card); padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid var(--border-color); }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .chart-header h3 { font-size: 0.95rem; color: var(--text-main); font-weight: 700; }
        .chart-header select { padding: 5px 10px; border-radius: 6px; border: 1px solid var(--border-color); font-size: 0.85rem; outline: none; background-color: var(--input-bg); color: var(--text-main); cursor: pointer; }
        .chart-container { position: relative; width: 100%; height: 260px; }
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
            <a href="dashboard.php" class="menu-item active"><i class="fa-solid fa-border-all"></i> Dashboard</a>
            <a href="activity_input.php" class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Activity Input</a>
            <a href="reports.php" class="menu-item"><i class="fa-solid fa-chart-simple"></i> Reports</a>
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
                <h2>Dashboard</h2>
                <p>Welcome Back, <?= htmlspecialchars($full_name) ?>!</p>
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
                        <div id="dashboardNotifContainer" style="max-height: 360px; overflow-y: auto;">
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
                                <div class="notification-item <?= $is_unread ? 'unread' : '' ?>" onclick="markAsReadDashboard(<?= intval($notif['id']) ?>, this)">
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
                
        <div class="dashboard-content">
            <div class="cards-row">
                <div class="ranking-card">
                    <h3>Your Current Ranking</h3>
                    <div class="card-icon"><i class="fa-solid fa-recycle"></i></div>
                    <p class="sub-text">You currently belong to the</p>
                    <div class="metric-value"><?= htmlspecialchars($user_percentile_ranking) ?></div>
                    <p class="desc-text">
                        <?php if ($user_percentile_ranking === 'N/A'): ?>
                            Data pending. Keep tracking your metrics to generate your carbon reduction percentile standing.
                        <?php else: ?>
                            Excellent work! You are currently among the <?= htmlspecialchars($user_percentile_ranking) ?> of users with the lowest carbon emissions this week.
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="ranking-card">
                    <h3>Department Ranking</h3>
                    <div class="card-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <p class="sub-text">Your department currently ranks</p>
                    <div class="metric-value"><?= htmlspecialchars($department_rank) ?></div>
                    <p class="desc-text">
                        <?php 
                        if ($user_department === 'Unassigned') {
                            echo "Please update your profile information. Your current department tracking is unassigned.";
                        } else {
                            if ($department_rank !== 'N/A') {
                                echo "Great push! The " . htmlspecialchars($user_department) . " department is currently ranked " . htmlspecialchars($department_rank) . " among all tracked departments for weekly emissions.";
                            } else {
                                echo "Metrics processing. Keep tracking your carbon usage to place your department.";
                            }
                        }
                        ?> 
                    </p>
                </div>

                <div class="ranking-card">
                    <h3>Campus Ranking</h3>
                    <div class="card-icon"><i class="fa-solid fa-building-user"></i></div>
                    <p class="sub-text">Your campus currently ranks</p>
                    <div class="metric-value"><?= htmlspecialchars($campus_rank) ?></div>
                    <p class="desc-text">
                        <?php if ($user_campus === 'Unassigned'): ?>
                            Please configure your institutional campus inside your user account profile layout.
                        <?php elseif ($campus_rank === 'N/A' || $total_campuses === 0): ?>
                            Emissions tracking data is currently processing for your campus community view.
                        <?php else: ?>
                            Outstanding achievement! The <strong><?= htmlspecialchars($user_campus) ?></strong> campus ranks <strong><?= htmlspecialchars($campus_rank) ?></strong> out of <?= (int)$total_campuses; ?> tracked active university campuses for sustainable low footprints.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Individual Status</h3>
                        <select id="individualTimeframe">
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="individualChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Department Ranking</h3>
                        <select id="departmentTimeframe">
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="departmentChart"></canvas>
                    </div>
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
            updateChartThemes(newTheme);
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

        function markAsReadDashboard(id, element) {
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

        const individualDatasets = {
            week: [<?= (float)$week_data['transportation']; ?>, <?= (float)$week_data['electricity']; ?>, <?= (float)$week_data['food']; ?>],
            month: [<?= (float)$month_data['transportation']; ?>, <?= (float)$month_data['electricity']; ?>, <?= (float)$month_data['food']; ?>]
        };

        const departmentDatasets = {
            week: {
                labels: <?= json_encode(array_keys($dept_week_emissions)); ?>,
                data: <?= json_encode(array_values($dept_week_emissions)); ?>
            },
            month: {
                labels: <?= json_encode(array_keys($dept_month_emissions)); ?>,
                data: <?= json_encode(array_values($dept_month_emissions)); ?>
            }
        };

        const getGridColor = (theme) => theme === 'dark' ? '#1B3A2B' : '#E5E7EB';
        const getLabelColor = (theme) => theme === 'dark' ? '#9CA3AF' : '#6B7280';

        const ctxIndiv = document.getElementById('individualChart').getContext('2d');
        const individualChart = new Chart(ctxIndiv, {
            type: 'bar',
            data: {
                labels: ['Transportation', 'Electricity Usage', 'Food Consumption'],
                datasets: [{
                    label: 'Carbon Emissions (kg CO2)',
                    data: individualDatasets.week,
                    backgroundColor: ['#5ea3e3', '#e9c46a', '#76d7b6'],
                    borderRadius: 6,
                    barThickness: 28
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: getGridColor(currentTheme) }, ticks: { color: getLabelColor(currentTheme) } },
                    x: { grid: { display: false }, ticks: { color: getLabelColor(currentTheme) } }
                },
                plugins: { legend: { display: false } }
            }
        });

        const ctxDept = document.getElementById('departmentChart').getContext('2d');
        const departmentChart = new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: departmentDatasets.week.labels,
                datasets: [{
                    label: 'Emissions (kg CO2)',
                    data: departmentDatasets.week.data,
                    backgroundColor: ['#0074d9', '#52b788', '#8a4215', '#9c6633', '#628cb3'],
                    borderRadius: 6,
                    barThickness: 24
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: getGridColor(currentTheme) }, ticks: { color: getLabelColor(currentTheme) } },
                    x: { grid: { display: false }, ticks: { color: getLabelColor(currentTheme) } }
                },
                plugins: { legend: { display: false } }
            }
        });

        document.getElementById('individualTimeframe').addEventListener('change', function() {
            individualChart.data.datasets[0].data = individualDatasets[this.value];
            individualChart.update();
        });

        document.getElementById('departmentTimeframe').addEventListener('change', function() {
            departmentChart.data.labels = departmentDatasets[this.value].labels;
            departmentChart.data.datasets[0].data = departmentDatasets[this.value].data;
            departmentChart.update();
        });

        function updateChartThemes(theme) {
            const gridColor = getGridColor(theme);
            const labelColor = getLabelColor(theme);
            [individualChart, departmentChart].forEach(chart => {
                chart.options.scales.y.grid.color = gridColor;
                chart.options.scales.y.ticks.color = labelColor;
                chart.options.scales.x.ticks.color = labelColor;
                chart.update();
            });
        }
        updateChartThemes(currentTheme);
    </script>
</body>
</html>