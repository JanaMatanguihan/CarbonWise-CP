<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Guard check: If the user isn't logged in, send them back to login
if (!isset($_SESSION['user_token'])) {
    header('Location: login.php');
    exit;
}

// 2. Extract session variables safely for the layout
$user_data = $_SESSION['user_data'] ?? [];
$raw_name = $user_data['full_name'] ?? ($user_metadata['full_name'] ?? ($user_metadata['name'] ?? 'Angel Mae Prado'));
if (isset($user_data['role']) && strtolower($user_data['role']) !== 'authenticated') {
    $raw_role = $user_data['role'];
} else {
    $raw_role = $user_metadata['role'] ?? 'Student';
}

$full_name = !empty($raw_name) ? ucwords(strtolower(trim($raw_name))) : 'Unknown User'; 
$role      = (!empty($raw_role) && strtolower($raw_role) !== 'authenticated') ? ucwords(strtolower(trim($raw_role))) : 'Student'; 

// Complete array of dataset mimicking possible needs and matching your application's architecture
$strategies = [
    [
        'title' => 'Promote Plant-Rich Meals',
        'description' => 'Encourage plant-based meal options in campus cafeterias and events.',
        'category' => 'Food Consumption',
        'frequency' => 'Daily',
        'impact' => 30,
        'unit' => 'tCO2e / day',
        'icon' => '🥗'
    ],
    [
        'title' => 'Print Less, Think Twice',
        'description' => 'Reduce unnecessary printing and encourage digital document sharing.',
        'category' => 'Office Resource Usage',
        'frequency' => 'Weekly',
        'impact' => 15,
        'unit' => 'tCO2e / week',
        'icon' => '🖨️'
    ],
    [
        'title' => 'Bike to Campus',
        'description' => 'Encourage cycling by improving bike racks.',
        'category' => 'Transport',
        'frequency' => 'Daily',
        'impact' => 25,
        'unit' => 'tCO2e / day',
        'icon' => '🚲'
    ],
    [
        'title' => 'Switch Off, Save More',
        'description' => 'Turn off lights, AC, and electronics when not in use.',
        'category' => 'Office Resource Usage',
        'frequency' => 'Daily',
        'impact' => 18,
        'unit' => 'tCO2e / day',
        'icon' => '💡'
    ],
    [
        'title' => 'Use Public Transport',
        'description' => 'Encourage the use of public transport through commuter benefits and awareness.',
        'category' => 'Transport',
        'frequency' => 'Weekly',
        'impact' => 40,
        'unit' => 'tCO2e / week',
        'icon' => '🚌'
    ],
    [
        'title' => 'LED Lighting Retrofit',
        'description' => 'Replace older fluorescent campus bulbs with energy-efficient smart LED setups.',
        'category' => 'Energy Efficiency',
        'frequency' => 'Monthly',
        'impact' => 85,
        'unit' => 'tCO2e / month',
        'icon' => '⚡'
    ],
    [
        'title' => 'Compost Cafeteria Scraps',
        'description' => 'Divert organic waste from landfills into functional agricultural grounds compost.',
        'category' => 'Food Consumption',
        'frequency' => 'Daily',
        'impact' => 12,
        'unit' => 'tCO2e / day',
        'icon' => '🍏'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise - Mitigation Strategies</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: #eef1f4; color: #333; overflow: hidden; }

        /* Unified Sidebar Layout Styles from report.php */
        .sidebar { width: 260px; background-color: #2D6A4F; color: white; display: flex; flex-direction: column; padding: 20px 0; z-index: 101; flex-shrink: 0; }
        .logo-section { display: flex; align-items: center; padding: 10px 25px; margin-bottom: 30px; }
        .logo-img-container { width: 45px; height: 45px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; overflow: hidden; }
        .logo-img-container img { width: 100%; height: 100%; object-fit: cover; }
        .logo-text { font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px; }
        
        .menu-items { flex: 1; display: flex; flex-direction: column; }
        .menu-item { display: flex; align-items: center; padding: 14px 25px; color: #b7e4c7; text-decoration: none; font-size: 0.95rem; font-weight: 500; transition: 0.2s; }
        .menu-item i { margin-right: 15px; width: 20px; text-align: center; font-size: 16px; }
        .menu-item:hover, .menu-item.active { background-color: #40916C; color: white; }
        .menu-separator { height: 1px; background-color: #40916C; margin: 40px 25px 20px 25px; }

        /* Main Workspace Structure */
        .main-workspace { flex: 1; display: flex; flex-direction: column; overflow-y: auto; position: relative; }
        
        /* Unified Top Navbar Configuration from report.php */
        .top-navbar { height: 75px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 40px; flex-shrink: 0; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #e2e8f0; }
        .search-box { display: flex; align-items: center; background-color: #ECEFF1; padding: 10px 15px; border-radius: 8px; width: 350px; }
        .search-box input { border: none; background: transparent; outline: none; margin-left: 10px; width: 100%; font-size: 0.9rem; }
        .user-nav-profile { display: flex; align-items: center; gap: 25px; }
        .profile-card { display: flex; align-items: center; gap: 12px; border-left: 1px solid #CBD5E1; padding-left: 25px; }
        .avatar-placeholder { width: 40px; height: 40px; background: #e2f0d9; color: #2D6A4F; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .user-info-text h4 { font-size: 0.95rem; color: #1E293B; font-weight: 700; }
        .user-info-text p { font-size: 0.8rem; color: #64748B; }

        /* Functional Grid Content and Strategy Elements */
        .strategies-content { padding: 40px; display: flex; flex-direction: column; gap: 25px; }
        .content-header h1 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 6px; }
        .content-header .subtitle { font-size: 0.88rem; color: #64748b; }

        .strategies-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01); overflow: hidden; }

        /* Toolbar Filter Row Elements */
        .toolbar { display: flex; justify-content: space-between; align-items: center; padding: 20px; border-bottom: 1px solid #e2e8f0; gap: 15px; background-color: #fafafa; }
        .table-search { display: flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; width: 250px; background: #fff; }
        .table-search i { color: #94a3b8; }
        .table-search input { border: none; outline: none; margin-left: 8px; width: 100%; font-size: 0.88rem; }
        .filters-group { display: flex; align-items: center; gap: 12px; }
        
        .filters-group select {
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background-color: white;
            font-size: 0.88rem;
            color: #334155;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
        }
        .filters-group select:hover, .filters-group select:focus { border-color: #2D6A4F; }
        .sort-label { font-size: 0.88rem; color: #64748b; font-weight: 500; }

        /* Table Components Integration */
        .strategies-table { width: 100%; border-collapse: collapse; text-align: left; }
        .strategies-table th { font-size: 0.75rem; text-transform: uppercase; color: #64748b; padding: 14px 20px; border-bottom: 1px solid #e2e8f0; font-weight: 600; letter-spacing: 0.5px; background: #f8fafc; }
        .strategies-table td { padding: 18px 20px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; background: #fff; }
        
        .strategy-info-cell { display: flex; align-items: center; gap: 15px; }
        .strategy-icon { width: 40px; height: 40px; border-radius: 50%; background-color: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .strategy-details .title { font-weight: 700; font-size: 0.95rem; color: #1e293b; margin-bottom: 4px; }
        .strategy-details .description { font-size: 0.83rem; color: #64748b; line-height: 1.4; }

        .badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background-color: #d8f3dc; color: #1b4332; }
        .badge.frequency-badge { background-color: #e1f0fe; color: #1e62a3; }
        .impact-value { font-size: 0.95rem; font-weight: 800; color: #0f172a; }
        .impact-unit { font-size: 0.75rem; color: #64748b; font-weight: 500; display: block; margin-top: 2px; }

        /* Empty State */
        .no-data-row { display: none; text-align: center; padding: 40px; color: #64748b; font-size: 0.88rem; background: #fff; }

        /* Pagination Layout Footers */
        .pagination-footer { display: flex; justify-content: space-between; align-items: center; padding: 20px; font-size: 0.88rem; color: #64748b; background: #fff; border-top: 1px solid #e2e8f0; }
        .pagination-buttons { display: flex; gap: 5px; }
        .pagination-buttons button { background-color: white; border: 1px solid #cbd5e1; width: 32px; height: 32px; border-radius: 4px; cursor: pointer; transition: 0.2s; }
        .pagination-buttons button:hover { border-color: #2D6A4F; color: #2D6A4F; }
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
            <a href="reports.php" class="menu-item"><i class="fa-solid fa-chart-simple"></i> Reports</a>
            <a href="mitigation_strategies.php" class="menu-item active"><i class="fa-solid fa-lightbulb"></i> Mitigation Strategies</a>
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

        <main class="strategies-content">
            <div class="content-header">
                <h1>Mitigation Strategies</h1>
                <p class="subtitle">Discover and take action on strategies that reduce our university's carbon footprint.</p>
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
                            <option value="Energy Efficiency">Energy Efficiency</option>
                        </select>

                        <select id="frequencyFilter" onchange="filterTable()">
                            <option value="all">All Frequencies</option>
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Monthly">Monthly</option>
                        </select>

                        <span class="sort-label">Sort by:</span>
                        <select id="sortFilter" onchange="filterTable()">
                            <option value="latest">Latest</option>
                            <option value="impact-high">Highest Impact</option>
                            <option value="impact-low">Lowest Impact</option>
                        </select>
                    </div>
                </div>

                <table class="strategies-table" id="strategiesTable">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Strategy</th>
                            <th style="width: 20%;">Category</th>
                            <th style="width: 15%;">Frequency</th>
                            <th style="width: 20%;">Impact Metric</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($strategies as $index => $strategy): ?>
                            <tr class="strategy-row" 
                                data-index="<?= $index ?>"
                                data-category="<?= htmlspecialchars($strategy['category']) ?>" 
                                data-frequency="<?= htmlspecialchars($strategy['frequency']) ?>"
                                data-impact="<?= $strategy['impact'] ?>"
                                data-title="<?= htmlspecialchars(strtolower($strategy['title'])) ?>">
                                <td>
                                    <div class="strategy-info-cell">
                                        <div class="strategy-icon"><?= $strategy['icon'] ?></div>
                                        <div class="strategy-details">
                                            <div class="title"><?= htmlspecialchars($strategy['title']) ?></div>
                                            <div class="description"><?= htmlspecialchars($strategy['description']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge"><?= htmlspecialchars($strategy['category']) ?></span></td>
                                <td><span class="badge frequency-badge"><?= htmlspecialchars($strategy['frequency']) ?></span></td>
                                <td>
                                    <span class="impact-value"><?= htmlspecialchars($strategy['impact']) ?></span> 
                                    <span class="impact-unit"><?= htmlspecialchars($strategy['unit']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div id="noData" class="no-data-row">No active strategy matches your selected criteria.</div>

                <div class="pagination-footer">
                    <div id="paginationText">Showing 7 to 7 of 7 strategies</div>
                    <div class="pagination-buttons">
                        <button>&lt;</button>
                        <button style="background-color: #eef1f4; font-weight: bold; color: #2D6A4F;">1</button>
                        <button>&gt;</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function filterTable() {
            const categoryValue = document.getElementById('categoryFilter').value;
            const frequencyValue = document.getElementById('frequencyFilter').value;
            const sortValue = document.getElementById('sortFilter').value;
            const searchValue = document.getElementById('searchStrategy').value.toLowerCase();
            
            const rows = Array.from(document.querySelectorAll('.strategy-row'));
            let visibleCount = 0;

            rows.forEach(row => {
                const matchesCategory = (categoryValue === 'all' || row.getAttribute('data-category') === categoryValue);
                const matchesFrequency = (frequencyValue === 'all' || row.getAttribute('data-frequency') === frequencyValue);
                const matchesSearch = row.getAttribute('data-title').includes(searchValue);

                if (matchesCategory && matchesFrequency && matchesSearch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle Sorting Logic dynamically inside the DOM Element tree
            const tbody = document.getElementById('tableBody');
            if (sortValue.startsWith('impact')) {
                rows.sort((a, b) => {
                    const impactA = parseFloat(a.getAttribute('data-impact'));
                    const impactB = parseFloat(b.getAttribute('data-impact'));
                    return sortValue === 'impact-high' ? impactB - impactA : impactA - impactB;
                });
            } else {
                // Return to original array layout index positioning
                rows.sort((a, b) => a.getAttribute('data-index') - b.getAttribute('data-index'));
            }

            // Append explicitly in updated order sequence
            rows.forEach(row => tbody.appendChild(row));

            // Inform runtime if records empty
            document.getElementById('noData').style.display = visibleCount === 0 ? 'block' : 'none';
            document.getElementById('paginationText').textContent = `Showing ${visibleCount} of ${rows.length} strategies`;
        }

        // Initialize script call on window payload mount
        window.addEventListener('DOMContentLoaded', () => {
            filterTable();
        });
    </script>
</body>
</html>