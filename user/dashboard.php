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
$user_data = $_SESSION['user_data'] ?? [];
$user_id = $user_data['id'] ?? null; 
$user_metadata = $user_data['user_metadata'] ?? [];

// 3. Check direct custom database array keys first BEFORE falling back to Supabase metadata objects
$raw_name = $user_data['full_name'] ?? ($user_metadata['full_name'] ?? ($user_metadata['name'] ?? ''));

if (isset($user_data['role']) && strtolower($user_data['role']) !== 'authenticated') {
    $raw_role = $user_data['role'];
} else {
    $raw_role = $user_metadata['role'] ?? '';
}

// 4. Format Cases dynamically
$full_name = !empty($raw_name) ? ucwords(strtolower(trim($raw_name))) : 'Unknown User'; 
$role      = (!empty($raw_role) && strtolower($raw_role) !== 'authenticated') ? ucwords(strtolower(trim($raw_role))) : 'Student'; 

// --- SUPABASE CONFIGURATION ---
$supabase_url = 'YOUR_SUPABASE_URL'; 
$supabase_key = 'YOUR_SUPABASE_ANON_KEY';
$auth_header = 'Authorization: Bearer ' . $_SESSION['user_token'];

function querySupabaseEndpoint($url, $key, $auth) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $key,
        $auth,
        'Content-Type: application/json'
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

// --- DYNAMIC FETCHES ---
$notif_url = $supabase_url . '/rest/v1/system_notifications?select=*&order=created_at.desc';
$dynamic_notifications = querySupabaseEndpoint($notif_url, $supabase_key, $auth_header);

$unread_count = 0;
foreach ($dynamic_notifications as $notification) {
    if (isset($notification['is_new']) && $notification['is_new'] == true) {
        $unread_count++;
    }
}

$past_month_date = date('Y-m-d', strtotime('-30 days'));
$activities_url = $supabase_url . "/rest/v1/user_activities?user_id=eq.{$user_id}&created_at=gte.{$past_month_date}&order=created_at.desc&limit=100";
$user_activities_history = querySupabaseEndpoint($activities_url, $supabase_key, $auth_header);

$week_data = ['transportation' => 0, 'office_usage' => 0, 'food_consumption' => 0];
$month_data = ['transportation' => 0, 'office_usage' => 0, 'food_consumption' => 0];
$one_week_ago = strtotime('-7 days');

foreach ($user_activities_history as $activity) {
    $activity_time = isset($activity['created_at']) ? strtotime($activity['created_at']) : time();
    $t = (float)($activity['transportation'] ?? 0);
    $o = (float)($activity['office_usage'] ?? 0);
    $f = (float)($activity['food_consumption'] ?? 0);

    $month_data['transportation'] += $t;
    $month_data['office_usage'] += $o;
    $month_data['food_consumption'] += $f;

    if ($activity_time >= $one_week_ago) {
        $week_data['transportation'] += $t;
        $week_data['office_usage'] += $o;
        $week_data['food_consumption'] += $f;
    }
}

$rankings_url = $supabase_url . "/rest/v1/user_rankings_summary?user_id=eq.{$user_id}&limit=1";
$live_rankings = querySupabaseEndpoint($rankings_url, $supabase_key, $auth_header);

$user_percentile_ranking = $live_rankings[0]['percentile_ranking'] ?? "Calculating...";
$department_rank         = $live_rankings[0]['department_rank'] ?? "N/A";
$campus_rank             = $live_rankings[0]['campus_rank'] ?? "N/A";
$total_campuses          = $live_rankings[0]['total_campuses'] ?? 12;

$dept_chart_url = $supabase_url . '/rest/v1/department_footprints?select=department_code,total_emissions&order=total_emissions.desc';
$live_dept_data = querySupabaseEndpoint($dept_chart_url, $supabase_key, $auth_header);

$departments_data = [];
foreach ($live_dept_data as $row) {
    if (isset($row['department_code'])) {
        $departments_data[$row['department_code']] = $row['total_emissions'];
    }
}

if (empty($departments_data)) {
    $departments_data = ['CABE' => 0, 'CAS' => 0, 'CET' => 0, 'CICS' => 0, 'CTE' => 0];
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);
    $string = array('y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second');
    foreach ($string as $k => &$v) {
        $value = match ($k) {
            'w' => $weeks,
            'd' => $days,
            default => $diff->$k,
        };
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
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; width: 100vw; background-color: #eef1f4; color: #333; overflow: hidden; }

        /* Sidebar Layout */
        .sidebar { width: 260px; background-color: #2D6A4F; color: white; display: flex; flex-direction: column; padding: 20px 0; flex-shrink: 0; height: 100%; }
        .logo-section { display: flex; align-items: center; padding: 10px 25px; margin-bottom: 30px; }
        
        .logo-img-container { width: 45px; height: 45px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.2); flex-shrink: 0; }
        .logo-img-container img { width: 100%; height: 100%; object-fit: cover; }
        .logo-text { font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px; }
        
        .menu-items { flex: 1; display: flex; flex-direction: column; }
        .menu-item { display: flex; align-items: center; padding: 14px 25px; color: #b7e4c7; text-decoration: none; font-size: 0.95rem; font-weight: 500; transition: 0.2s; }
        .menu-item i { margin-right: 15px; width: 20px; text-align: center; font-size: 16px; }
        .menu-item:hover, .menu-item.active { background-color: #40916C; color: white; }
        .menu-separator { height: 1px; background-color: #40916C; margin: 40px 25px 20px 25px; }
        
        /* Main Workspace Content Layout Wrapper */
        .main-workspace { flex: 1; display: flex; flex-direction: column; height: 100%; overflow: hidden; }
        .top-navbar { height: 75px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 40px; flex-shrink: 0; border-bottom: 1px solid #eef1f4; }
        
        .search-box { display: flex; align-items: center; background-color: #ECEFF1; padding: 10px 15px; border-radius: 8px; width: 350px; }
        .search-box i { color: #555; font-size: 14px; }
        .search-box input { border: none; background: transparent; outline: none; margin-left: 10px; width: 100%; font-size: 0.9rem; }
        
        .user-nav-profile { display: flex; align-items: center; gap: 25px; }
        
        /* Notifications */
        .notification-container { position: relative; display: inline-block; }
        .notification-bell { background: #e2f0d9; padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #2D6A4F; font-size: 18px; width: 40px; height: 40px; }
        .notification-bell:hover { background: #d2e7c4; }
        .notification-badge { position: absolute; top: -2px; right: -2px; background-color: #BA181B; color: white; font-size: 10px; font-weight: 700; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 2px solid white; pointer-events: none; }
        
        .notification-dropdown { position: absolute; top: 50px; right: 0; width: 320px; background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid #eef1f4; display: none; z-index: 1000; overflow: hidden; }
        .notification-dropdown.show { display: block; }
        .dropdown-header { padding: 15px; font-weight: 700; font-size: 0.9rem; border-bottom: 1px solid #eef1f4; color: #1E293B; background: #f8fafc; }
        .notification-item { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.8rem; color: #334155; line-height: 1.4; }
        .notification-item:hover { background-color: #f8fafc; }
        .notification-item.unread { background-color: #f0fdf4; border-left: 3px solid #2D6A4F; }
        .notification-item span.time { display: block; font-size: 0.7rem; color: #94a3b8; margin-top: 5px; }
        .no-notifications { padding: 20px; text-align: center; color: #64748B; font-size: 0.85rem; }

        /* Profile Styles */
        .profile-card { display: flex; align-items: center; gap: 12px; border-left: 1px solid #CBD5E1; padding-left: 25px; }
        .avatar-placeholder { width: 40px; height: 40px; background: #e2f0d9; color: #2D6A4F; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .user-info-text h4 { font-size: 0.95rem; color: #1E293B; font-weight: 700; }
        .user-info-text p { font-size: 0.8rem; color: #64748B; }

        /* Dashboard Content Scrolling Region */
        .dashboard-content { padding: 35px; flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 25px; }
        .cards-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .ranking-card { background: white; padding: 25px; border-radius: 12px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid #eef1f4; }
        .ranking-card h3 { font-size: 0.95rem; color: #000; text-align: left; margin-bottom: 10px; font-weight: 700; }
        .card-icon { font-size: 2.8rem; color: #2D6A4F; margin: 15px 0 10px 0; }
        .sub-text { font-size: 0.75rem; color: #52B788; margin-bottom: 5px; font-style: italic; font-weight: 500; }
        .metric-value { font-size: 2.8rem; font-weight: 800; color: #1B4332; margin-bottom: 10px; }
        .desc-text { font-size: 0.75rem; color: #444; line-height: 1.4; padding: 0 5px; }

        /* Charts Layout */
        .charts-row { display: grid; grid-template-columns: 4fr 6fr; gap: 25px; margin-bottom: 20px; }
        .chart-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); border: 1px solid #eef1f4; }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .chart-header h3 { font-size: 0.95rem; color: #000; font-weight: 700; }
        .chart-header select { padding: 5px 10px; border-radius: 6px; border: 1px solid #CBD5E1; font-size: 0.85rem; outline: none; background-color: #fff; cursor: pointer; }
        .chart-container { position: relative; width: 100%; height: 260px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo-section">
            <div class="logo-img-container">
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
            <a href="settings.php" class="menu-item"><i class="fa-solid fa-gear"></i> Settings</a>
            <div class="menu-separator"></div>
            <a href="javascript:void(0);" onclick="confirmLogout();" class="menu-item"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <div class="main-workspace">
        <div class="top-navbar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search CarbonWise">
            </div>
            
            <div class="user-nav-profile">
                <div class="notification-container">
                    <div class="notification-bell" id="bellBtn">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?= $unread_count ?></span>
                    <?php endif; ?>

                    <div class="notification-dropdown" id="notificationMenu">
                        <div class="dropdown-header">System Updates</div>
                        <?php if (empty($dynamic_notifications)): ?>
                            <div class="no-notifications">No new updates at this time.</div>
                        <?php else: ?>
                            <?php foreach ($dynamic_notifications as $notif): ?>
                                <div class="notification-item <?= ($notif['is_new'] ?? false) ? 'unread' : '' ?>">
                                    <?= htmlspecialchars($notif['message']) ?>
                                    <span class="time"><?= time_elapsed_string($notif['created_at']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="avatar-placeholder"><i class="fa-solid fa-user"></i></div>
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
                    <p class="desc-text">Excellent work! You are currently among the <?= htmlspecialchars($user_percentile_ranking) ?> of users with the lowest carbon emissions this week.</p>
                </div>
                <div class="ranking-card">
                    <h3>Department Ranking</h3>
                    <div class="card-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <p class="sub-text">Your department currently ranks</p>
                    <div class="metric-value"><?= htmlspecialchars($department_rank) ?></div>
                    <p class="desc-text">Excellent performance! Your department is currently ranked <?= htmlspecialchars($department_rank) ?> among all departments on your campus.</p>
                </div>
                <div class="ranking-card">
                    <h3>Campus Ranking</h3>
                    <div class="card-icon"><i class="fa-solid fa-building-user"></i></div>
                    <p class="sub-text">Your campus currently ranks</p>
                    <div class="metric-value"><?= htmlspecialchars($campus_rank) ?></div>
                    <p class="desc-text">Outstanding achievement! Your campus ranks <?= htmlspecialchars($campus_rank) ?> out of <?= (int)$total_campuses; ?> Batangas State University campuses for low carbon emissions.</p>
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
                        <select><option>This Week</option><option value="month">This Month</option></select>
                    </div>
                    <div class="chart-container">
                        <canvas id="departmentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmLogout() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to log out of your CarbonWise session?",
                icon: 'warning',
                iconColor: '#F2A654',
                showCancelButton: true,
                confirmButtonColor: '#2D6A4F',
                cancelButtonColor: '#BA181B',
                confirmButtonText: 'Yes, log me out',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false,
                allowEscapeKey: false,
                backdrop: `rgba(0, 0, 0, 0.4)`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
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

        const individualDatasets = {
            week: [
                <?= (float)$week_data['transportation']; ?>, 
                <?= (float)$week_data['office_usage']; ?>, 
                <?= (float)$week_data['food_consumption']; ?>
            ],
            month: [
                <?= (float)$month_data['transportation']; ?>, 
                <?= (float)$month_data['office_usage']; ?>, 
                <?= (float)$month_data['food_consumption']; ?>
            ]
        };

        const ctxIndiv = document.getElementById('individualChart').getContext('2d');
        const individualChart = new Chart(ctxIndiv, {
            type: 'bar',
            data: {
                labels: [''],
                datasets: [
                    { label: 'Transportation', data: [individualDatasets.week[0]], backgroundColor: '#5ea3e3', barPercentage: 0.4, categoryPercentage: 1.0 },
                    { label: 'Office Resource Usage', data: [individualDatasets.week[1]], backgroundColor: '#76d7b6', barPercentage: 0.4, categoryPercentage: 1.0 },
                    { label: 'Food Consumption', data: [individualDatasets.week[2]], backgroundColor: '#a3d995', barPercentage: 0.4, categoryPercentage: 1.0 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { display: true } },
                    x: { stacked: false, grid: { display: false } }
                },
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } }
            }
        });

        document.getElementById('individualTimeframe').addEventListener('change', function() {
            const selectedTimeframe = this.value;
            const dataToLoad = individualDatasets[selectedTimeframe];

            individualChart.data.datasets[0].data = [dataToLoad[0]];
            individualChart.data.datasets[1].data = [dataToLoad[1]];
            individualChart.data.datasets[2].data = [dataToLoad[2]];
            individualChart.update();
        });

        const ctxDept = document.getElementById('departmentChart').getContext('2d');
        new Chart(ctxDept, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($departments_data)); ?>,
                datasets: [{
                    data: <?= json_encode(array_values($departments_data)); ?>,
                    backgroundColor: ['#0074d9', '#52b788', '#8a4215', '#9c6633', '#628cb3'],
                    barThickness: 24
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { display: true } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>
</body>
</html>