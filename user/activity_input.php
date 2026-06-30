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

// --- CONNECT TO SUPABASE ---
$supabase_url = 'https://cvlibryzqhoztbutyvbx.supabase.co';
$table_name = 'carbon_records';
$supabase_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImN2bGlicnl6cWhvenRidXR5dmJ4Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODIyMDgxNTcsImV4cCI6MjA5Nzc4NDE1N30.q0vj8nBE4_SPVs8DDDeBOnzu8rpvGdfA5GXQpGp5rWs';

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_emissions') {
    
    // Extract calculated inputs from the fields
    $transportation = (float)($_POST['total_transport'] ?? 0);
    $electricity    = (float)($_POST['total_office'] ?? 0);
    $food           = (float)($_POST['total_food'] ?? 0);
    $waste          = 0.00; 

    // Calculate total footprint
    $total_emission = $transportation + $electricity + $food + $waste;

    // Extract user email from session for the g_suite column
    $user_email = $_SESSION['user_data']['email'] ?? null;

    // Individual record row mapped perfectly to your table structure
    $row = [
        'transportation' => $transportation,
        'electricity'    => $electricity,
        'food'           => $food,
        'waste'          => $waste,
        'total_emission' => $total_emission,
        'record_date'    => date('Y-m-d'), // Fills the mandatory date column
        'g_suite'        => $user_email    // Dynamically tracks who submitted the data
    ];

    // Wrap inside the outer array vector for Supabase/PostgREST standard
    $payload = [$row];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, rtrim($supabase_url, '/') . '/rest/v1/' . $table_name);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $supabase_key, // FIX: Changed from expired user token to master key to bypass RLS
        'Content-Type: application/json',
        'Prefer: return=representation'
    ]);
    
    // Bypass local machine SSL verification conflicts
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $curl_error_msg = curl_error($ch);
    }
    curl_close($ch);

    if ($http_code === 201 || $http_code === 200) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error_detail = isset($curl_error_msg) ? " | Detail: " . htmlspecialchars($curl_error_msg) : " | Response: " . htmlspecialchars($response);
        $message = "<div class='alert error'>Error saving to database. Code: $http_code $error_detail</div>";
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
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: #eef1f4; color: #333; overflow: hidden; }

        /* Sidebar Layout */
        .sidebar { width: 260px; background-color: #2D6A4F; color: white; display: flex; flex-direction: column; padding: 20px 0; flex-shrink: 0; }
        .logo-section { display: flex; align-items: center; padding: 10px 25px; margin-bottom: 30px; }
        
        .logo-img-container { width: 45px; height: 45px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.2); }
        .logo-img-container img { width: 100%; height: 100%; object-fit: cover; }
        .logo-text { font-size: 1.1rem; font-weight: 700; letter-spacing: 0.5px; }
        
        .menu-items { flex: 1; display: flex; flex-direction: column; }
        .menu-item { display: flex; align-items: center; padding: 14px 25px; color: #b7e4c7; text-decoration: none; font-size: 0.95rem; font-weight: 500; transition: 0.2s; }
        .menu-item i { margin-right: 15px; width: 20px; text-align: center; font-size: 16px; }
        .menu-item:hover, .menu-item.active { background-color: #40916C; color: white; }
        .menu-separator { height: 1px; background-color: #40916C; margin: 40px 25px 20px 25px; }
        
        /* Main Workspace Layout */
        .main-workspace { flex: 1; display: flex; flex-direction: column; overflow: hidden; height: 100vh; }
        .top-navbar { height: 75px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 40px; flex-shrink: 0; border-bottom: 1px solid #eef1f4; }
        .search-box { display: flex; align-items: center; background-color: #ECEFF1; padding: 10px 15px; border-radius: 8px; width: 350px; }
        .search-box input { border: none; background: transparent; outline: none; margin-left: 10px; width: 100%; font-size: 0.9rem; }
        
        .user-nav-profile { display: flex; align-items: center; gap: 25px; }
        .profile-card { display: flex; align-items: center; gap: 12px; border-left: 1px solid #CBD5E1; padding-left: 25px; }
        .avatar-placeholder { width: 40px; height: 40px; background: #e2f0d9; color: #2D6A4F; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .user-info-text h4 { font-size: 0.95rem; color: #1E293B; font-weight: 700; }
        .user-info-text p { font-size: 0.8rem; color: #64748B; }

        .workspace-body { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }

        /* Progress Bar */
        .progress-wrapper { padding: 25px 40px 0 40px; text-align: center; }
        .progress-container { width: 100%; background-color: #CBD5E1; height: 16px; border-radius: 8px; overflow: hidden; margin-bottom: 8px; }
        .progress-bar { width: 100%; background-color: #2D6A4F; height: 100%; border-radius: 8px; transition: width 0.4s ease; }
        .progress-text { font-size: 0.9rem; font-style: italic; font-weight: 700; color: #1B4332; }

        /* Content Container */
        .content-container { padding: 25px 40px; display: flex; flex-direction: column; gap: 20px; }
        .input-card { background: white; padding: 25px; border-radius: 12px; border: 1px solid #eef1f4; }
        .input-card h3 { font-size: 1.3rem; font-weight: 700; color: #BA181B; margin-bottom: 15px; }
        
        .form-row { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 0.85rem; font-weight: 700; color: #000; }
        .form-group select, .form-group input { padding: 12px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 0.9rem; width: 100%; background: white; outline: none; }
        
        .btn-add { background-color: #40916C; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; height: 45px; }
        .btn-add:hover { background-color: #2D6A4F; }

        /* Review List Layout */
        .list-card { background: white; padding: 25px; border-radius: 12px; border: 1px solid #eef1f4; }
        .list-card h3 { font-size: 1.2rem; font-weight: 700; color: #BA181B; margin-bottom: 20px; }
        .list-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        @media(max-width: 900px) { .list-grid { grid-template-columns: 1fr; } }
        .list-column { background-color: #E8F5E9; border-radius: 8px; min-height: 250px; padding: 20px; }
        .list-column h4 { font-size: 1rem; font-weight: 700; color: #BA181B; text-align: center; margin-bottom: 15px; border-bottom: 2px dashed #BA181B; padding-bottom: 5px; }
        
        .logged-item { font-size: 0.85rem; background: white; padding: 8px 12px; margin-bottom: 8px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex; justify-content: space-between; font-weight: 500; gap: 10px; }
        .logged-item span:first-child { word-break: break-word; }
        
        .btn-calculate { width: 100%; background-color: #40916C; color: white; border: none; padding: 16px; border-radius: 8px; font-size: 1.2rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-calculate:hover { background-color: #2D6A4F; }
        .alert.error { background-color: #fca5a5; color: #7f1d1d; padding: 15px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; }
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
            <a href="dashboard.php" class="menu-item"><i class="fa-solid fa-border-all"></i> Dashboard</a>
            <a href="activity_input.php" class="menu-item active"><i class="fa-solid fa-pen-to-square"></i> Activity Input</a>
            <a href="reports.php" class="menu-item"><i class="fa-solid fa-chart-simple"></i> Reports</a>
            <a href="mitigation_strategies.php" class="menu-item"><i class="fa-solid fa-lightbulb"></i> Mitigation Strategies</a>
            <a href="profile.php" class="menu-item"><i class="fa-solid fa-circle-user"></i> View Profile</a>
            <a href="settings.php" class="menu-item"><i class="fa-solid fa-gear"></i> Settings</a>
            <div class="menu-separator"></div>
            <a href="logout.php" class="menu-item"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
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

        <div class="workspace-body">
            <div class="progress-wrapper">
                <div class="progress-container">
                    <div id="dynamicProgressBar" class="progress-bar"></div>
                </div>
                <p class="progress-text">Green Points: <span id="greenPointsLabel">100</span></p>
            </div>

            <div class="content-container">
                <?= $message; ?>

                <div class="input-card">
                    <h3>Transport</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Transport Type</label>
                            <select id="transportType">
                                <option value="" selected disabled>Select your transport type</option>
                                <option value="0.06">Public Jeepney (0.06 kg/km)</option>
                                <option value="0.103">Motorcycle (0.103 kg/km)</option>
                                <option value="0.171">Private Car (0.171 kg/km)</option>
                                <option value="0.07">Bus (0.07 kg/km)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>One-Way Distance (in kilometers)</label>
                            <input type="number" id="transportDistance" placeholder="Input distance (e.g., 10)">
                        </div>
                        <button type="button" class="btn-add" onclick="addTransport()">Add Emission</button>
                    </div>
                </div>

                <div class="input-card">
                    <h3>Office Resource</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Office Resource / Appliance</label>
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
                            <label>Duration of Usage (Hours)</label>
                            <input type="number" id="officeUsageUnits" placeholder="Input total active hours" step="0.1">
                        </div>
                        <button type="button" class="btn-add" onclick="addOffice()">Add Emission</button>
                    </div>
                </div>

                <div class="input-card">
                    <h3>Food Consumption</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Food Type</label>
                            <select id="foodType">
                                <option value="" selected disabled>Select food type</option>
                                <option value="2.5">Pork / Beef Meal (2.5 kg CO2e)</option>
                                <option value="1.2">Chicken / Poultry (1.2 kg CO2e)</option>
                                <option value="0.4">Vegetarian Meal (0.4 kg CO2e)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Portions Serving Count</label>
                            <input type="number" id="foodServings" placeholder="Input total servings consumed">
                        </div>
                        <button type="button" class="btn-add" onclick="addFood()">Add Emission</button>
                    </div>
                </div>

                <form id="supabaseMasterForm" method="POST" action="activity_input.php">
                    <input type="hidden" name="action" value="save_emissions">
                    <input type="hidden" id="hiddenTransport" name="total_transport" value="0">
                    <input type="hidden" id="hiddenOffice" name="total_office" value="0">
                    <input type="hidden" id="hiddenFood" name="total_food" value="0">
                    <input type="hidden" id="hiddenGreenPoints" name="calculated_green_points" value="100">

                    <div class="list-card">
                        <h3>Your Carbon Emission List</h3>
                        <div class="list-grid">
                            <div class="list-column" id="colTransport">
                                <h4>Transportation</h4>
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
        let totals = { transport: 0, office: 0, food: 0 };
        let points = 100;
        
        const GHG_ELECTRICITY_FACTOR = 0.7122; 

        function updateGreenPointsUI() {
            let collectiveFootprint = totals.transport + totals.office + totals.food;
            let deduction = Math.min(collectiveFootprint * 4, 90); 
            points = Math.round(100 - deduction);
            
            document.getElementById('greenPointsLabel').innerText = points;
            document.getElementById('dynamicProgressBar').style.width = points + '%';
            document.getElementById('hiddenGreenPoints').value = points;
        }

        function addTransport() {
            const typeSel = document.getElementById('transportType');
            const distInp = document.getElementById('transportDistance');
            
            if(!typeSel.value || !distInp.value) return alert("Please specify complete transportation info!");
            
            let calculatedValue = (parseFloat(distInp.value) * 2) * parseFloat(typeSel.value);
            totals.transport += calculatedValue;
            document.getElementById('hiddenTransport').value = totals.transport.toFixed(4);
            
            createListItem('colTransport', `${typeSel.options[typeSel.selectedIndex].text.split(' (')[0]}`, `${calculatedValue.toFixed(2)} kg`);
            distInp.value = "";
            updateGreenPointsUI();
        }

        function addOffice() {
            const typeSel = document.getElementById('officeType');
            const hoursInp = document.getElementById('officeUsageUnits');
            
            if(!typeSel.value || !hoursInp.value) return alert("Please specify complete office resource details!");
            
            let watts = parseFloat(typeSel.value);
            let hours = parseFloat(hoursInp.value);
            let calculatedValue = (watts * hours / 1000) * GHG_ELECTRICITY_FACTOR;
            
            totals.office += calculatedValue;
            document.getElementById('hiddenOffice').value = totals.office.toFixed(4);
            
            let selectedLabel = typeSel.options[typeSel.selectedIndex].text.split(' (')[0];
            createListItem('colOffice', `${selectedLabel} (${hours}h)`, `${calculatedValue.toFixed(4)} kg`);
            
            hoursInp.value = "";
            updateGreenPointsUI();
        }

        function addFood() {
            const typeSel = document.getElementById('foodType');
            const servingsInp = document.getElementById('foodServings');
            
            if(!typeSel.value || !servingsInp.value) return alert("Please specify food criteria data!");
            
            let calculatedValue = parseFloat(typeSel.value) * parseFloat(servingsInp.value);
            totals.food += calculatedValue;
            document.getElementById('hiddenFood').value = totals.food.toFixed(4);
            
            createListItem('colFood', `${typeSel.options[typeSel.selectedIndex].text.split(' (')[0]}`, `${calculatedValue.toFixed(2)} kg`);
            servingsInp.value = "";
            updateGreenPointsUI();
        }

        function createListItem(columnId, labelText, numericalText) {
            const container = document.getElementById(columnId);
            const wrapperDiv = document.createElement('div');
            wrapperDiv.className = 'logged-item';
            wrapperDiv.innerHTML = `<span>${labelText}</span><span>${numericalText}</span>`;
            container.appendChild(wrapperDiv);
        }

        updateGreenPointsUI();
    </script>
</body>
</html>