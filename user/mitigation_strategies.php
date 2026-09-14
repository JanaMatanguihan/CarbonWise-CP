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
$user_data  = $_SESSION['user_profile'] ?? ($_SESSION['user_data'] ?? []);
$user_id    = $_SESSION['user_id'] ?? ($user_data['id'] ?? null); 
$user_metadata = $user_data['user_metadata'] ?? [];
$user_email = $user_data['email'] ?? ($user_metadata['email'] ?? ($_SESSION['user_email'] ?? ''));

// 3. Resolve display name and user role
$raw_name = $_SESSION['user_name'] ?? ($user_data['full_name'] ?? ($user_metadata['full_name'] ?? ($user_metadata['name'] ?? ($user_data['name'] ?? ''))));
$raw_role = $_SESSION['user_role'] ?? ((isset($user_data['role']) && strtolower($user_data['role']) !== 'authenticated') ? $user_data['role'] : ($user_metadata['role'] ?? ''));

$full_name = !empty($raw_name) ? ucwords(strtolower(trim($raw_name))) : 'Unknown User'; 
$role      = (!empty($raw_role) && strtolower($raw_role) !== 'authenticated') ? ucwords(strtolower(trim($raw_role))) : 'User';

// --- NEON POSTGRESQL CONFIGURATION ---
$db_host     = 'ep-red-hill-a5erg1sb-pooler.us-east-2.aws.neon.tech';
$endpoint_id = 'ep-red-hill-a5erg1sb-pooler'; 
$db_port     = '5432';
$db_name     = 'neondb';
$db_user     = 'neondb_owner'; 
$db_pass     = 'npg_B7h4oEQbqJdG'; 

$avatar_url = $user_data['avatar_url'] ?? ($user_metadata['avatar_url'] ?? null);

// Connect to Neon Database and fetch profile_picture from the users table
try {
    $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name};sslmode=require;options='endpoint={$endpoint_id}'";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

    if ($user_id) {
        $stmt_u = $pdo->prepare("SELECT profile_picture FROM users WHERE id = :id LIMIT 1");
        $stmt_u->execute([':id' => $user_id]);
        $u_info = $stmt_u->fetch();
        if ($u_info && !empty($u_info['profile_picture'])) {
            $avatar_url = $u_info['profile_picture'];
        }
    }
} catch (PDOException $e) {
    // Database Connection Error Fallback
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

// --- STATIC/SESSION EMISSIONS DATA ---
$total_transport = 0.0;
$total_electricity = 0.0;
$total_food = 0.0;
$grand_total = $total_transport + $total_electricity + $total_food;
$highest_emission_category = 'None';
$emission_tier = 'Low Impact (Eco-Friendly)';

// --- DYNAMIC STRATEGY ENGINE (DYNAMIC GEMINI API INTEGRATION) ---
$gemini_api_key = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
$ai_insight_summary = "";
$strategies = [];
$api_success = false;

if (!empty($gemini_api_key)) {
    $prompt = "You are a real-time environmental analysis engine for CarbonWise.
    Analyze these EXACT 60-day metrics logged by user {$full_name}:
    - Transport: {$total_transport} kg CO2
    - Electricity/Resource: {$total_electricity} kg CO2
    - Food: {$total_food} kg CO2
    - Combined Total: {$grand_total} kg CO2 ({$emission_tier})

    Generate a highly customized JSON response containing a tailored 'insight_summary' paragraph and an array of 'strategies'. 
    - Provide actionable recommendations ONLY for categories where the user has logged emissions > 0.
    - If ALL categories are 0, return an empty strategies array [].

    Return the response strictly as valid, raw JSON matching this schema format. Do not use markdown backticks:
    {
      \"insight_summary\": \"string\",
      \"strategies\": [
        {
          \"title\": \"string\",
          \"description\": \"string\",
          \"category\": \"Transport|Office Resource Usage|Food Consumption\",
          \"frequency\": \"Daily|Weekly\",
          \"impact\": float,
          \"unit\": \"string\",
          \"icon\": \"string\",
          \"priority\": boolean
        }
      ]
    }";

    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $gemini_api_key;
    $post_data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ], 
        "generationConfig" => [
            "responseMimeType" => "application/json"
        ]
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12); 
    
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $response_arr = json_decode($response, true);
        $raw_text = trim($response_arr['candidates'][0]['content']['parts'][0]['text'] ?? '');
        $clean_json_str = trim(preg_replace('/^```json\s*|\s*```$/', '', $raw_text));
        $clean_json = json_decode($clean_json_str, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($clean_json['strategies'])) {
            $ai_insight_summary = $clean_json['insight_summary'];
            $strategies = $clean_json['strategies'];
            $api_success = true;
        }
    }
}

// --- DYNAMIC ADAPTIVE STRATEGIES (STATIC TELEMETRY FALLBACK) ---
if (!$api_success) {
    if ($grand_total > 0) {
        $ai_insight_summary = "Based on your recent activity logs totaling <strong>" . number_format($grand_total, 1) . " kg CO2</strong>, your highest impact area is currently in <strong>{$highest_emission_category}</strong>. Review the targeted reduction strategies below to optimize your environmental footprint.";
        
        if ($total_transport > 0) {
            $strategies[] = [
                'title' => 'Optimize Transport Logistics',
                'description' => 'Targeting your logged ' . number_format($total_transport, 1) . ' kg CO2 transport footprint via public transits or carpooling.',
                'category' => 'Transport',
                'frequency' => 'Weekly',
                'impact' => round($total_transport * 0.15, 1),
                'unit' => 'kg CO2 / week',
                'icon' => '🚗',
                'priority' => ($highest_emission_category === 'Transport')
            ];
        }
        if ($total_electricity > 0) {
            $strategies[] = [
                'title' => 'Curtail Idle Power Consumption',
                'description' => 'Targeting your logged ' . number_format($total_electricity, 1) . ' kg CO2 resource profile by shutting off unused electronics.',
                'category' => 'Office Resource Usage',
                'frequency' => 'Daily',
                'impact' => round($total_electricity * 0.20, 1),
                'unit' => 'kg CO2 / day',
                'icon' => '⚡',
                'priority' => ($highest_emission_category === 'Office Resource Usage')
            ];
        }
        if ($total_food > 0) {
            $strategies[] = [
                'title' => 'Sustainable Diet Adjustments',
                'description' => 'Transitioning your logged ' . number_format($total_food, 1) . ' kg CO2 dietary footprint toward eco-friendly plant choices.',
                'category' => 'Food Consumption',
                'frequency' => 'Daily',
                'impact' => round($total_food * 0.12, 1),
                'unit' => 'kg CO2 / day',
                'icon' => '🥗',
                'priority' => ($highest_emission_category === 'Food Consumption')
            ];
        }
    } else {
        $ai_insight_summary = "Welcome to CarbonWise! We couldn't find any historical activity records in your logs for the last 60 days. Start logging your daily activities to receive personalized carbon reduction pathways.";
        $strategies = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise - Mitigation Strategies</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --banner-insights: #E2F0D9;
            --banner-text: #1B4332;
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
            --banner-insights: #162E24;
            --banner-text: #52B788;
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
        .sidebar-footer { margin-top: auto; display: flex; flex-direction: column; }
        .sidebar-divider { height: 1px; background-color: var(--sidebar-divider); margin: 10px 25px; }

        .main-workspace { flex: 1; display: flex; flex-direction: column; height: 100%; overflow: hidden; }
        .top-navbar { height: 75px; background: var(--bg-card); display: flex; align-items: center; justify-content: space-between; padding: 0 40px; flex-shrink: 0; border-bottom: 1px solid var(--border-color); }
        
        .header-title-area h2 { font-size: 1.4rem; font-weight: 700; color: var(--text-main); margin-bottom: 2px; }
        .header-title-area p { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }

        .user-nav-profile { display: flex; align-items: center; gap: 25px; }
        .profile-card { display: flex; align-items: center; gap: 12px; border-left: 1px solid var(--border-color); padding-left: 25px; }
        .avatar-circle-nav { width: 40px; height: 40px; background: var(--input-bg); color: var(--accent-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; overflow: hidden; border: 1px solid var(--accent-green); }
        .avatar-circle-nav img { width: 100%; height: 100%; object-fit: cover; }
        .user-info-text h4 { font-size: 0.95rem; color: var(--text-main); font-weight: 700; }
        .user-info-text p { font-size: 0.8rem; color: var(--text-muted); }

        .strategies-content { padding: 35px; flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 25px; }
        
        .insights-action-banner { background-color: var(--banner-insights); color: var(--banner-text); padding: 20px; border-radius: 12px; border-left: 5px solid var(--accent-green); display: flex; align-items: center; gap: 20px; }
        .insights-action-banner i { font-size: 28px; }
        .insights-content-block h4 { font-size: 1.05rem; font-weight: 700; margin-bottom: 4px; }
        .insights-content-block p { font-size: 0.88rem; font-weight: 500; opacity: 0.9; line-height: 1.4; }

        .strategies-card { background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden; }
        
        .toolbar { display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid var(--border-color); gap: 15px; background-color: var(--input-bg); }
        .table-search { display: flex; align-items: center; border: 1px solid var(--input-border); border-radius: 6px; padding: 8px 12px; width: 250px; background: var(--bg-card); }
        .table-search input { border: none; outline: none; margin-left: 8px; width: 100%; font-size: 0.88rem; background: transparent; color: var(--text-main); }
        
        .filters-group { display: flex; align-items: center; gap: 12px; }
        .filters-group select { padding: 8px 16px; border-radius: 6px; border: 1px solid var(--input-border); background-color: var(--bg-card); color: var(--text-main); font-size: 0.88rem; cursor: pointer; outline: none; }

        .strategies-table { width: 100%; border-collapse: collapse; text-align: left; }
        .strategies-table th { font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); padding: 14px 20px; border-bottom: 1px solid var(--border-color); font-weight: 600; background: var(--input-bg); }
        .strategies-table td { padding: 18px 20px; border-bottom: 1px solid var(--border-color); background: var(--bg-card); }
        
        .strategy-info-cell { display: flex; align-items: center; gap: 15px; }
        .strategy-icon { width: 40px; height: 40px; border-radius: 50%; background-color: var(--input-bg); display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .strategy-details .title { font-weight: 700; font-size: 0.95rem; color: var(--text-main); }
        .strategy-details .description { font-size: 0.83rem; color: var(--text-muted); margin-top: 2px; }

        .badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background-color: #d8f3dc; color: #1b4332; }
        .badge.frequency-badge { background-color: #e1f0fe; color: #1e62a3; }
        .badge.priority-badge { background-color: #ffddd2; color: #e63946; border: 1px dashed #e63946; margin-left: 8px; font-size: 0.72rem; padding: 2px 8px; }
        .impact-value { font-size: 0.95rem; font-weight: 800; color: var(--text-main); }

        .notification-container { position: relative; display: inline-block; }
        .notification-bell { background: var(--bell-bg); padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--accent-green); font-size: 18px; width: 40px; height: 40px; }
        .notification-bell:hover { opacity: 0.85; }
        .notification-badge { position: absolute; top: -2px; right: -2px; background-color: #BA181B; color: white; font-size: 10px; font-weight: 700; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 2px solid var(--bg-card); pointer-events: none; }
        .notification-dropdown { position: absolute; top: 50px; right: 0; width: 320px; background: var(--bg-card); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid var(--border-color); display: none; z-index: 1000; overflow: hidden; }
        .notification-dropdown.show { display: block; }
        .dropdown-header { padding: 15px; font-weight: 700; font-size: 0.9rem; border-bottom: 1px solid var(--border-color); color: var(--text-main); background: var(--input-bg); }
        #notificationList { max-height: 280px; overflow-y: auto; }
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
            <a href="reports.php" class="menu-item"><i class="fa-solid fa-chart-simple"></i> Reports</a>
            <a href="mitigation_strategies.php" class="menu-item active"><i class="fa-solid fa-lightbulb"></i> Mitigation Strategies</a>
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
                <h2>Mitigation Strategies</h2>
                <p>Discover actionable reduction blueprints</p>
            </div>
            <div class="user-nav-profile">
                <div class="notification-container">
                    <div class="notification-bell" id="bellBtn">
                        <i class="fa-regular fa-bell"></i>
                    </div>
                    <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>

                    <div class="notification-dropdown" id="notificationMenu">
                        <div class="dropdown-header">Notifications</div>
                        <div id="notificationList">
                            <div style="padding: 20px; text-align: center; color: var(--text-muted);">Loading updates...</div>
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

        <main class="strategies-content">
            
            <div class="insights-action-banner">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <div class="insights-content-block">
                    <h4>Sustainability Insights for <?= explode(' ', htmlspecialchars($full_name))[0] ?></h4>
                    <p><?= $ai_insight_summary ?></p>
                </div>
            </div>

            <div class="strategies-card">
                <div class="toolbar">
                    <div class="table-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="searchStrategy" placeholder="Search strategies..." oninput="filterTable()">
                    </div>
                    <div class="filters-group">
                        <select id="categoryFilter" onchange="filterTable()">
                            <option value="all">All Categories</option>
                            <option value="Food Consumption">Food Consumption</option>
                            <option value="Office Resource Usage">Office Resource Usage</option>
                            <option value="Transport">Transport</option>
                        </select>
                    </div>
                </div>

                <table class="strategies-table">
                    <thead>
                        <tr>
                            <th>Strategy</th>
                            <th>Category</th>
                            <th>Frequency</th>
                            <th>Impact Metric</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if (!empty($strategies)): ?>
                            <?php foreach ($strategies as $strategy): ?>
                                <tr class="strategy-row" data-category="<?= htmlspecialchars($strategy['category']) ?>" data-title="<?= strtolower(htmlspecialchars($strategy['title'])) ?>">
                                    <td>
                                        <div class="strategy-info-cell">
                                            <div class="strategy-icon"><?= htmlspecialchars($strategy['icon'] ?? '📊') ?></div>
                                            <div class="strategy-details">
                                                <div class="title">
                                                    <?= htmlspecialchars($strategy['title']) ?>
                                                    <?php if(!empty($strategy['priority'])): ?>
                                                        <span class="badge priority-badge"><i class="fa-solid fa-star"></i> Priority</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="description"><?= htmlspecialchars($strategy['description']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge"><?= htmlspecialchars($strategy['category']) ?></span></td>
                                    <td><span class="badge frequency-badge"><?= htmlspecialchars($strategy['frequency']) ?></span></td>
                                    <td><span class="impact-value"><?= htmlspecialchars($strategy['impact']) ?> <?= htmlspecialchars($strategy['unit']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px; color: var(--text-muted);">
                                    No mitigation strategies available. Please log your activities in Activity Input to see customized insights.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
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

        function filterTable() {
            const cat = document.getElementById('categoryFilter').value;
            const q = document.getElementById('searchStrategy').value.toLowerCase();
            document.querySelectorAll('.strategy-row').forEach(row => {
                const matchesCat = cat === 'all' || row.getAttribute('data-category') === cat;
                const matchesQ = row.getAttribute('data-title').includes(q);
                row.style.display = matchesCat && matchesQ ? '' : 'none';
            });
        }

        function confirmLogout() {
            let activeTheme = localStorage.getItem('theme') || 'light';
            let popupBg = activeTheme === 'dark' ? '#121A16' : '#ffffff';
            let popupText = activeTheme === 'dark' ? '#F3F4F6' : '#333333';

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
                backdrop: `rgba(0, 0, 0, 0.4)`
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Logging out...',
                        text: 'Please wait a moment.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    window.location.href = 'logout.php?t=' + new Date().getTime();
                }
            });
        }

        document.addEventListener("DOMContentLoaded", function() {
            const bellBtn = document.getElementById('bellBtn');
            const notificationMenu = document.getElementById('notificationMenu');
            const badge = document.getElementById('notificationBadge');
            const list = document.getElementById('notificationList');

            if (bellBtn && notificationMenu) {
                bellBtn.addEventListener('click', (e) => { 
                    e.stopPropagation(); 
                    notificationMenu.classList.toggle('show'); 
                });
                document.addEventListener('click', () => notificationMenu.classList.remove('show'));
            }

            function loadNotifications() {
                if(!list || !badge) return;
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
                            <div class="notif-item" data-id="${n.id}" style="padding: 12px 15px; border-bottom: 1px solid var(--border-color); background: ${n.is_read ? 'transparent' : 'var(--input-bg)'}; cursor: pointer;">
                                <span style="font-weight: 700; color: var(--text-main);">${n.title}</span>
                                <p style="margin: 0; color: var(--text-muted); font-size: 0.8rem;">${n.message}</p>
                            </div>
                        `).join('');
                    }).catch(() => {
                        list.innerHTML = `<div style="padding: 20px; text-align: center; color: var(--text-muted);">Error loading notifications</div>`;
                    });
            }

            loadNotifications();
            setInterval(loadNotifications, 60000);
        });
    </script>
</body>
</html>