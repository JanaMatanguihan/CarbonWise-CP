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

$raw_name = $user_data['full_name'] ?? ($user_metadata['full_name'] ?? ($user_metadata['name'] ?? ''));
if (isset($user_data['role']) && strtolower($user_data['role']) !== 'authenticated') {
    $raw_role = $user_data['role'];
} else {
    $raw_role = $user_metadata['role'] ?? '';
}

$full_name = !empty($raw_name) ? ucwords(strtolower(trim($raw_name))) : 'Unknown User'; 
$role      = (!empty($raw_role) && strtolower($raw_role) !== 'authenticated') ? ucwords(strtolower(trim($raw_role))) : 'Student'; 

// --- SUPABASE CREDENTIALS ---
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

// --- FETCH ALL USER ACTIVITIES (Unified for counters, graphs, and points calculation) ---
$historical_date = date('Y-m-d', strtotime('-60 days'));
$activities_url = $supabase_url . "/rest/v1/user_activities?user_id=eq.{$user_id}&created_at=gte.{$historical_date}&order=created_at.asc";
$activities = querySupabaseEndpoint($activities_url, $supabase_key, $auth_header);

// Containers for calculation metrics
$total_emissions = 0.0;
$this_week_total = 0.0;
$this_month_total = 0.0;
$last_month_total = 0.0;

$transport_total = 0.0;
$office_total = 0.0;
$food_total = 0.0;
$devices_total = 0.0; 

// Green Points Counter matching activity_input logic
$total_green_points = 0;

$seven_days_ago = strtotime('-7 days');
$thirty_days_ago = strtotime('-30 days');
$sixty_days_ago = strtotime('-60 days');

// Chart data arrays initialized with days of the current week (Mon-Sun)
$days_of_week = ['Mon' => 0, 'Tue' => 0, 'Wed' => 0, 'Thu' => 0, 'Fri' => 0, 'Sat' => 0, 'Sun' => 0];

foreach ($activities as $row) {
    $t = (float)($row['transportation'] ?? 0);
    $o = (float)($row['office_usage'] ?? 0);
    $f = (float)($row['food_consumption'] ?? 0);
    $row_total = $t + $o + $f;
    
    $total_emissions += $row_total;
    $created_at = isset($row['created_at']) ? strtotime($row['created_at']) : time();
    
    // Categorization Breakdown by source
    $transport_total += $t;
    $office_total += $o;
    $food_total += $f;
    
    // Unchanged Activity-Input Threshold Rules for Points Calculation
    if ($t > 0 && $t < 2.0)  $total_green_points += 15; 
    if ($o > 0 && $o < 1.0)  $total_green_points += 10; 
    if ($f > 0 && $f < 1.5)  $total_green_points += 20; 

    // Date aggregations
    if ($created_at >= $seven_days_ago) {
        $this_week_total += $row_total;
        
        // Map to Line Graph days
        $day_name = date('D', $created_at);
        if (array_key_exists($day_name, $days_of_week)) {
            $days_of_week[$day_name] += $row_total;
        }
    }
    
    if ($created_at >= $thirty_days_ago) {
        $this_month_total += $row_total;
    } elseif ($created_at >= $sixty_days_ago && $created_at < $thirty_days_ago) {
        $last_month_total += $row_total;
    }
}

// Normalize points to a scale of 100 for tracker visual rendering
$max_goal_points = 500; 
$progress_percentage = min(100, round(($total_green_points / $max_goal_points) * 100));

// Calculate Month-over-Month Percentages changes
$mom_change_text = "0% since last month";
$mom_percentage = 0;
$mom_direction = "up";

if ($last_month_total > 0) {
    $mom_percentage = round((($this_month_total - $last_month_total) / $last_month_total) * 100);
    if ($mom_percentage >= 0) {
        $mom_change_text = "+" . $mom_percentage . "% since last month";
        $mom_direction = "up";
    } else {
        $mom_change_text = $mom_percentage . "% emissions";
        $mom_direction = "down";
    }
} else if ($this_month_total > 0) {
    $mom_change_text = "+100% since last month";
    $mom_direction = "up";
}

// Source percentages logic for Donut chart context
$source_sum = $transport_total + $office_total + $food_total;
$p_transport = $source_sum > 0 ? round(($transport_total / $source_sum) * 100) : 0;
$p_office = $source_sum > 0 ? round(($office_total / $source_sum) * 100) : 0;
$p_food = $source_sum > 0 ? round(($food_total / $source_sum) * 100) : 0;
$p_devices = 0; 
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
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: #eef1f4; color: #333; overflow: hidden; }

        /* Sidebar Structure Styles */
        .sidebar { width: 260px; background-color: #2D6A4F; color: white; display: flex; flex-direction: column; padding: 20px 0; z-index: 101; }
        .logo-section { display: flex; align-items: center; padding: 10px 25px; margin-bottom: 30px; }
        .logo-img-container { width: 45px; height: 45px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; overflow: hidden; }
        .logo-img-container img { width: 100%; height: 100%; object-fit: cover; }
        .logo-text { font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px; }
        
        .menu-items { flex: 1; display: flex; flex-direction: column; }
        .menu-item { display: flex; align-items: center; padding: 14px 25px; color: #b7e4c7; text-decoration: none; font-size: 0.95rem; font-weight: 500; transition: 0.2s; }
        .menu-item i { margin-right: 15px; width: 20px; text-align: center; font-size: 16px; }
        .menu-item:hover, .menu-item.active { background-color: #40916C; color: white; }
        .menu-separator { height: 1px; background-color: #40916C; margin: 40px 25px 20px 25px; }

        /* Work Environment Base Frame */
        .main-workspace { flex: 1; display: flex; flex-direction: column; overflow-y: auto; position: relative; }
        
        /* Sticky Top Navbar Configuration */
        .top-navbar { height: 75px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 40px; flex-shrink: 0; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #e2e8f0; }
        .search-box { display: flex; align-items: center; background-color: #ECEFF1; padding: 10px 15px; border-radius: 8px; width: 350px; }
        .search-box input { border: none; background: transparent; outline: none; margin-left: 10px; width: 100%; font-size: 0.9rem; }
        .user-nav-profile { display: flex; align-items: center; gap: 25px; }
        .profile-card { display: flex; align-items: center; gap: 12px; border-left: 1px solid #CBD5E1; padding-left: 25px; }
        .avatar-placeholder { width: 40px; height: 40px; background: #e2f0d9; color: #2D6A4F; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .user-info-text h4 { font-size: 0.95rem; color: #1E293B; font-weight: 700; }
        .user-info-text p { font-size: 0.8rem; color: #64748B; }
        
        /* Sticky Green Progress Ribbon Style */
        .progress-ribbon-container { background-color: #f1f5f9; padding: 15px 40px; border-bottom: 1px solid #e2e8f0; text-align: center; position: sticky; top: 75px; z-index: 99; }
        .progress-bar-wrapper { width: 100%; max-width: 800px; background-color: #cbd5e1; height: 14px; border-radius: 10px; margin: 0 auto 6px auto; overflow: hidden; position: relative; }
        .progress-bar-fill { background-color: #2D6A4F; height: 100%; width: <?= $progress_percentage ?>%; border-radius: 10px; transition: width 0.5s ease; }
        .progress-ribbon-container h5 { font-size: 0.85rem; font-weight: 700; color: #334155; font-style: italic; }

        /* Document Metric Summary Quadrants */
        .reports-content { padding: 30px; display: flex; flex-direction: column; gap: 25px; }
        .metrics-quad-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .metric-quad-card { background: white; border-radius: 12px; padding: 22px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01); display: flex; flex-direction: column; justify-content: space-between; }
        .metric-quad-card h4 { font-size: 0.85rem; color: #475569; font-weight: 600; margin-bottom: 10px; text-transform: capitalize; }
        .metric-quad-card .main-value-group { display: flex; align-items: baseline; gap: 6px; }
        .metric-quad-card .value { font-size: 2.2rem; font-weight: 800; color: #0f172a; }
        .metric-quad-card .unit { font-size: 0.85rem; font-weight: 600; color: #64748b; }
        .metric-quad-card .sub-label { font-size: 0.75rem; font-weight: 500; color: #94a3b8; margin-top: 5px; }
        .metric-quad-card .trend-pct { font-size: 0.8rem; font-weight: 700; margin-top: 8px; }
        .trend-pct.up { color: #BA181B; }
        .trend-pct.down { color: #2D6A4F; }

        /* Report Segment Layout Configurations */
        .charts-split-grid { display: grid; grid-template-columns: 6fr 4fr; gap: 20px; }
        .report-chart-card { background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; }
        .chart-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .chart-card-header h3 { font-size: 0.95rem; font-weight: 700; color: #1e293b; }
        .chart-card-header select { padding: 4px 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.8rem; outline: none; background: #fff; cursor: pointer; }
        .canvas-area { position: relative; width: 100%; height: 240px; }

        /* Suggestions and Progress Columns */
        .bottom-split-grid { display: grid; grid-template-columns: 7fr 3fr; gap: 20px; }
        .suggestions-card { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; }
        .suggestions-card h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 18px; }
        .suggestion-item-row { display: flex; align-items: center; justify-content: space-between; border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; margin-bottom: 12px; transition: transform 0.2s; cursor: pointer; }
        .suggestion-item-row:hover { transform: translateY(-2px); background-color: #f8fafc; }
        .suggestion-left { display: flex; align-items: center; gap: 15px; }
        .suggestion-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .icon-blue { background-color: #eff6ff; color: #2563eb; }
        .icon-yellow { background-color: #fefce8; color: #ca8a04; }
        .icon-red { background-color: #fff5f5; color: #e53e3e; }
        .suggestion-text h5 { font-size: 0.88rem; font-weight: 700; color: #1e293b; }
        .suggestion-text p { font-size: 0.75rem; color: #64748b; margin-top: 2px; }
        .suggestions-card .view-more-link { display: block; text-align: center; margin-top: 15px; font-size: 0.85rem; color: #2D6A4F; font-weight: 700; text-decoration: none; }

        /* Journey Progression Module */
        .journey-card { background: white; border-radius: 12px; padding: 22px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; align-items: center; text-align: center; justify-content: space-between; }
        .journey-card h3 { font-size: 0.9rem; font-weight: 700; color: #1e293b; width: 100%; margin-bottom: 10px; }
        .radial-progress-container { position: relative; width: 130px; height: 130px; display: flex; align-items: center; justify-content: center; margin: 10px 0; }
        .radial-percentage { font-size: 1.8rem; font-weight: 800; color: #2D6A4F; position: absolute; }
        .journey-card p.status-brief { font-size: 0.78rem; font-weight: 600; color: #475569; padding: 0 5px; }
        .target-footer-metric { width: 100%; text-align: left; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 12px; }
        .target-footer-metric p { font-size: 0.7rem; font-weight: 700; color: #64748b; margin-bottom: 4px; }
        .mini-progress-track { background-color: #e2e8f0; height: 6px; border-radius: 4px; overflow: hidden; }
        .mini-progress-fill { background-color: #2D6A4F; height: 100%; width: 45%; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo-section">
            <div class="logo-img-container">
                <img src="logo.png" alt="CarbonWise Brand Logo">
            </div>
            <span class="logo-text">CARBONWISE</span>
        </div>
        <div class="menu-items">
            <a href="dashboard.php" class="menu-item"><i class="fa-solid fa-border-all"></i> Dashboard</a>
            <a href="activity_input.php" class="menu-item"><i class="fa-solid fa-pen-to-square"></i> Activity Input</a>
            <a href="reports.php" class="menu-item active"><i class="fa-solid fa-chart-simple"></i> Reports</a>
            <a href="mitigation_strategies.php" class="menu-item"><i class="fa-solid fa-lightbulb"></i> Mitigation Strategies</a>
            <a href="profile.php" class="menu-item"><i class="fa-solid fa-circle-user"></i> View Profile</a>
            <a href="settings.php" class="menu-item"><i class="fa-solid fa-gear"></i> Settings</a>
            <div class="menu-separator"></div>
            <a href="logout.php?action=logout" class="menu-item"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
        </div>
    </div>

    <div class="main-workspace">
        <div class="top-navbar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search CarbonWise">
            </div>
            <div class="user-nav-profile">
                <div class="profile-card">
                    <div class="avatar-placeholder"><i class="fa-solid fa-user"></i></div>
                    <div class="user-info-text">
                        <h4><?= htmlspecialchars($full_name) ?></h4>
                        <p><?= htmlspecialchars($role) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="progress-ribbon-container">
            <div class="progress-bar-wrapper">
                <div class="progress-bar-fill"></div>
            </div>
            <h5>Green Points (<?= $total_green_points ?> / <?= $max_goal_points ?> pts)</h5>
        </div>

        <div class="reports-content">
            
            <div class="metrics-quad-row">
                <div class="metric-quad-card">
                    <h4>Total CO2 Emissions</h4>
                    <div class="main-value-group">
                        <span class="value"><?= number_format($total_emissions, 0) ?></span>
                        <span class="unit">kg CO2</span>
                    </div>
                    <span class="trend-pct <?= $mom_direction ?>"><?= htmlspecialchars($mom_change_text) ?></span>
                </div>
                <div class="metric-quad-card">
                    <h4>This Week</h4>
                    <div class="main-value-group">
                        <span class="value"><?= number_format($this_week_total, 0) ?></span>
                        <span class="unit">kg CO2</span>
                    </div>
                    <span class="sub-label">Aggregated past 7 days</span>
                </div>
                <div class="metric-quad-card">
                    <h4>This Month</h4>
                    <div class="main-value-group">
                        <span class="value"><?= number_format($this_month_total, 0) ?></span>
                        <span class="unit">kg CO2</span>
                    </div>
                    <span class="sub-label">Current billing window</span>
                </div>
                <div class="metric-quad-card">
                    <h4>Your Emissions Last Month</h4>
                    <div class="main-value-group">
                        <span class="value"><?= $last_month_total > 0 ? '-' : '' ?><?= number_format(abs($mom_percentage), 0) ?>%</span>
                    </div>
                    <span class="sub-label">emissions trajectory change</span>
                </div>
            </div>

            <div class="charts-split-grid">
                <div class="report-chart-card">
                    <div class="chart-card-header">
                        <h3>Emissions Over Time</h3>
                        <select><option>This Week</option></select>
                    </div>
                    <div class="canvas-area">
                        <canvas id="emissionsOverTimeChart"></canvas>
                    </div>
                </div>
                <div class="report-chart-card">
                    <div class="chart-card-header">
                        <h3>Emissions by Source</h3>
                        <select><option>This Week</option></select>
                    </div>
                    <div class="canvas-area">
                        <canvas id="emissionsBySourceChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bottom-split-grid">
                <div class="suggestions-card">
                    <h3>Smart Suggestions</h3>
                    
                    <div class="suggestion-item-row">
                        <div class="suggestion-left">
                            <div class="suggestion-icon icon-blue"><i class="fa-solid fa-bus"></i></div>
                            <div class="suggestion-text">
                                <h5>Use public transport 2x this week.</h5>
                                <p>You can save up to 4.3 kg CO2.</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="color:#cbd5e1; font-size: 14px;"></i>
                    </div>

                    <div class="suggestion-item-row">
                        <div class="suggestion-left">
                            <div class="suggestion-icon icon-yellow"><i class="fa-solid fa-lightbulb"></i></div>
                            <div class="suggestion-text">
                                <h5>Turn off lights and electric fans when not in use.</h5>
                                <p>You can save up to ~0.5 kg CO2/day.</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="color:#cbd5e1; font-size: 14px;"></i>
                    </div>

                    <div class="suggestion-item-row">
                        <div class="suggestion-left">
                            <div class="suggestion-icon icon-red"><i class="fa-solid fa-utensils"></i></div>
                            <div class="suggestion-text">
                                <h5>Choose more plant-based meals this week.</h5>
                                <p>Slashes individual agriculture overhead metrics.</p>
                            </div>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="color:#cbd5e1; font-size: 14px;"></i>
                    </div>

                    <a href="#" class="view-more-link">View More Suggestions</a>
                </div>

                <div class="journey-card">
                    <h3>Carbon Reduction Journey</h3>
                    <div class="radial-progress-container">
                        <svg width="130" height="130" viewBox="0 0 130 130" style="transform: rotate(-90deg);">
                            <circle cx="65" cy="65" r="54" stroke="#e2e8f0" stroke-width="10" fill="transparent" />
                            <circle cx="65" cy="65" r="54" stroke="#2D6A4F" stroke-width="10" fill="transparent" 
                                    stroke-dasharray="339.3" stroke-dashoffset="118.7" stroke-linecap="round"/>
                        </svg>
                        <span class="radial-percentage">65%</span>
                    </div>
                    <p class="status-brief">Great job! You're on track to reduce emissions.</p>
                    <div class="target-footer-metric">
                        <p>Your Goal: Reduce 200 kg CO2 this month.</p>
                        <div class="mini-progress-track">
                            <div class="mini-progress-fill"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Line Chart Configuration - Emissions Over Time
        const ctxTime = document.getElementById('emissionsOverTimeChart').getContext('2d');
        new Chart(ctxTime, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Emissions (kg CO2)',
                    data: [
                        <?= (float)$days_of_week['Mon'] ?>,
                        <?= (float)$days_of_week['Tue'] ?>,
                        <?= (float)$days_of_week['Wed'] ?>,
                        <?= (float)$days_of_week['Thu'] ?>,
                        <?= (float)$days_of_week['Fri'] ?>,
                        <?= (float)$days_of_week['Sat'] ?>,
                        <?= (float)$days_of_week['Sun'] ?>
                    ],
                    borderColor: '#2D6A4F',
                    backgroundColor: 'rgba(45, 106, 79, 0.05)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#2D6A4F',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });

        // Donut Chart Configuration - Emissions By Source
        const ctxSource = document.getElementById('emissionsBySourceChart').getContext('2d');
        new Chart(ctxSource, {
            type: 'doughnut',
            data: {
                labels: ['Transport', 'Electricity', 'Food', 'Devices'],
                datasets: [{
                    data: [
                        <?= $p_transport == 0 && $p_office == 0 && $p_food == 0 ? '45, 30, 15, 10' : "$p_transport, $p_office, $p_food, $p_devices" ?>
                    ],
                    backgroundColor: ['#1d3557', '#e9c46a', '#52b788', '#457b9d'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 12, padding: 15, font: { size: 11, weight: '500' } }
                    }
                }
            }
        });
    </script>
</body>
</html>