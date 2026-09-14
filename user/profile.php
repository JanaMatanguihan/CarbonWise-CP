<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Generate CSRF Token for Secure form submissions[cite: 8]
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Guard check: If the user isn't logged in, send them back to login[cite: 8]
if (!isset($_SESSION['user_token'])) {
    header('Location: login.php');
    exit;
}

// --- NEON POSTGRESQL DATABASE CONFIGURATION ---[cite: 8]
$neon_host     = getenv('NEON_DB_HOST')     ?: 'ep-red-hill-a5erg1sb-pooler.us-east-2.aws.neon.tech';
$neon_port     = getenv('NEON_DB_PORT')     ?: '5432';
$neon_dbname   = getenv('NEON_DB_NAME')     ?: 'neondb';
$neon_user     = getenv('NEON_DB_USER')     ?: 'neondb_owner';
$neon_password = getenv('NEON_DB_PASSWORD') ?: 'npg_B7h4oEQbqJdG';

$endpoint_id = 'ep-red-hill-a5erg1sb-pooler';
$dsn = "pgsql:host={$neon_host};port={$neon_port};dbname={$neon_dbname};sslmode=require;options='endpoint={$endpoint_id}'";

$pdo = null;
$db_connection_error = '';

try {
    $pdo = new PDO($dsn, $neon_user, $neon_password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    $db_connection_error = $e->getMessage();
}

// 3. Extract user session data strictly using user_id[cite: 8]
$user_data = $_SESSION['user_profile'] ?? ($_SESSION['user_data'] ?? []);
$user_id   = $_SESSION['user_id'] ?? ($user_data['id'] ?? null); 
$user_metadata = $user_data['user_metadata'] ?? [];

// Redirect if user_id is missing from session[cite: 8]
if (empty($user_id)) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user_email'] ?? ($user_data['email'] ?? ($user_metadata['email'] ?? 'Unknown Email'));
$user_name  = $_SESSION['user_name'] ?? ($user_data['full_name'] ?? ($user_metadata['full_name'] ?? ($user_metadata['name'] ?? '')));

// 4. Extract Name & Role[cite: 8]
$raw_name = $user_name;
if (isset($user_data['role']) && strtolower($user_data['role']) !== 'authenticated') {
    $raw_role = $user_data['role'];
} else {
    $raw_role = $user_metadata['role'] ?? '';
}

// 5. Extract Department & Campus from session user data[cite: 8]
$raw_dept   = $user_data['department'] ?? ($user_metadata['department'] ?? '');
$raw_campus = $user_data['campus'] ?? ($user_metadata['campus'] ?? '');

$full_name = !empty($raw_name) ? ucwords(strtolower(trim($raw_name))) : 'Unknown User'; 
$role      = (!empty($raw_role) && strtolower($raw_role) !== 'authenticated') ? ucwords(strtolower(trim($raw_role))) : 'Student / Faculty / Staff';

if (!empty($raw_dept) && !empty($raw_campus)) {
    $dept_and_campus = strtoupper(trim($raw_dept)) . ' - ' . ucwords(strtolower(trim($raw_campus)));
} elseif (!empty($raw_dept)) {
    $dept_and_campus = strtoupper(trim($raw_dept));
} elseif (!empty($raw_campus)) {
    $dept_and_campus = ucwords(strtolower(trim($raw_campus)));
} else {
    $dept_and_campus = 'No Department / Campus Assigned';
}

// --- POST INTERCEPTOR PIPES & CONTROLLERS ---[cite: 8]
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CSRF Guard verification[cite: 8]
    $provided_token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $provided_token)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'CSRF verification token expired or mismatched.']);
        exit;
    }

    // Process Avatar Upload strictly bound to user_id[cite: 7, 8]
    if (isset($_FILES['profile_avatar'])) {
        $file = $_FILES['profile_avatar'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            header("Location: profile.php?status=error&msg=" . urlencode("Upload error encountered. Code: " . $file['error']));
            exit;
        }

        $max_size = 2 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            header("Location: profile.php?status=error&msg=" . urlencode("Image size must be less than 2MB."));
            exit;
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            header("Location: profile.php?status=error&msg=" . urlencode("Invalid format. Use JPG, PNG, WEBP, or GIF."));
            exit;
        }

        if (!$pdo) {
            header("Location: profile.php?status=error&msg=" . urlencode("Database connection issue. Unable to update profile picture."));
            exit;
        }

        // --- LOCAL FILE STORAGE PIPELINE ---[cite: 7]
        $upload_dir = 'uploads/avatars/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $extension   = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename    = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :user_id");
                $stmt->execute([
                    ':profile_picture' => $target_path,
                    ':user_id'         => $user_id
                ]);

                $_SESSION['user_profile']['user_metadata']['profile_picture'] = $target_path;
                header("Location: profile.php?status=success&msg=" . urlencode("Profile picture updated!"));
                exit;
            } catch (PDOException $e) {
                header("Location: profile.php?status=error&msg=" . urlencode("Database failure: " . $e->getMessage()));
                exit;
            }
        } else {
            header("Location: profile.php?status=error&msg=" . urlencode("Failed to move uploaded file. Check folder permissions."));
            exit;
        }
    }

    // Process Password Updates strictly bound to user_id[cite: 8]
    if (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password) || strlen($new_password) < 4) {
            header("Location: profile.php?status=error&msg=" . urlencode("Password must be at least 4 characters long."));
            exit;
        }

        if ($new_password !== $confirm_password) {
            header("Location: profile.php?status=error&msg=" . urlencode("Passwords do not match."));
            exit;
        }

        if (!$pdo) {
            header("Location: profile.php?status=error&msg=" . urlencode("Database connection failure. Password not updated."));
            exit;
        }

        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :user_id");
            $stmt->execute([
                ':password' => $hashed_password,
                ':user_id'  => $user_id
            ]);

            header("Location: profile.php?status=success&msg=" . urlencode("Password updated successfully!"));
            exit;
        } catch (PDOException $e) {
            header("Location: profile.php?status=error&msg=" . urlencode("Database modification failure: " . $e->getMessage()));
            exit;
        }
    }
}

// 6. DYNAMICALLY CALCULATE CARBON SCORE FROM carbon_records STRICTLY BY user_id[cite: 8]
$current_week_score = 0;
$last_week_score    = 0;
$grand_total        = 0;
$trend_text         = "Starting your green track!";
$info_http_code     = 500;
$info_response      = !empty($db_connection_error) ? $db_connection_error : "";

if ($pdo) {
    try {
        // Query profile_picture by user_id[cite: 8]
        $avatar_stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = :user_id LIMIT 1");
        $avatar_stmt->execute([':user_id' => $user_id]);
        $user_row = $avatar_stmt->fetch();
        if ($user_row && !empty($user_row['profile_picture'])) {
            $_SESSION['user_profile']['user_metadata']['profile_picture'] = $user_row['profile_picture'];
        }

        // Aggregate emissions data matching strictly by user_id[cite: 8]
        $score_stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CAST(REGEXP_REPLACE(COALESCE(total_emission::text, '0'), '[^0-9.]', '', 'g') AS NUMERIC)), 0) AS grand_total,
                COALESCE(SUM(CASE WHEN created_at >= NOW() - INTERVAL '7 days' THEN CAST(REGEXP_REPLACE(COALESCE(total_emission::text, '0'), '[^0-9.]', '', 'g') AS NUMERIC) ELSE 0 END), 0) AS current_week,
                COALESCE(SUM(CASE WHEN created_at >= NOW() - INTERVAL '14 days' AND created_at < NOW() - INTERVAL '7 days' THEN CAST(REGEXP_REPLACE(COALESCE(total_emission::text, '0'), '[^0-9.]', '', 'g') AS NUMERIC) ELSE 0 END), 0) AS last_week
            FROM carbon_records 
            WHERE user_id = :user_id
        ");
        $score_stmt->execute([':user_id' => $user_id]);
        $score_data = $score_stmt->fetch();

        if ($score_data) {
            $info_http_code = 200;
            $info_response  = json_encode($score_data);

            $grand_total = floatval($score_data['grand_total']);
            $weekly_sum  = floatval($score_data['current_week']);
            
            // Fallback: If current 7-day total is 0, display cumulative lifetime carbon score[cite: 8]
            $current_week_score = ($weekly_sum > 0) ? round($weekly_sum, 1) : round($grand_total, 1);
            $last_week_score    = floatval($score_data['last_week']);

            // Sync calculated score to users table[cite: 8]
            $update_stmt = $pdo->prepare("UPDATE users SET carbon_score = :score WHERE id = :user_id");
            $update_stmt->execute([
                ':score'   => $current_week_score,
                ':user_id' => $user_id
            ]);
        }

        // Generate performance text[cite: 8]
        if ($current_week_score > 0) {
            if ($last_week_score > 0) {
                if ($current_week_score < $last_week_score) {
                    $pct = round((($last_week_score - $current_week_score) / $last_week_score) * 100);
                    $trend_text = "{$pct}% less than last week";
                } elseif ($current_week_score > $last_week_score) {
                    $pct = round((($current_week_score - $last_week_score) / $last_week_score) * 100);
                    $trend_text = "{$pct}% more than last week";
                } else {
                    $trend_text = "Same emissions as last week";
                }
            } else {
                $trend_text = "Tracking active emissions metrics!";
            }
        } else {
            $trend_text = "Starting your green track!";
        }

    } catch (PDOException $e) {
        $info_response = "Error calculating carbon score: " . $e->getMessage();
    }
}

// 7. DYNAMICALLY COMPUTE CATEGORY BREAKDOWNS (NO HARDCODED SCHEMA)[cite: 8]
$categories_data = [
    'transportation'   => ['current_week' => 0, 'top_activity' => 'Commuting Daily', 'icon' => 'fa-bus'],
    'office_resource'  => ['current_week' => 0, 'top_activity' => 'Electricity & Office Usage', 'icon' => 'fa-bolt'],
    'food_consumption' => ['current_week' => 0, 'top_activity' => 'Meals & Food Log', 'icon' => 'fa-utensils']
];

if ($pdo) {
    try {
        // 1. Inspect table columns dynamically at runtime[cite: 8]
        $col_stmt = $pdo->prepare("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name = 'carbon_records'
        ");
        $col_stmt->execute();
        $existing_columns = $col_stmt->fetchAll(PDO::FETCH_COLUMN);

        // Define search patterns matching possible emission column names[cite: 8]
        $category_patterns = [
            'transportation'   => ['transport', 'commute', 'travel', 'vehicle'],
            'office_resource'  => ['office', 'elec', 'energy', 'resource', 'power', 'utility'],
            'food_consumption' => ['food', 'meal', 'diet', 'consumption', 'eat']
        ];

        // 2. Identify relevant emission columns dynamically for each category[cite: 8]
        $dynamic_selects = [];
        
        foreach ($category_patterns as $category_key => $patterns) {
            $matching_cols = [];
            foreach ($existing_columns as $col) {
                if (in_array($col, ['id', 'user_id', 'created_at', 'record_date', 'total_emission', 'transport_item', 'office_item', 'food_item'])) {
                    continue;
                }
                foreach ($patterns as $pattern) {
                    if (strpos(strtolower($col), $pattern) !== false) {
                        $matching_cols[] = $col;
                        break;
                    }
                }
            }

            if (!empty($matching_cols)) {
                $col_expressions = array_map(function($col) {
                    return "CAST(NULLIF(REGEXP_REPLACE(COALESCE(\"{$col}\"::text, '0'), '[^0-9.]', '', 'g'), '') AS NUMERIC)";
                }, $matching_cols);
                
                $sum_expr = implode(' + ', $col_expressions);
                $dynamic_selects[] = "COALESCE(SUM({$sum_expr}), 0) AS {$category_key}_sum";
            } else {
                $dynamic_selects[] = "0 AS {$category_key}_sum";
            }
        }

        // 3. Execute dynamically assembled summation query[cite: 8]
        $dynamic_sql = "SELECT " . implode(', ', $dynamic_selects) . " FROM carbon_records WHERE user_id = :user_id";
        $breakdown_stmt = $pdo->prepare($dynamic_sql);
        $breakdown_stmt->execute([':user_id' => $user_id]);
        $totals = $breakdown_stmt->fetch();

        if ($totals) {
            $categories_data['transportation']['current_week']   = floatval($totals['transportation_sum'] ?? 0);
            $categories_data['office_resource']['current_week']  = floatval($totals['office_resource_sum'] ?? 0);
            $categories_data['food_consumption']['current_week'] = floatval($totals['food_consumption_sum'] ?? 0);
        }

        // 4. Dynamically fetch latest activity description text without hardcoded expectations[cite: 8]
        $activity_fields = [
            'transportation'   => ['transport_item', 'transportation_item', 'travel_item', 'commute_type'],
            'office_resource'  => ['office_item', 'electricity_item', 'resource_item', 'appliance'],
            'food_consumption' => ['food_item', 'meal_item', 'diet_item', 'food_type']
        ];

        foreach ($activity_fields as $cat_key => $possible_text_cols) {
            $valid_text_col = array_intersect($possible_text_cols, $existing_columns);
            if (!empty($valid_text_col)) {
                $col_name = reset($valid_text_col);
                $act_stmt = $pdo->prepare("
                    SELECT \"{$col_name}\" 
                    FROM carbon_records 
                    WHERE user_id = :user_id AND \"{$col_name}\" IS NOT NULL AND \"{$col_name}\"::text != '' 
                    ORDER BY id DESC LIMIT 1
                ");
                $act_stmt->execute([':user_id' => $user_id]);
                if ($res = $act_stmt->fetch()) {
                    $categories_data[$cat_key]['top_activity'] = ucwords(strtolower(trim($res[$col_name])));
                }
            }
        }

    } catch (PDOException $e) {
        // Safe fallback execution
    }
}

// 8. FETCH RECENT ACTIVITY TIMELINE STRICTLY BY user_id[cite: 8]
$timeline_records = [];
if ($pdo) {
    try {
        $timeline_stmt = $pdo->prepare("
            SELECT * FROM carbon_records 
            WHERE user_id = :user_id 
            ORDER BY id DESC 
            LIMIT 5
        ");
        $timeline_stmt->execute([':user_id' => $user_id]);
        $timeline_records = $timeline_stmt->fetchAll();
    } catch (PDOException $e) {
        $timeline_records = [];
    }
}

// Avatar URL retrieval using profile_picture[cite: 8]
$avatar_url = $_SESSION['user_profile']['user_metadata']['profile_picture'] ?? ($user_metadata['profile_picture'] ?? null); 

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarbonWise - View Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-body: #F4F6F6;
            --bg-card: #ffffff;
            --bg-sidebar: #2D6A4F;
            --bg-sidebar-hover: #3F8A65;
            --text-main: #2D3748;
            --text-title: #1A202C;
            --text-muted: #718096;
            --text-sidebar-menu: #D8F3DC;
            --border-color: #E2E8F0;
            --accent-green: #2D6A4F;
            --accent-green-hover: #1B4332;
            --bell-bg: #E8F5E9;
            --achieve-locked-bg: #F8FAFC;
        }

        body.dark-mode {
            --bg-body: #121212;
            --bg-card: #1E1E1E;
            --bg-sidebar: #090F0C;             
            --bg-sidebar-hover: #14241C;       
            --text-sidebar-menu: #74C69D;      
            --text-main: #CBD5E0;
            --text-title: #F7FAFC;
            --text-muted: #A0AEC0;
            --border-color: #2D3748;
            --bell-bg: #1B4332;
            --achieve-locked-bg: #252525;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: var(--bg-body); color: var(--text-main); overflow: hidden; transition: background-color 0.3s, color 0.3s; }

        .sidebar { width: 260px; background-color: var(--bg-sidebar); color: white; display: flex; flex-direction: column; padding: 25px 0; flex-shrink: 0; transition: background-color 0.3s; }
        .logo-section { display: flex; align-items: center; padding: 0 25px; margin-bottom: 40px; gap: 12px; }
        .brand-logo-container { width: 38px; height: 38px; border-radius: 50%; background-color: white; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .brand-logo-container img { width: 85%; height: 85%; object-fit: contain; }
        .logo-text { font-size: 1.15rem; font-weight: 800; letter-spacing: 0.5px; color: #ffffff; }
        
        .menu-items { flex: 1; display: flex; flex-direction: column; }
        .menu-item { display: flex; align-items: center; padding: 14px 25px; color: var(--text-sidebar-menu); text-decoration: none; font-size: 0.95rem; font-weight: 500; cursor: pointer; gap: 15px; transition: background-color 0.3s, color 0.3s; }
        .menu-item i { width: 20px; font-size: 1.1rem; text-align: center; }
        .menu-item:hover, .menu-item.active { background-color: var(--bg-sidebar-hover); color: #ffffff; }
        .menu-item.active { font-weight: 600; border-left: 4px solid #74C69D; padding-left: 21px; }
        
        .sidebar-footer { margin-top: auto; }
        .sidebar-divider { height: 1px; background-color: rgba(255, 255, 255, 0.1); margin: 15px 25px; }

        .main-workspace { flex: 1; display: flex; flex-direction: column; overflow: hidden; position: relative; }
        .top-navbar { height: 85px; background: var(--bg-card); display: flex; align-items: center; justify-content: space-between; padding: 0 40px; border-bottom: 1px solid var(--border-color); flex-shrink: 0; transition: background-color 0.3s, border-color 0.3s; }
        .page-heading h2 { font-size: 1.5rem; font-weight: 700; color: var(--text-title); }
        .page-heading p { font-size: 0.85rem; color: var(--text-muted); margin-top: 2px; }

        .user-nav-profile { display: flex; align-items: center; gap: 20px; }
        
        .notification-container { position: relative; display: inline-block; }
        .notification-bell { background: var(--bell-bg); padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--accent-green); font-size: 20px; width: 44px; height: 44px; position: relative; transition: background-color 0.3s, color 0.3s; }
        .notification-badge { position: absolute; top: 0; right: 0; background-color: #E63946; color: white; font-size: 10px; font-weight: 700; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; }
        
        .notification-dropdown { position: absolute; top: 55px; right: 0; width: 380px; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); display: none; flex-direction: column; overflow: hidden; z-index: 1000; transition: background-color 0.3s, border-color 0.3s; }
        .notification-dropdown.visible { display: flex; }
        .noti-header { padding: 16px 20px; font-size: 1.05rem; font-weight: 700; color: #1A202C; border-bottom: 1px solid var(--border-color); }
        body.dark-mode .noti-header { color: #F7FAFC; }
        .noti-diagnostic-box { background-color: #FFF9E6; border-bottom: 1px solid #FFEAA7; padding: 15px 20px; font-size: 0.85rem; color: #B7791F; text-align: left; line-height: 1.6; max-height: 200px; overflow-y: auto; }
        body.dark-mode .noti-diagnostic-box { background-color: #2D2510; border-bottom: 1px solid #443310; color: #ECC94B; }
        .noti-diagnostic-box strong { display: block; margin-bottom: 6px; font-weight: 700; font-size: 0.9rem; }
        
        .noti-empty-state { padding: 15px 20px; text-align: center; color: var(--text-muted); font-size: 0.85rem; font-weight: 500; }

        .header-profile-box { display: flex; align-items: center; gap: 12px; border-left: 2px solid var(--border-color); padding-left: 25px; transition: border-left 0.3s; }
        .nav-avatar { width: 44px; height: 44px; background: #E2E8F0; color: var(--accent-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; overflow: hidden; }
        .nav-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .workspace-body { flex: 1; overflow-y: auto; padding: 30px 40px; display: flex; flex-direction: column; gap: 25px; }
        
        .metrics-row { display: grid; grid-template-columns: 1.2fr 0.9fr 0.9fr; gap: 25px; }
        .dashboard-grid { display: grid; grid-template-columns: 1.6fr 1.4fr; gap: 25px; }
        .single-column-grid { display: grid; grid-template-columns: 1fr; gap: 25px; }

        .dash-card { background: var(--bg-card); border-radius: 12px; padding: 25px; border: 1px solid var(--border-color); position: relative; transition: background-color 0.3s, border-color 0.3s; }
        .dash-card-title { font-size: 1rem; font-weight: 700; color: var(--text-title); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .card-link { font-size: 0.85rem; color: var(--accent-green); text-decoration: none; font-weight: 600; }

        .profile-main-box { display: flex; align-items: center; gap: 25px; }
        .avatar-uploader { position: relative; cursor: pointer; border-radius: 50%; width: 90px; height: 90px; overflow: hidden; background: #EDF2F7; border: 3px solid var(--accent-green); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; color: var(--accent-green); }
        .avatar-uploader img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-overlay { position: absolute; inset: 0; background: rgba(45, 106, 79, 0.8); display: flex; align-items: center; justify-content: center; color: white; opacity: 0; transition: opacity 0.2s; font-size: 1.2rem; }
        .avatar-uploader:hover .avatar-overlay { opacity: 1; }
        
        .profile-text-details h3 { font-size: 1.4rem; font-weight: 700; color: var(--text-title); }
        .profile-text-details .role-tag { font-size: 0.95rem; color: var(--text-muted); font-weight: 500; margin-top: 2px; }
        .profile-text-details .sub-details { font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; }

        .score-value { font-size: 3rem; font-weight: 800; color: var(--accent-green); line-height: 1; }
        .score-unit { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; }
        .score-trend { font-size: 0.85rem; color: #52B788; font-weight: 700; margin-top: 8px; }

        .sustainability-badge-box { display: flex; align-items: center; gap: 15px; margin-top: 5px; }
        .badge-icon-circle { width: 55px; height: 55px; border-radius: 50%; background: #D8F3DC; display: flex; align-items: center; justify-content: center; color: var(--accent-green); font-size: 1.5rem; }
        .badge-title { font-size: 1rem; font-weight: 700; color: var(--accent-green); }
        .badge-desc { font-size: 0.75rem; color: var(--text-muted); max-width: 150px; }

        .breakdown-tabs { display: flex; gap: 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 8px; margin-bottom: 20px; }
        .tab-item { font-size: 0.9rem; font-weight: 700; color: var(--text-muted); cursor: pointer; text-decoration: none; padding-bottom: 8px; }
        .tab-item.active { color: var(--accent-green); border-bottom: 3px solid var(--accent-green); }

        .breakdown-body { display: flex; justify-content: space-between; align-items: center; min-height: 100px; }

        .achievement-list { display: flex; gap: 15px; }
        .achieve-card { flex: 1; border: 1px solid var(--border-color); border-radius: 10px; padding: 15px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .achieve-card i { font-size: 2rem; color: var(--accent-green); }
        .achieve-card h5 { font-size: 0.85rem; font-weight: 700; color: var(--text-title); }
        .achieve-card p { font-size: 0.75rem; color: var(--text-muted); line-height: 1.2; }

        .timeline-box { display: flex; flex-direction: column; gap: 15px; }
        .timeline-item { display: flex; gap: 15px; position: relative; font-size: 0.85rem; }
        .timeline-icon { width: 32px; height: 32px; background: var(--bell-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--accent-green); z-index: 2; flex-shrink: 0; }
        .timeline-details { flex: 1; }
        .timeline-details h5 { font-weight: 700; font-size: 0.85rem; color: var(--text-title); }
        .timeline-details p { color: var(--text-muted); font-size: 0.75rem; }
        .timeline-right { text-align: right; font-weight: 700; color: var(--text-title); font-size: 0.85rem; }
        .timeline-right span { display: block; font-size: 0.7rem; color: var(--text-muted); font-weight: 400; }

        .settings-banner { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; transition: background-color 0.3s, border-color 0.3s; }
        .settings-info { display: flex; align-items: center; gap: 20px; }
        .settings-info i { font-size: 1.8rem; color: var(--text-muted); }
        .settings-info h4 { font-size: 1rem; font-weight: 700; color: var(--text-title); }
        .settings-info p { font-size: 0.85rem; color: var(--text-muted); }

        .edit-profile-btn { background: transparent; border: 1px solid var(--accent-green); color: var(--accent-green); padding: 10px 22px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; text-decoration: none; }
        .edit-profile-btn:hover { background: var(--accent-green); color: white; }

        .modal-input-group { margin-bottom: 15px; text-align: left; }
        .modal-input-group label { display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; }
        .modal-input { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; outline: none; background: var(--bg-body); color: var(--text-title); }
    </style>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark-mode');
            }
        })();
        
        const globalCategoriesData = <?= json_encode($categories_data); ?>;
        const appCsrfToken = "<?= $_SESSION['csrf_token']; ?>";
    </script>
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
            <a href="mitigation_strategies.php" class="menu-item"><i class="fa-solid fa-lightbulb"></i> Mitigation Strategies</a>
            <a href="profile.php" class="menu-item active"><i class="fa-solid fa-circle-user"></i> View Profile</a>
            
            <div class="sidebar-footer">
                <div class="sidebar-divider"></div>
                <a href="javascript:void(0);" id="themeToggleSidebar" class="menu-item">
                    <i class="fa-solid fa-moon" id="themeIconSidebar"></i> 
                    <span id="themeTextSidebar">Dark Mode</span>
                </a>
                <a href="javascript:void(0);" onclick="confirmLogout();" class="menu-item"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
            </div>
        </div>
    </div>

    <div class="main-workspace">
        <div class="top-navbar">
            <div class="page-heading">
                <h2>View Profile</h2>
                <p>Update your personal details and organization info</p>
            </div>
            <div class="user-nav-profile">
                <div class="notification-container">
                    <div class="notification-bell" id="notiBellTrigger">
                        <i class="fa-regular fa-bell"></i>
                        <span class="notification-badge">!</span>
                    </div>
                    
                    <div class="notification-dropdown" id="notiDropdownMenu">
                        <div class="noti-header">Database Diagnostic Tool</div>
                        <div class="noti-diagnostic-box">
                            <strong>Neon DB Connection Info:</strong>
                            <div style="font-family: monospace; font-size: 11px; white-space: pre-wrap; word-break: break-all; margin-top: 5px; color: #4A5568;">
                                <?php 
                                    echo "STATUS CODE: " . $info_http_code . "\n";
                                    if (empty($info_response)) {
                                        echo "RESPONSE: [Empty/No Data]";
                                    } else {
                                        echo "RESPONSE: " . htmlspecialchars(substr($info_response, 0, 300));
                                    }
                                ?>
                            </div>
                        </div>
                        <div class="noti-empty-state">Querying User ID: <?= htmlspecialchars((string)$user_id) ?></div>
                    </div>
                </div>

                <div class="header-profile-box">
                    <div class="nav-avatar">
                        <?php if (!empty($avatar_url)): ?>
                            <img src="<?= htmlspecialchars($avatar_url) ?>" alt="Avatar">
                        <?php else: ?>
                            <?= htmlspecialchars($initials) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-title);"><?= htmlspecialchars($full_name) ?></h4>
                        <p style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($role) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="workspace-body">
            <div class="metrics-row">
                <div class="dash-card">
                    <div class="profile-main-box">
                        <form id="avatarForm" method="POST" action="profile.php" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <label class="avatar-uploader" title="Click to upload profile picture">
                                <?php if (!empty($avatar_url)): ?>
                                    <img src="<?= htmlspecialchars($avatar_url) ?>" alt="Avatar">
                                <?php else: ?>
                                    <span><?= htmlspecialchars($initials) ?></span>
                                <?php endif; ?>
                                <div class="avatar-overlay"><i class="fa-solid fa-camera"></i></div>
                                <input type="file" name="profile_avatar" style="display: none;" accept="image/*" onchange="document.getElementById('avatarForm').submit();">
                            </label>
                        </form>
                        <div class="profile-text-details">
                            <h3 style="color: var(--text-title);"><?= htmlspecialchars($full_name) ?></h3>
                            <div class="role-tag"><?= htmlspecialchars($role) ?></div>
                            <div class="sub-details" style="font-weight: 500;"><?= htmlspecialchars($dept_and_campus) ?></div>
                            <div class="sub-details" style="color: var(--accent-green); font-weight: 500; margin-top: 2px;"><?= htmlspecialchars($user_email) ?></div>
                        </div>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-title">Carbon Score</div>
                    <div>
                        <span class="score-value" id="liveCarbonScoreVal" style="color: #143D28; font-size: 3rem; font-weight: 800; line-height: 1;">
                            <?= htmlspecialchars($current_week_score); ?>
                        </span>
                        <span class="score-unit" style="color: #4A5568; font-size: 1rem; font-weight: 500; margin-left: 4px;">kg CO2 / week</span>
                    </div>
                    
                    <div class="score-trend" id="liveCarbonTrendTxt" style="color: #52B788; font-weight: 700; margin-top: 8px;">
                        <?= htmlspecialchars($trend_text); ?>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-title">Sustainability Level</div>
                    <div class="sustainability-badge-box">
                        <div class="badge-icon-circle"><i class="fa-solid fa-leaf"></i></div>
                        <div>
                            <div class="badge-title">Conscious User</div>
                            <div class="badge-desc">Keep it up! You are on the right track.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dash-card">
                    <div class="breakdown-tabs">
                        <span class="tab-item active" onclick="switchCategoryTab(this, 'transportation')">Transportation</span>
                        <span class="tab-item" onclick="switchCategoryTab(this, 'office_resource')">Office Resource</span>
                        <span class="tab-item" onclick="switchCategoryTab(this, 'food_consumption')">Food Consumption</span>
                    </div>
                    <div class="breakdown-body">
                        <div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Total Impact</div>
                            <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-title); margin: 4px 0;">
                                <span id="tabWeeklyScore">
                                    <?php 
                                        $val = floatval($categories_data['transportation']['current_week']);
                                        echo ($val == floor($val)) ? number_format($val, 0) : number_format($val, 1);
                                    ?>
                                </span> 
                                <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-muted);">kg CO2</span>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Total cumulative emissions impact</div>
                            
                            <div style="margin-top: 20px; display: flex; align-items: center; gap: 10px;">
                                <i id="tabActivityIcon" class="fa-solid fa-bus" style="color: var(--accent-green); font-size: 1.2rem; width: 24px; text-align: center;"></i>
                                <div>
                                    <div id="tabActivityName" style="font-size: 0.8rem; font-weight: 700; color: var(--text-title);"><?= htmlspecialchars($categories_data['transportation']['top_activity']) ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">Contributing activity type</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 25px; text-align: right;">
                        <a href="reports.php" class="card-link">View Breakdown Details</a>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-title">Achievements <a href="#" class="card-link">View All</a></div>
                    <div class="achievement-list">
                        <div class="achieve-card">
                            <i class="fa-solid fa-seedling"></i>
                            <h5>Going Green</h5>
                            <p>Start your sustainability journey.</p>
                        </div>
                        <div class="achieve-card">
                            <i class="fa-solid fa-bicycle"></i>
                            <h5>Eco Commute</h5>
                            <p>Choose greener ways to travel.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="single-column-grid">
                <div class="dash-card">
                    <div class="dash-card-title">Activity Timeline <a href="activity_input.php" class="card-link">View All</a></div>
                    <div class="timeline-box">
                        <?php if (!empty($timeline_records)): ?>
                            <?php foreach ($timeline_records as $rec): 
                                $trans_val  = floatval(preg_replace('/[^0-9.]/', '', $rec['transportation'] ?? '0'));
                                $elec_val   = floatval(preg_replace('/[^0-9.]/', '', $rec['electricity'] ?? ($rec['office_transportation'] ?? '0')));
                                $food_val   = floatval(preg_replace('/[^0-9.]/', '', $rec['food'] ?? ($rec['food_consumption'] ?? ($rec['food consumption'] ?? '0'))));
                                $total_val  = floatval(preg_replace('/[^0-9.]/', '', $rec['total_emission'] ?? ($trans_val + $elec_val + $food_val)));
                                
                                $item_title = 'Carbon Record';
                                $item_desc  = '';
                                $icon_class = 'fa-leaf';
                                
                                if (!empty($rec['transport_item'])) {
                                    $item_title = 'Transportation';
                                    $item_desc  = ucwords(strtolower($rec['transport_item']));
                                    $icon_class = 'fa-car';
                                } elseif (!empty($rec['office_item'])) {
                                    $item_title = 'Office Resource';
                                    $item_desc  = ucwords(strtolower($rec['office_item']));
                                    $icon_class = 'fa-bolt';
                                } elseif (!empty($rec['food_item'])) {
                                    $item_title = 'Food Consumption';
                                    $item_desc  = ucwords(strtolower($rec['food_item']));
                                    $icon_class = 'fa-utensils';
                                } else {
                                    $item_desc  = 'Daily Emissions Entry';
                                }
                                
                                $time_formatted = isset($rec['created_at']) ? date('M j, Y g:ia', strtotime($rec['created_at'])) : ($rec['record_date'] ?? 'Recent');
                            ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon"><i class="fa-solid <?= $icon_class ?>"></i></div>
                                    <div class="timeline-details">
                                        <h5><?= htmlspecialchars($item_title) ?></h5>
                                        <p><?= htmlspecialchars($item_desc) ?></p>
                                    </div>
                                    <div class="timeline-right">+<?= number_format($total_val, 1) ?> kg<span><?= htmlspecialchars($time_formatted) ?></span></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: var(--text-muted); padding: 20px; font-size: 0.85rem;">No activity records found in Neon database for this user.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="settings-banner">
                <div class="settings-info">
                    <i class="fa-solid fa-user-gear"></i>
                    <div>
                        <h4>Account Settings</h4>
                        <p>Manage your personal information, password, and notification preferences.</p>
                    </div>
                </div>
                <button type="button" class="edit-profile-btn" onclick="openPasswordModal()">Edit Profile</button>
            </div>
        </div>
    </div>

    <script>
        const themeToggleSidebar = document.getElementById('themeToggleSidebar');
        const themeIconSidebar = document.getElementById('themeIconSidebar');
        const themeTextSidebar = document.getElementById('themeTextSidebar');
        const bodyElement = document.body;

        const notiBellTrigger = document.getElementById('notiBellTrigger');
        const notiDropdownMenu = document.getElementById('notiDropdownMenu');

        notiBellTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            notiDropdownMenu.classList.toggle('visible');
        });

        document.addEventListener('click', (e) => {
            if (!notiDropdownMenu.contains(e.target) && e.target !== notiBellTrigger) {
                notiDropdownMenu.classList.remove('visible');
            }
        });

        function switchCategoryTab(element, categoryKey) {
            document.querySelectorAll('.breakdown-tabs .tab-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');

            const targetData = globalCategoriesData[categoryKey];
            
            const val = Number(targetData.current_week);
            document.getElementById('tabWeeklyScore').textContent = (val % 1 === 0) ? val : val.toFixed(1);
            document.getElementById('tabActivityName').textContent = targetData.top_activity;
            
            const iconEl = document.getElementById('tabActivityIcon');
            iconEl.className = `fa-solid ${targetData.icon}`;
        }

        function updateThemeUI(isDark) {
            if (isDark) {
                themeIconSidebar.className = 'fa-solid fa-sun';
                themeTextSidebar.textContent = 'Light Mode';
            } else {
                themeIconSidebar.className = 'fa-solid fa-moon';
                themeTextSidebar.textContent = 'Dark Mode';
            }
        }

        if (localStorage.getItem('theme') === 'dark') {
            bodyElement.classList.add('dark-mode');
            updateThemeUI(true);
        } else {
            updateThemeUI(false);
        }

        themeToggleSidebar.addEventListener('click', () => {
            bodyElement.classList.toggle('dark-mode');
            const isDark = bodyElement.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeUI(isDark);
        });

        function openPasswordModal() {
            const isDarkModeActive = bodyElement.classList.contains('dark-mode');
            Swal.fire({
                title: 'Update Credentials',
                background: isDarkModeActive ? '#1E1E1E' : '#ffffff',
                color: isDarkModeActive ? '#F7FAFC' : '#1A202C',
                html: `
                    <form id="swalPasswordForm" method="POST" action="profile.php">
                        <input type="hidden" name="action" value="update_password">
                        <input type="hidden" name="csrf_token" value="${appCsrfToken}">
                        <div class="modal-input-group">
                            <label style="color: ${isDarkModeActive ? '#A0AEC0' : '#718096'}">New Password</label>
                            <input type="password" name="new_password" class="modal-input" placeholder="Enter new password" required>
                        </div>
                        <div class="modal-input-group">
                            <label style="color: ${isDarkModeActive ? '#A0AEC0' : '#718096'}">Confirm Password</label>
                            <input type="password" name="confirm_password" class="modal-input" placeholder="Confirm new password" required>
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonColor: '#2D6A4F',
                cancelButtonColor: '#BA181B',
                confirmButtonText: 'Save Changes',
                preConfirm: () => {
                    const form = document.getElementById('swalPasswordForm');
                    if (form.checkValidity()) {
                        form.submit();
                    } else {
                        Swal.showValidationMessage('Please fill out all fields.');
                    }
                }
            });
        }

        function confirmLogout() {
            const isDarkModeActive = bodyElement.classList.contains('dark-mode');
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to log out of your CarbonWise session?",
                icon: 'warning',
                iconColor: '#f42828',
                background: isDarkModeActive ? '#1E1E1E' : '#ffffff',
                color: isDarkModeActive ? '#F7FAFC' : '#1A202C',
                showCancelButton: true,
                confirmButtonColor: '#2D6A4F',
                cancelButtonColor: '#BA181B',
                confirmButtonText: 'Yes, log me out',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php?t=' + new Date().getTime();
                }
            });
        }

        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');
        if (status && msg) {
            const isDarkModeActive = bodyElement.classList.contains('dark-mode');
            Swal.fire({
                title: status === 'success' ? 'Success!' : 'Error',
                text: decodeURIComponent(msg),
                icon: status,
                background: isDarkModeActive ? '#1E1E1E' : '#ffffff',
                color: isDarkModeActive ? '#F7FAFC' : '#1A202C',
                confirmButtonColor: '#2D6A4F'
            }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    </script>
</body>
</html>