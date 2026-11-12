<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Campus-only access: location must be verified (and match the account's campus)
require_once __DIR__ . '/require_campus.php';

// 1. Generate CSRF Token for Secure form submissions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Guard check: If the user isn't logged in, send them back to login
if (!isset($_SESSION['user_token'])) {
    header('Location: login.php');
    exit;
}

// --- NEON POSTGRESQL DATABASE CONFIGURATION ---
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

// --- NOTIFICATION HELPER (writes to the same `notifications` table the bell reads from) ---
function cw_push_notification($pdo, $user_id, $title, $message, $type = 'info') {
    if (empty($pdo) || empty($user_id)) { return false; }
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, created_at, updated_at)
            VALUES (:user_id, :title, :message, :type, FALSE, NOW(), NOW())
        ");
        $stmt->execute([
            ':user_id' => $user_id,
            ':title'   => $title,
            ':message' => $message,
            ':type'    => $type
        ]);
        return true;
    } catch (\PDOException $e) {
        return false;
    }
}

// 3. Extract user session data strictly using user_id
$user_data = $_SESSION['user_profile'] ?? ($_SESSION['user_data'] ?? []);
$user_id   = $_SESSION['user_id'] ?? ($user_data['id'] ?? null); 
$user_metadata = $user_data['user_metadata'] ?? [];

// Redirect if user_id is missing from session
if (empty($user_id)) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user_email'] ?? ($user_data['email'] ?? ($user_metadata['email'] ?? 'Unknown Email'));
$user_name  = $_SESSION['user_name'] ?? ($user_data['full_name'] ?? ($user_metadata['full_name'] ?? ($user_metadata['name'] ?? '')));

// 4. Extract Name & Role
$raw_name = $user_name;
if (isset($user_data['role']) && strtolower($user_data['role']) !== 'authenticated') {
    $raw_role = $user_data['role'];
} else {
    $raw_role = $user_metadata['role'] ?? '';
}

// 5. Extract Department & Campus from session user data
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

// --- POST INTERCEPTOR PIPES & CONTROLLERS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // CSRF Guard verification
    $provided_token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $provided_token)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'CSRF verification token expired or mismatched.']);
        exit;
    }

    // Process Avatar Upload strictly bound to user_id
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

        // --- LOCAL FILE STORAGE PIPELINE ---
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
                cw_push_notification($pdo, $user_id, 'Profile photo updated 📸', 'Your new profile picture now shows on every CarbonWise page.', 'success');
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
}

// 6. DYNAMICALLY CALCULATE CARBON SCORE & SUSTAINABILITY LEVEL FROM carbon_records STRICTLY BY user_id
$current_week_score   = 0;
$last_week_score      = 0;
$grand_total          = 0;
$total_records_count  = 0;
$month_avg_score      = 0;
$sustainability_level = "New Explorer";
$sustainability_desc  = "Start logging activity to earn your sustainability badge!";
$trend_text           = "Starting your green track!";
$current_month_name   = date('F');
$info_http_code       = 500;
$info_response        = !empty($db_connection_error) ? $db_connection_error : "";

if ($pdo) {
    try {
        // Query profile_picture by user_id
        $avatar_stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = :user_id LIMIT 1");
        $avatar_stmt->execute([':user_id' => $user_id]);
        $user_row = $avatar_stmt->fetch();
        if ($user_row && !empty($user_row['profile_picture'])) {
            $_SESSION['user_profile']['user_metadata']['profile_picture'] = $user_row['profile_picture'];
        }

        // Aggregate emissions data matching strictly by user_id
        $score_stmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS record_count,
                COALESCE(SUM(CAST(REGEXP_REPLACE(COALESCE(total_emission::text, '0'), '[^0-9.]', '', 'g') AS NUMERIC)), 0) AS grand_total,
                COALESCE(SUM(CASE WHEN created_at >= NOW() - INTERVAL '7 days' THEN CAST(REGEXP_REPLACE(COALESCE(total_emission::text, '0'), '[^0-9.]', '', 'g') AS NUMERIC) ELSE 0 END), 0) AS current_week,
                COALESCE(SUM(CASE WHEN created_at >= NOW() - INTERVAL '14 days' AND created_at < NOW() - INTERVAL '7 days' THEN CAST(REGEXP_REPLACE(COALESCE(total_emission::text, '0'), '[^0-9.]', '', 'g') AS NUMERIC) ELSE 0 END), 0) AS last_week,
                COALESCE(AVG(CASE WHEN created_at >= NOW() - INTERVAL '30 days' THEN CAST(REGEXP_REPLACE(COALESCE(total_emission::text, '0'), '[^0-9.]', '', 'g') AS NUMERIC) END), 0) AS month_avg
            FROM carbon_records 
            WHERE user_id = :user_id
        ");
        $score_stmt->execute([':user_id' => $user_id]);
        $score_data = $score_stmt->fetch();

        if ($score_data) {
            $info_http_code = 200;
            $info_response  = json_encode($score_data);

            $total_records_count = intval($score_data['record_count']);
            $grand_total = floatval($score_data['grand_total']);
            $weekly_sum  = floatval($score_data['current_week']);
            
            // Current week emissions
            $current_week_score = ($weekly_sum > 0) ? round($weekly_sum, 1) : round($grand_total, 1);
            $last_week_score    = floatval($score_data['last_week']);
            $month_avg_score    = floatval($score_data['month_avg']) > 0 ? floatval($score_data['month_avg']) : ($grand_total / max(1, $total_records_count));

            // --- CALCULATE SUSTAINABILITY LEVEL DYNAMICALLY FROM DATABASE SCORE ---
            if ($total_records_count === 0) {
                $sustainability_level = "New Explorer";
                $sustainability_desc  = "Log your first activity entry to compute your level.";
            } elseif ($current_week_score <= 15.0) {
                $sustainability_level = "Green Advocate";
                $sustainability_desc  = "Great job! You're maintaining sustainable habits.";
            } elseif ($current_week_score <= 30.0) {
                $sustainability_level = "Conscious User";
                $sustainability_desc  = "Keep it up! You are maintaining solid green habits.";
            } else {
                $sustainability_level = "Emerging Eco-Learner";
                $sustainability_desc  = "Consider trying out mitigation strategies to reduce emissions.";
            }

            // Notify when the sustainability level changes (visible in the bell on every page)
            try {
                $old_level_stmt = $pdo->prepare("SELECT sustainability_level FROM users WHERE id = :user_id");
                $old_level_stmt->execute([':user_id' => $user_id]);
                $old_level = $old_level_stmt->fetchColumn();
                if (!empty($old_level) && $old_level !== $sustainability_level && $total_records_count > 0) {
                    $level_rank = ['New Explorer' => 0, 'Emerging Eco-Learner' => 1, 'Conscious User' => 2, 'Green Advocate' => 3];
                    $improved   = ($level_rank[$sustainability_level] ?? 0) > ($level_rank[$old_level] ?? 0);
                    cw_push_notification(
                        $pdo, $user_id,
                        $improved ? 'Sustainability level up! 🌱' : 'Sustainability level changed',
                        "Your level changed from {$old_level} to {$sustainability_level}. {$sustainability_desc}",
                        $improved ? 'success' : 'info'
                    );
                }
            } catch (PDOException $e) {
                // Level column may not exist yet; skip silently
            }

            // Sync calculated score and sustainability level back to users table
            try {
                $update_stmt = $pdo->prepare("
                    UPDATE users 
                    SET carbon_score = :score, sustainability_level = :level 
                    WHERE id = :user_id
                ");
                $update_stmt->execute([
                    ':score'   => $current_week_score,
                    ':level'   => $sustainability_level,
                    ':user_id' => $user_id
                ]);
            } catch (PDOException $e) {
                $update_stmt = $pdo->prepare("UPDATE users SET carbon_score = :score WHERE id = :user_id");
                $update_stmt->execute([
                    ':score'   => $current_week_score,
                    ':user_id' => $user_id
                ]);
            }
        }

        // --- DYNAMIC TREND TEXT CALCULATION ---
        if ($total_records_count === 0) {
            $trend_text = "Starting your green track!";
        } else {
            if ($last_week_score > 0) {
                if ($current_week_score < $last_week_score) {
                    $percentage = round((($last_week_score - $current_week_score) / $last_week_score) * 100);
                    $trend_text = "{$percentage}% less than last week";
                } elseif ($current_week_score > $last_week_score) {
                    $percentage = round((($current_week_score - $last_week_score) / $last_week_score) * 100);
                    $trend_text = "{$percentage}% more than last week";
                } else {
                    $trend_text = "Same emissions as last week";
                }
            } else {
                $trend_text = "1st week of tracking logged!";
            }
        }

    } catch (PDOException $e) {
        $info_response = "Error calculating carbon score: " . $e->getMessage();
    }
}

// 7. DYNAMICALLY COMPUTE CATEGORY BREAKDOWNS
$categories_data = [
    'transportation'   => ['current_week' => 0, 'top_activity' => 'Commuting Daily', 'icon' => 'fa-bus'],
    'office_resource'  => ['current_week' => 0, 'top_activity' => 'Electricity & Office Usage', 'icon' => 'fa-bolt'],
    'food_consumption' => ['current_week' => 0, 'top_activity' => 'Meals & Food Log', 'icon' => 'fa-utensils']
];

if ($pdo) {
    try {
        $col_stmt = $pdo->prepare("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name = 'carbon_records'
        ");
        $col_stmt->execute();
        $existing_columns = $col_stmt->fetchAll(PDO::FETCH_COLUMN);

        $category_patterns = [
            'transportation'   => ['transport', 'commute', 'travel', 'vehicle'],
            'office_resource'  => ['office', 'elec', 'energy', 'resource', 'power', 'utility'],
            'food_consumption' => ['food', 'meal', 'diet', 'consumption', 'eat']
        ];

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

        $dynamic_sql = "SELECT " . implode(', ', $dynamic_selects) . " FROM carbon_records WHERE user_id = :user_id";
        $breakdown_stmt = $pdo->prepare($dynamic_sql);
        $breakdown_stmt->execute([':user_id' => $user_id]);
        $totals = $breakdown_stmt->fetch();

        if ($totals) {
            $categories_data['transportation']['current_week']   = floatval($totals['transportation_sum'] ?? 0);
            $categories_data['office_resource']['current_week']  = floatval($totals['office_resource_sum'] ?? 0);
            $categories_data['food_consumption']['current_week'] = floatval($totals['food_consumption_sum'] ?? 0);
        }

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

// 8. CALCULATE MOBILE ACHIEVEMENT STATES
$trans_score = floatval($categories_data['transportation']['current_week']);
$office_score= floatval($categories_data['office_resource']['current_week']);
$food_score  = floatval($categories_data['food_consumption']['current_week']);

$achievements = [
    [
        'title'       => 'First Footprint',
        'unlocked_sub'=> "You've recorded your first carbon activity!",
        'locked_sub'  => "Log your first carbon emission.",
        'icon'        => 'fa-leaf',
        'unlocked'    => $total_records_count > 0,
    ],
    [
        'title'       => 'Eco Commuter',
        'unlocked_sub'=> "Transportation emissions kept below 5 kg CO₂.",
        'locked_sub'  => "Keep transportation emissions below 5 kg CO₂.",
        'icon'        => 'fa-bicycle',
        'unlocked'    => $trans_score > 0 && $trans_score <= 5.0,
    ],
    [
        'title'       => 'Energy Saver',
        'unlocked_sub'=> "You kept your office resource emissions low!",
        'locked_sub'  => "Keep your Office Resource emissions below 10 kg CO₂.",
        'icon'        => 'fa-bolt',
        'unlocked'    => $office_score > 0 && $office_score <= 10.0,
    ],
    [
        'title'       => 'Plant Lover',
        'unlocked_sub'=> "Excellent food choices this month!",
        'locked_sub'  => "Keep your Food Consumption emissions below 8 kg CO₂.",
        'icon'        => 'fa-tree',
        'unlocked'    => $food_score > 0 && $food_score <= 8.0,
    ],
    [
        'title'       => 'Eco Champion',
        'unlocked_sub'=> "Outstanding carbon footprint!",
        'locked_sub'  => "Keep your total carbon emission below 20 kg CO₂.",
        'icon'        => 'fa-award',
        'unlocked'    => $current_week_score > 0 && $current_week_score <= 20.0,
    ],
];

// 8b. MONTHLY CERTIFICATES: scored on each calendar month's overall activity, unlocked at month end
const CW_CERT_WINDOW_DAYS = 3;     // certificate of the running month unlocks during the last N days
const CW_CERT_MIN_ACTIVITIES = 3;  // minimum logged activities in a month to qualify
const CW_CERT_ECO_MONTHLY_KG = 80; // "Eco Champion": month total at or below this (kg CO2e)

$certificates     = [];   // earned, available certificates (newest first)
$cert_current     = null; // status of the running month
$cert_has_records = ($total_records_count > 0);

$cw_eval_cert = function ($total, $count, $prev_total, $better, $users, $label) {
    if ($count < CW_CERT_MIN_ACTIVITIES || $total <= 0) { return null; }
    $top = ($users > 0) ? max(1, (int) ceil((($better + 1) / $users) * 100)) : null;
    $reduction = ($prev_total !== null && $prev_total > 0 && $total < $prev_total)
        ? (int) round((($prev_total - $total) / $prev_total) * 100) : 0;
    $summary = "Logged {$count} activities totaling " . number_format($total, 1) . " kg CO₂e in {$label}";

    if ($top !== null && $users >= 2 && $top <= 10) {
        return ['tier' => 'Top 10%', 'headline' => 'achieving a ranking of', 'reason' => $summary . '. One of the lowest footprints on campus.'];
    }
    if ($top !== null && $users >= 2 && $top <= 25) {
        return ['tier' => 'Top 25%', 'headline' => 'achieving a ranking of', 'reason' => $summary . '. Lower than most users on campus.'];
    }
    if ($reduction > 0) {
        return ['tier' => $reduction . '% Less CO₂e', 'headline' => 'lowering your monthly emissions by', 'reason' => $summary . '. Down from ' . number_format($prev_total, 1) . ' kg the month before.'];
    }
    if ($total <= CW_CERT_ECO_MONTHLY_KG) {
        return ['tier' => 'Eco Champion', 'headline' => 'keeping your monthly emissions low and earning the title of', 'reason' => $summary . ' (' . CW_CERT_ECO_MONTHLY_KG . ' kg or below).'];
    }
    return null;
};

// Safe default so the banner always has a sensible message, even if the query below fails
$cw_days_total = (int) date('t');
$cw_unlock_day = $cw_days_total - CW_CERT_WINDOW_DAYS + 1;
$cert_current  = [
    'status' => ((int) date('j') >= $cw_unlock_day) ? 'missed' : 'pending',
    'unlock' => date('F') . ' ' . $cw_unlock_day . ', ' . date('Y'),
    'on_track' => null, 'activities' => 0, 'total' => 0,
];

if ($pdo && $cert_has_records) {
    try {
        $min_a = (int) CW_CERT_MIN_ACTIVITIES;
        $monthly_stmt = $pdo->prepare("
            WITH per AS (
                SELECT user_id,
                       DATE_TRUNC('month', created_at) AS m,
                       COALESCE(SUM(CAST(NULLIF(REGEXP_REPLACE(COALESCE(total_emission::text, '0'), '[^0-9.]', '', 'g'), '') AS NUMERIC)), 0) AS total,
                       COUNT(*) AS cnt
                FROM carbon_records
                WHERE created_at IS NOT NULL
                GROUP BY user_id, DATE_TRUNC('month', created_at)
            )
            SELECT TO_CHAR(per.m, 'YYYY-MM') AS ym, per.total, per.cnt,
                   (SELECT COUNT(*) FROM per p2 WHERE p2.m = per.m AND p2.cnt >= {$min_a}) AS users_in_month,
                   (SELECT COUNT(*) FROM per p2 WHERE p2.m = per.m AND p2.cnt >= {$min_a} AND p2.total < per.total) AS better
            FROM per
            WHERE per.user_id = :uid
            ORDER BY per.m ASC
        ");
        $monthly_stmt->execute([':uid' => $user_id]);
        $monthly_rows = $monthly_stmt->fetchAll();

        $current_ym      = date('Y-m');
        $days_in_month   = (int) date('t');
        $window_start    = $days_in_month - CW_CERT_WINDOW_DAYS + 1;
        $in_window       = ((int) date('j') >= $window_start);
        $unlock_label    = date('F', strtotime('first day of this month')) . ' ' . $window_start . ', ' . date('Y');
        $prev_by_month   = [];
        foreach ($monthly_rows as $r) { $prev_by_month[$r['ym']] = floatval($r['total']); }

        foreach ($monthly_rows as $r) {
            $ym        = $r['ym'];
            $first_day = DateTime::createFromFormat('Y-m-d', $ym . '-01');
            $label     = $first_day->format('F Y');
            $issued    = (clone $first_day)->modify('last day of this month')->format('F j, Y');
            $prev_ym   = (clone $first_day)->modify('-1 month')->format('Y-m');
            $prev_tot  = $prev_by_month[$prev_ym] ?? null;

            $result = $cw_eval_cert(floatval($r['total']), intval($r['cnt']), $prev_tot, intval($r['better']), intval($r['users_in_month']), $label);

            if ($ym === $current_ym) {
                if ($result && $in_window) {
                    $cert_current = ['status' => 'available'];
                } elseif ($in_window) {
                    $cert_current = ['status' => 'missed', 'activities' => intval($r['cnt']), 'total' => floatval($r['total'])];
                    $result = null;
                } else {
                    $cert_current = [
                        'status'   => 'pending',
                        'unlock'   => $unlock_label,
                        'on_track' => $result ? $result['tier'] : null,
                        'activities' => intval($r['cnt']),
                        'total'    => floatval($r['total']),
                    ];
                    $result = null;
                }
                $issued = (clone $first_day)->modify('last day of this month')->format('F j, Y');
            }

            if ($result) {
                $certificates[] = [
                    'key'      => $ym,
                    'month'    => $first_day->format('F'),
                    'year'     => $first_day->format('Y'),
                    'label'    => $label,
                    'name'     => $full_name,
                    'tier'     => $result['tier'],
                    'headline' => $result['headline'],
                    'reason'   => $result['reason'],
                    'issued'   => $issued,
                    'activities' => intval($r['cnt']),
                    'total'    => round(floatval($r['total']), 1),
                    'file'     => 'CarbonWise_Certificate_' . preg_replace('/[^A-Za-z0-9]+/', '_', $full_name) . '_' . $ym,
                ];
            }
        }
        $certificates = array_reverse($certificates);

    } catch (PDOException $e) {
        $certificates = [];
    }
}

// 9. FETCH RECENT ACTIVITY TIMELINE
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

// Avatar URL retrieval using profile_picture
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
            --text-muted: #8E8E93;
            --text-sidebar-menu: #D8F3DC;
            --border-color: #E2E8F0;
            --accent-green: #2D6A4F;
            --accent-green-hover: #1B4332;
            --bell-bg: #E8F5E9;
            --achieve-locked-bg: #F8FAFC;
            --sustain-green: #278A5B;
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
            --sustain-green: #38A169;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { display: flex; height: 100vh; background-color: var(--bg-body); color: var(--text-main); overflow: hidden; transition: background-color 0.3s, color 0.3s; }

        .sidebar { width: 260px; background-color: var(--bg-sidebar); color: white; display: flex; flex-direction: column; padding: 25px 0; flex-shrink: 0; transition: all 0.3s ease; z-index: 1050; }
        .logo-section { display: flex; align-items: center; padding: 0 25px; margin-bottom: 40px; gap: 12px; }
        .brand-logo-container { width: 38px; height: 38px; border-radius: 50%; background-color: white; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
        .brand-logo-container img { width: 85%; height: 85%; object-fit: contain; }
        .logo-text { font-size: 1.15rem; font-weight: 800; letter-spacing: 0.5px; color: #ffffff; }
        
        .menu-items { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .menu-item { display: flex; align-items: center; padding: 14px 25px; color: var(--text-sidebar-menu); text-decoration: none; font-size: 0.95rem; font-weight: 500; cursor: pointer; gap: 15px; transition: background-color 0.3s, color 0.3s; }
        .menu-item i { width: 20px; font-size: 1.1rem; text-align: center; }
        .menu-item:hover, .menu-item.active { background-color: var(--bg-sidebar-hover); color: #ffffff; }
        .menu-item.active { font-weight: 600; border-left: 4px solid #74C69D; padding-left: 21px; }
        
        .sidebar-footer { margin-top: auto; }
        .sidebar-divider { height: 1px; background-color: rgba(255, 255, 255, 0.1); margin: 15px 25px; }

        .main-workspace { flex: 1; display: flex; flex-direction: column; overflow: hidden; position: relative; width: 100%; }
        .top-navbar { height: 85px; background: var(--bg-card); display: flex; align-items: center; justify-content: space-between; padding: 0 40px; border-bottom: 1px solid var(--border-color); flex-shrink: 0; transition: background-color 0.3s, border-color 0.3s; gap: 15px; }
        
        .menu-toggle-btn { display: none; background: transparent; border: none; font-size: 1.4rem; color: var(--text-title); cursor: pointer; padding: 5px; }
        .nav-left-wrapper { display: flex; align-items: center; gap: 15px; }

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
        .nav-avatar { width: 44px; height: 44px; background: #E2E8F0; color: var(--accent-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; overflow: hidden; flex-shrink: 0; }
        .nav-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .workspace-body { flex: 1; overflow-y: auto; padding: 30px 40px; display: flex; flex-direction: column; gap: 20px; }

        .dash-card { background: var(--bg-card); border-radius: 12px; padding: 20px; border: 1px solid var(--border-color); position: relative; transition: background-color 0.3s, border-color 0.3s; }
        .dash-card-title { font-size: 0.95rem; font-weight: 700; color: var(--text-title); margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
        .card-link { font-size: 0.85rem; color: var(--accent-green); text-decoration: none; font-weight: 600; }

        .profile-main-box { display: flex; align-items: center; gap: 20px; }
        .avatar-uploader { position: relative; cursor: pointer; border-radius: 50%; width: 75px; height: 75px; overflow: hidden; background: #EDF2F7; border: 3px solid var(--accent-green); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: 700; color: var(--accent-green); flex-shrink: 0; }
        .avatar-uploader img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-overlay { position: absolute; inset: 0; background: rgba(45, 106, 79, 0.8); display: flex; align-items: center; justify-content: center; color: white; opacity: 0; transition: opacity 0.2s; font-size: 1rem; }
        .avatar-uploader:hover .avatar-overlay { opacity: 1; }
        
        .profile-text-details h3 { font-size: 1.25rem; font-weight: 700; color: var(--text-title); word-break: break-word; }
        .profile-text-details .role-tag { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; margin-top: 2px; }
        .profile-text-details .sub-details { font-size: 0.8rem; color: var(--text-muted); margin-top: 3px; word-break: break-word; }

        /* --- SUSTAINABILITY CONTAINER --- */
        .sustainability-wrapper {
            background: var(--bg-card);
            border-radius: 10px;
            padding: 16px 20px;
            border: 1px solid var(--border-color);
        }

        .sustainability-header-title {
            font-size: 0.68rem;
            font-weight: 800;
            color: #718096;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        .sustainability-item {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .sustainability-divider {
            height: 1px;
            background-color: #E2E8F0;
            margin: 14px 0;
        }

        body.dark-mode .sustainability-divider {
            background-color: #2D3748;
        }

        /* Carbon Score Left Circle Icon */
        .icon-circle-filled {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: #E6F4EA;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #278A5B;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        body.dark-mode .icon-circle-filled {
            background-color: #123321;
            color: #38A169;
        }

        /* Sustainability Ring Gauge Icon */
        .ring-gauge-container {
            width: 44px;
            height: 44px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ring-gauge-container svg {
            width: 44px;
            height: 44px;
            transform: rotate(-90deg);
        }

        .ring-gauge-container circle {
            fill: none;
            stroke-width: 4;
        }

        .ring-gauge-bg {
            stroke: #E2E8F0;
        }

        body.dark-mode .ring-gauge-bg {
            stroke: #2D3748;
        }

        .ring-gauge-fill {
            stroke: #278A5B;
            stroke-dasharray: 100.5;
            stroke-dashoffset: 30.1;
            stroke-linecap: round;
        }

        body.dark-mode .ring-gauge-fill {
            stroke: #38A169;
        }

        .ring-gauge-icon {
            position: absolute;
            color: #278A5B;
            font-size: 0.95rem;
        }

        body.dark-mode .ring-gauge-icon {
            color: #38A169;
        }

        .sustainability-details {
            display: flex;
            flex-direction: column;
        }

        .sustainability-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #4A5568;
            margin-bottom: 2px;
        }

        body.dark-mode .sustainability-label {
            color: #CBD5E0;
        }

        .carbon-score-val-row {
            display: flex;
            align-items: baseline;
            gap: 5px;
            flex-wrap: wrap;
        }

        .carbon-score-num {
            font-size: 1.45rem;
            font-weight: 800;
            color: #1A202C;
            line-height: 1;
        }

        body.dark-mode .carbon-score-num {
            color: #F7FAFC;
        }

        .carbon-score-unit {
            font-size: 0.75rem;
            color: #A0AEC0;
            font-weight: 500;
        }

        .carbon-trend-text {
            font-size: 0.72rem;
            color: #278A5B;
            font-weight: 700;
            margin-top: 3px;
        }

        body.dark-mode .carbon-trend-text {
            color: #38A169;
        }

        .sustainability-level-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #278A5B;
            margin-bottom: 2px;
        }

        body.dark-mode .sustainability-level-title {
            color: #38A169;
        }

        .sustainability-subdesc {
            font-size: 0.75rem;
            color: #A0AEC0;
            font-weight: 500;
        }

        .sustainability-month-avg {
            font-size: 0.72rem;
            color: #A0AEC0;
            font-style: italic;
            margin-top: 2px;
        }

        body.dark-mode .sustainability-month-avg {
            color: #718096;
        }

        /* --- BREAKDOWN TABS & CONTENT (FIXED FULL FIT NO TRUNCATION) --- */
        .breakdown-tabs { 
            display: flex; 
            justify-content: space-between;
            align-items: center;
            gap: 10px; 
            border-bottom: 2px solid var(--border-color); 
            padding-bottom: 8px; 
            margin-bottom: 16px; 
            width: 100%;
            overflow-x: auto; 
            scrollbar-width: none; /* Hide scrollbar for seamless look */
            -ms-overflow-style: none;
        }

        .breakdown-tabs::-webkit-scrollbar {
            display: none;
        }

        .tab-item { 
            font-size: clamp(0.72rem, 2.2vw, 0.85rem); 
            font-weight: 700; 
            color: var(--text-muted); 
            cursor: pointer; 
            text-decoration: none; 
            padding: 0 4px 8px 4px; 
            white-space: nowrap;
            text-align: center;
            flex: 1 1 auto;
            transition: color 0.2s, border-color 0.2s;
        }
        
        .tab-item.active { 
            color: var(--accent-green); 
            border-bottom: 3px solid var(--accent-green); 
        }

        .breakdown-body { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
            flex-wrap: wrap; 
            gap: 15px; 
            min-height: auto; 
        }

        .dashboard-grid { display: grid; grid-template-columns: 1fr; gap: 20px; width: 100%; min-width: 0; }
        .single-column-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }

        /* --- HORIZONTALLY SCROLLABLE ACHIEVEMENTS CARD CONTAINER --- */
        .dash-card.achievements-card {
            overflow: hidden;
        }

        .achievements-scroll-wrapper {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            overflow-y: hidden;
            width: 100%;
            padding-bottom: 8px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }
        .achievements-scroll-wrapper::-webkit-scrollbar {
            height: 6px;
        }
        .achievements-scroll-wrapper::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.03);
            border-radius: 10px;
        }
        .achievements-scroll-wrapper::-webkit-scrollbar-thumb {
            background: #CBD5E0;
            border-radius: 10px;
        }

        .achieve-card-item {
            min-width: 180px;
            width: 180px;
            max-width: 180px;
            height: 140px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 12px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            flex-shrink: 0;
            background: var(--bg-card);
            transition: all 0.2s ease-in-out;
        }

        .achieve-card-item.unlocked {
            border-color: #2D6A4F;
            background: rgba(45, 106, 79, 0.03);
        }

        .achieve-card-item.locked {
            opacity: 0.65;
            background: var(--achieve-locked-bg);
        }

        .achieve-icon-box {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            margin-bottom: 2px;
        }

        .achieve-card-item.unlocked .achieve-icon-box {
            background: #D8F3DC;
            color: #2D6A4F;
        }

        .achieve-card-item.locked .achieve-icon-box {
            background: #E2E8F0;
            color: #A0AEC0;
        }

        .achieve-card-item h5 {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-title);
            margin: 0;
        }

        .achieve-card-item p {
            font-size: 0.7rem;
            color: var(--text-muted);
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin: 0;
        }

        .timeline-box { display: flex; flex-direction: column; gap: 12px; }
        .timeline-item { display: flex; gap: 12px; position: relative; font-size: 0.82rem; align-items: center; }
        .timeline-icon { width: 30px; height: 30px; background: var(--bell-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--accent-green); z-index: 2; flex-shrink: 0; font-size: 0.85rem; }
        .timeline-details { flex: 1; min-width: 0; }
        .timeline-details h5 { font-weight: 700; font-size: 0.82rem; color: var(--text-title); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .timeline-details p { color: var(--text-muted); font-size: 0.72rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .timeline-right { text-align: right; font-weight: 700; color: var(--text-title); font-size: 0.82rem; flex-shrink: 0; }
        .timeline-right span { display: block; font-size: 0.68rem; color: var(--text-muted); font-weight: 400; }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        /* --- CERTIFICATE OF RECOGNITION --- */
        .cert-section-sub { font-size: 0.82rem; color: var(--text-muted); margin: -4px 0 16px; line-height: 1.5; }
        .cert-frame { border: 3px solid #95D5B2; border-radius: 24px; padding: 28px 20px 22px; text-align: center; background: var(--bg-card); }
        .cert-medal { width: 58px; height: 58px; margin: 0 auto 14px; border-radius: 50%; background: #E6F4EA; color: #2D8F5E; display: flex; align-items: center; justify-content: center; font-size: 1.7rem; }
        .cert-kicker { font-size: 0.85rem; font-weight: 700; letter-spacing: 3px; color: var(--accent-green); text-transform: uppercase; }
        .cert-presented { font-size: 0.8rem; color: var(--text-muted); margin-top: 20px; }
        .cert-name { font-size: 1.7rem; font-weight: 800; color: var(--text-title); margin: 6px 0 14px; word-break: break-word; }
        .cert-for { font-size: 0.8rem; color: var(--text-muted); max-width: 420px; margin: 0 auto; line-height: 1.5; }
        .cert-tier-pill { display: inline-block; margin-top: 12px; padding: 10px 22px; border-radius: 999px; background: #E6F4EA; color: #2D8F5E; font-weight: 800; font-size: 1.05rem; }
        body.dark-mode .cert-tier-pill, body.dark-mode .cert-medal { background: #123321; color: #38A169; }
        .cert-reason { font-size: 0.74rem; color: var(--text-muted); margin-top: 10px; }
        .cert-foot { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 26px; text-align: left; gap: 16px; }
        .cert-foot small { display: block; font-size: 0.68rem; color: var(--text-muted); }
        .cert-foot strong { font-size: 0.82rem; color: var(--text-title); }
        .cert-sign { min-width: 130px; text-align: center; border-bottom: 1px solid var(--border-color); padding-bottom: 4px; font-style: italic; font-weight: 700; font-size: 0.8rem; color: var(--accent-green); }
        .cert-download-btn { width: 100%; margin-top: 16px; padding: 15px; border: none; border-radius: 14px; background: #1B4332; color: #fff; font-size: 0.95rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: background-color 0.2s; }
        .cert-download-btn:hover { background: #2D6A4F; }
        .cert-locked { text-align: center; padding: 26px 16px; border: 2px dashed var(--border-color); border-radius: 18px; background: var(--achieve-locked-bg); }
        .cert-locked i { font-size: 1.8rem; color: var(--text-muted); margin-bottom: 10px; }
        .cert-locked h4 { font-size: 0.95rem; color: var(--text-title); margin-bottom: 6px; }
        .cert-locked p { font-size: 0.8rem; color: var(--text-muted); line-height: 1.55; max-width: 460px; margin: 0 auto; }

        .cert-banner { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px; border-radius: 14px; background: var(--achieve-locked-bg); border: 1px solid var(--border-color); margin-bottom: 16px; font-size: 0.82rem; color: var(--text-main); line-height: 1.5; }
        .cert-banner i { color: var(--accent-green); font-size: 1.1rem; margin-top: 2px; }
        .cert-banner strong { color: var(--text-title); }
        .cert-month-chip { display: inline-block; font-size: 0.78rem; font-weight: 700; color: var(--accent-green); background: var(--bell-bg); padding: 5px 14px; border-radius: 999px; margin-top: 14px; }
        .cert-compilation-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin: 26px 0 12px; flex-wrap: wrap; }
        .cert-compilation-head h4 { font-size: 0.95rem; color: var(--text-title); }
        .cert-compilation-head span { font-size: 0.75rem; color: var(--text-muted); font-weight: 500; margin-left: 6px; }
        .cert-all-btn { background: transparent; border: 1.5px solid var(--accent-green); color: var(--accent-green); font-weight: 600; font-size: 0.78rem; padding: 8px 14px; border-radius: 10px; cursor: pointer; display: inline-flex; gap: 8px; align-items: center; }
        .cert-all-btn:hover { background: var(--bell-bg); }
        .cert-list { display: flex; flex-direction: column; gap: 10px; }
        .cert-row { display: flex; align-items: center; gap: 14px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 14px; background: var(--bg-card); }
        .cert-row-icon { width: 42px; height: 42px; border-radius: 50%; background: #E6F4EA; color: #2D8F5E; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        body.dark-mode .cert-row-icon { background: #123321; color: #38A169; }
        .cert-row-info { flex: 1; min-width: 0; }
        .cert-row-info h5 { font-size: 0.88rem; color: var(--text-title); }
        .cert-row-info p { font-size: 0.72rem; color: var(--text-muted); margin-top: 2px; }
        .cert-row-tier { font-size: 0.74rem; font-weight: 800; color: #2D8F5E; background: #E6F4EA; padding: 4px 10px; border-radius: 999px; white-space: nowrap; }
        body.dark-mode .cert-row-tier { background: #123321; color: #38A169; }
        .cert-row-dl { background: #1B4332; color: #fff; border: none; width: 38px; height: 38px; border-radius: 10px; cursor: pointer; flex-shrink: 0; }
        .cert-row-dl:hover { background: #2D6A4F; }
        @media (max-width: 600px) { .cert-row { flex-wrap: wrap; } .cert-row-tier { order: 3; } }

        /* --- RESPONSIVE MEDIA QUERIES --- */
        @media (max-width: 1024px) {
            .top-navbar { padding: 0 25px; }
            .workspace-body { padding: 25px; }
        }

        @media (max-width: 850px) {
            .sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                bottom: 0;
                box-shadow: 4px 0 15px rgba(0,0,0,0.2);
            }
            .sidebar.active {
                left: 0;
            }
            .sidebar-overlay.active {
                display: block;
                opacity: 1;
            }
            .menu-toggle-btn {
                display: block;
            }
            .top-navbar {
                height: 70px;
                padding: 0 20px;
            }
            .workspace-body {
                padding: 20px;
            }
            .header-profile-box div {
                display: none;
            }
            .header-profile-box {
                padding-left: 10px;
                border-left: none;
            }
        }

        @media (max-width: 600px) {
            .top-navbar {
                padding: 0 15px;
            }
            .page-heading h2 {
                font-size: 1.2rem;
            }
            .page-heading p {
                display: none;
            }
            .workspace-body {
                padding: 15px;
                gap: 15px;
            }
            .profile-main-box {
                flex-direction: column;
                text-align: center;
                gap: 12px;
            }
            .notification-dropdown {
                right: -60px;
                width: 300px;
            }
            .sustainability-item {
                flex-direction: row;
                align-items: flex-start;
            }
            .dash-card {
                padding: 15px;
            }
            .breakdown-tabs {
                justify-content: flex-start;
            }
            .tab-item {
                padding: 0 8px 8px 8px;
            }
        }

        @media (max-width: 400px) {
            .notification-dropdown {
                right: -100px;
                width: 280px;
            }
            .timeline-item {
                gap: 8px;
            }
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
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1B4332">
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="appSidebar">
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
            <div class="nav-left-wrapper">
                <button class="menu-toggle-btn" id="menuToggleBtn" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="page-heading">
                    <h2>View Profile</h2>
                    <p>Update your personal details and organization info</p>
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
            <!-- USER PROFILE INFO CARD -->
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

            <!-- COMPACT DYNAMIC SUSTAINABILITY CARD -->
            <div class="sustainability-wrapper">
                <div class="sustainability-header-title">
                    YOUR SUSTAINABILITY
                </div>

                <!-- Carbon Score Section -->
                <div class="sustainability-item">
                    <div class="icon-circle-filled">
                        <i class="fa-solid fa-leaf"></i>
                    </div>
                    <div class="sustainability-details">
                        <div class="sustainability-label">Carbon Score</div>
                        <div class="carbon-score-val-row">
                            <span class="carbon-score-num"><?= number_format($current_week_score, 1); ?></span>
                            <span class="carbon-score-unit">kg CO₂ / week</span>
                        </div>
                        <div class="carbon-trend-text"><?= htmlspecialchars($trend_text); ?></div>
                    </div>
                </div>

                <div class="sustainability-divider"></div>

                <!-- Sustainability Level Section -->
                <div class="sustainability-item">
                    <div class="ring-gauge-container">
                        <svg viewBox="0 0 40 40">
                            <circle class="ring-gauge-bg" cx="20" cy="20" r="16"></circle>
                            <circle class="ring-gauge-fill" cx="20" cy="20" r="16"></circle>
                        </svg>
                        <i class="fa-solid fa-leaf ring-gauge-icon"></i>
                    </div>
                    <div class="sustainability-details">
                        <div class="sustainability-label">Sustainability Level</div>
                        <div class="sustainability-level-title"><?= htmlspecialchars($sustainability_level); ?></div>
                        <div class="sustainability-subdesc"><?= htmlspecialchars($sustainability_desc); ?></div>
                        <div class="sustainability-month-avg"><?= htmlspecialchars($current_month_name); ?> Average: <?= number_format($month_avg_score, 1); ?> kg CO₂</div>
                    </div>
                </div>
            </div>

            <!-- ACHIEVEMENTS SECTION -->
            <div class="dashboard-grid">
                <div class="dash-card achievements-card">
                    <div class="dash-card-title" style="font-size: 0.7rem; font-weight: 800; letter-spacing: 1.1px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">
                        Achievements
                    </div>

                    <div class="achievements-scroll-wrapper">
                        <?php foreach ($achievements as $ach): ?>
                            <div class="achieve-card-item <?= $ach['unlocked'] ? 'unlocked' : 'locked' ?>">
                                <div class="achieve-icon-box">
                                    <i class="fa-solid <?= $ach['icon'] ?>"></i>
                                </div>
                                <h5><?= htmlspecialchars($ach['title']) ?></h5>
                                <p><?= htmlspecialchars($ach['unlocked'] ? $ach['unlocked_sub'] : $ach['locked_sub']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- MONTHLY CERTIFICATES SECTION -->
            <div class="single-column-grid">
                <div class="dash-card">
                    <div class="dash-card-title" style="font-size: 0.7rem; font-weight: 800; letter-spacing: 1.1px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">
                        Monthly Recognition
                    </div>

                    <?php if (!$cert_has_records): ?>
                        <div class="cert-locked">
                            <i class="fa-solid fa-lock"></i>
                            <h4>No certificates yet</h4>
                            <p>Log at least <?= CW_CERT_MIN_ACTIVITIES ?> activities in a month to be considered for that month's certificate.</p>
                        </div>
                    <?php else: ?>

                        <?php $cc = $cert_current['status'] ?? 'pending'; ?>
                        <?php if ($cc === 'pending'): ?>
                            <div class="cert-banner">
                                <i class="fa-solid fa-hourglass-half"></i>
                                <div>
                                    <strong><?= htmlspecialchars(date('F Y')) ?> certificate unlocks on <?= htmlspecialchars($cert_current['unlock'] ?? '') ?>.</strong><br>
                                    So far this month: <?= intval($cert_current['activities'] ?? 0) ?> activities, <?= number_format($cert_current['total'] ?? 0, 1) ?> kg CO₂e.
                                    <?php if (!empty($cert_current['on_track'])): ?>
                                        You're currently on track for <strong><?= htmlspecialchars($cert_current['on_track']) ?></strong>.
                                    <?php else: ?>
                                        Keep logging (minimum <?= CW_CERT_MIN_ACTIVITIES ?> activities) and cut emissions to qualify.
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php elseif ($cc === 'missed'): ?>
                            <div class="cert-banner">
                                <i class="fa-solid fa-circle-info"></i>
                                <div>
                                    <strong>No <?= htmlspecialchars(date('F Y')) ?> certificate this month.</strong><br>
                                    Qualify with at least <?= CW_CERT_MIN_ACTIVITIES ?> logged activities and a Top 25% rank, lower emissions than last month, or a monthly total of <?= CW_CERT_ECO_MONTHLY_KG ?> kg CO₂e or below. Your earlier certificates stay below.
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="cert-section-sub">Congratulations! You've earned your <?= htmlspecialchars(date('F Y')) ?> certificate for your sustainability efforts.</p>
                        <?php endif; ?>

                        <?php if (!empty($certificates)): $latest = $certificates[0]; ?>
                            <div class="cert-frame">
                                <div class="cert-medal"><i class="fa-solid fa-award"></i></div>
                                <div class="cert-kicker">Certificate of Recognition</div>
                                <div class="cert-month-chip"><?= htmlspecialchars($latest['label']) ?></div>
                                <div class="cert-presented">This certificate is proudly presented to</div>
                                <div class="cert-name"><?= htmlspecialchars($latest['name']) ?></div>
                                <div class="cert-for">For outstanding dedication to reducing your carbon footprint and <?= htmlspecialchars($latest['headline']) ?></div>
                                <div class="cert-tier-pill"><?= htmlspecialchars($latest['tier']) ?></div>
                                <div class="cert-reason"><?= htmlspecialchars($latest['reason']) ?></div>
                                <div class="cert-foot">
                                    <div><small>Date Issued</small><strong><?= htmlspecialchars($latest['issued']) ?></strong></div>
                                    <div class="cert-sign">CarbonWise Team</div>
                                </div>
                            </div>
                            <button type="button" class="cert-download-btn" data-cert-index="0">
                                <i class="fa-solid fa-download"></i> Download <?= htmlspecialchars($latest['label']) ?> Certificate
                            </button>

                            <div class="cert-compilation-head">
                                <h4>Certificate Compilation <span><?= count($certificates) ?> earned</span></h4>
                                <?php if (count($certificates) > 1): ?>
                                    <button type="button" class="cert-all-btn" id="downloadAllCertsBtn"><i class="fa-solid fa-file-arrow-down"></i> Download all</button>
                                <?php endif; ?>
                            </div>
                            <div class="cert-list">
                                <?php foreach ($certificates as $i => $c): ?>
                                    <div class="cert-row">
                                        <div class="cert-row-icon"><i class="fa-solid fa-award"></i></div>
                                        <div class="cert-row-info">
                                            <h5><?= htmlspecialchars($c['label']) ?></h5>
                                            <p><?= intval($c['activities']) ?> activities · <?= number_format($c['total'], 1) ?> kg CO₂e · Issued <?= htmlspecialchars($c['issued']) ?></p>
                                        </div>
                                        <span class="cert-row-tier"><?= htmlspecialchars($c['tier']) ?></span>
                                        <button type="button" class="cert-row-dl" data-cert-index="<?= $i ?>" aria-label="Download <?= htmlspecialchars($c['label']) ?> certificate"><i class="fa-solid fa-download"></i></button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="cert-locked">
                                <i class="fa-solid fa-lock"></i>
                                <h4>No certificates earned yet</h4>
                                <p>Certificates are awarded at the end of each month based on your overall activity: a Top 25% rank, lower emissions than the previous month, or a monthly total of <?= CW_CERT_ECO_MONTHLY_KG ?> kg CO₂e or below, with at least <?= CW_CERT_MIN_ACTIVITIES ?> logged activities.</p>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>

            <!-- TOTAL IMPACT BREAKDOWN SECTION -->
            <div class="single-column-grid">
                <div class="dash-card">
                    <div class="breakdown-tabs">
                        <span class="tab-item active" onclick="switchCategoryTab(this, 'transportation')">Transportation</span>
                        <span class="tab-item" onclick="switchCategoryTab(this, 'office_resource')">Office Resource</span>
                        <span class="tab-item" onclick="switchCategoryTab(this, 'food_consumption')">Food Consumption</span>
                    </div>
                    <div class="breakdown-body">
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Total Impact</div>
                            <div style="font-size: 1.6rem; font-weight: 800; color: var(--text-title); margin: 3px 0;">
                                <span id="tabWeeklyScore">
                                    <?php 
                                        $val = floatval($categories_data['transportation']['current_week']);
                                        echo ($val == floor($val)) ? number_format($val, 0) : number_format($val, 1);
                                    ?>
                                </span> 
                                <span style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">kg CO₂</span>
                            </div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Total cumulative emissions impact</div>
                            
                            <div style="margin-top: 16px; display: flex; align-items: center; gap: 10px; word-break: break-word;">
                                <i id="tabActivityIcon" class="fa-solid fa-bus" style="color: var(--accent-green); font-size: 1.1rem; width: 20px; text-align: center; flex-shrink: 0;"></i>
                                <div style="min-width: 0;">
                                    <div id="tabActivityName" style="font-size: 0.8rem; font-weight: 700; color: var(--text-title); word-break: break-word;"><?= htmlspecialchars($categories_data['transportation']['top_activity']) ?></div>
                                    <div style="font-size: 0.68rem; color: var(--text-muted);">Contributing activity type</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 18px; text-align: right;">
                        <a href="reports.php" class="card-link">View Breakdown Details</a>
                    </div>
                </div>
            </div>

            <!-- ACTIVITY TIMELINE SECTION -->
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
                            <div style="text-align: center; color: var(--text-muted); padding: 15px; font-size: 0.8rem;">No activity records found in Neon database for this user.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const themeToggleSidebar = document.getElementById('themeToggleSidebar');
        const themeIconSidebar = document.getElementById('themeIconSidebar');
        const themeTextSidebar = document.getElementById('themeTextSidebar');
        const bodyElement = document.body;

        const menuToggleBtn = document.getElementById('menuToggleBtn');
        const appSidebar = document.getElementById('appSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        // Sidebar drawer toggles
        function toggleSidebar() {
            appSidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        }

        if (menuToggleBtn) menuToggleBtn.addEventListener('click', toggleSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);

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

        const certificates = <?= json_encode($certificates, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;

        (function () {
            if (!certificates.length) return;

            function wrapText(ctx, text, maxWidth) {
                const words = text.split(' ');
                const lines = [];
                let line = '';
                words.forEach(function (w) {
                    const test = line ? line + ' ' + w : w;
                    if (ctx.measureText(test).width > maxWidth && line) { lines.push(line); line = w; }
                    else { line = test; }
                });
                if (line) lines.push(line);
                return lines;
            }

            function roundRect(ctx, x, y, w, h, r) {
                ctx.beginPath();
                ctx.moveTo(x + r, y);
                ctx.arcTo(x + w, y, x + w, y + h, r);
                ctx.arcTo(x + w, y + h, x, y + h, r);
                ctx.arcTo(x, y + h, x, y, r);
                ctx.arcTo(x, y, x + w, y, r);
                ctx.closePath();
            }

            function drawCertificate(cert) {
                const W = 1600, H = 1130, cx = W / 2;
                const canvas = document.createElement('canvas');
                canvas.width = W; canvas.height = H;
                const ctx = canvas.getContext('2d');
                const font = "'Inter', 'Segoe UI', Arial, sans-serif";

                ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, W, H);
                ctx.lineWidth = 14; ctx.strokeStyle = '#95D5B2';
                roundRect(ctx, 50, 50, W - 100, H - 100, 40); ctx.stroke();
                ctx.lineWidth = 3; ctx.strokeStyle = '#2D6A4F';
                roundRect(ctx, 80, 80, W - 160, H - 160, 28); ctx.stroke();

                // Medal
                ctx.fillStyle = '#E6F4EA';
                ctx.beginPath(); ctx.arc(cx, 180, 74, 0, Math.PI * 2); ctx.fill();
                ctx.fillStyle = '#2D8F5E';
                ctx.beginPath(); ctx.arc(cx, 171, 42, 0, Math.PI * 2); ctx.fill();
                ctx.beginPath();
                ctx.moveTo(cx - 28, 206); ctx.lineTo(cx - 37, 257); ctx.lineTo(cx, 238);
                ctx.lineTo(cx + 37, 257); ctx.lineTo(cx + 28, 206); ctx.closePath(); ctx.fill();
                ctx.fillStyle = '#ffffff'; ctx.font = '700 50px ' + font;
                ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
                ctx.fillText('★', cx, 173);
                ctx.textBaseline = 'alphabetic';

                // Title
                ctx.fillStyle = '#2D6A4F'; ctx.font = '700 44px ' + font;
                if ('letterSpacing' in ctx) ctx.letterSpacing = '10px';
                ctx.fillText('CERTIFICATE OF RECOGNITION', cx + 5, 335);
                if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';

                // Month + year chip
                ctx.font = '700 34px ' + font;
                const chipText = cert.month + ' ' + cert.year;
                const cw = ctx.measureText(chipText).width + 80;
                ctx.fillStyle = '#E6F4EA'; roundRect(ctx, cx - cw / 2, 360, cw, 64, 32); ctx.fill();
                ctx.fillStyle = '#2D6A4F'; ctx.fillText(chipText, cx, 405);

                ctx.fillStyle = '#718096'; ctx.font = '400 30px ' + font;
                ctx.fillText('This certificate is proudly presented to', cx, 490);

                // Name
                let size = 96;
                ctx.fillStyle = '#1A202C';
                do { ctx.font = '800 ' + size + 'px ' + font; size -= 4; }
                while (ctx.measureText(cert.name).width > W - 300 && size > 40);
                ctx.fillText(cert.name, cx, 595);

                // Description
                ctx.fillStyle = '#718096'; ctx.font = '400 30px ' + font;
                const desc = 'For outstanding dedication to reducing your carbon footprint and ' + cert.headline;
                let y = 665;
                wrapText(ctx, desc, W - 420).forEach(function (l) { ctx.fillText(l, cx, y); y += 42; });

                // Tier pill
                y += 14;
                ctx.font = '800 54px ' + font;
                const pw = ctx.measureText(cert.tier).width + 110;
                ctx.fillStyle = '#E6F4EA'; roundRect(ctx, cx - pw / 2, y, pw, 96, 48); ctx.fill();
                ctx.fillStyle = '#2D8F5E'; ctx.fillText(cert.tier, cx, y + 66);

                // Reason
                ctx.fillStyle = '#718096'; ctx.font = '400 24px ' + font;
                y += 138;
                wrapText(ctx, cert.reason, W - 380).forEach(function (l) { ctx.fillText(l, cx, y); y += 34; });

                // Footer: date issued (with year) + signature
                ctx.textAlign = 'left';
                ctx.fillStyle = '#718096'; ctx.font = '400 24px ' + font;
                ctx.fillText('Date Issued', 170, H - 160);
                ctx.fillStyle = '#1A202C'; ctx.font = '700 32px ' + font;
                ctx.fillText(cert.issued, 170, H - 118);

                ctx.textAlign = 'center';
                ctx.fillStyle = '#2D6A4F'; ctx.font = 'italic 700 32px ' + font;
                ctx.fillText('CarbonWise Team', W - 330, H - 128);
                ctx.strokeStyle = '#CBD5E0'; ctx.lineWidth = 2;
                ctx.beginPath(); ctx.moveTo(W - 520, H - 108); ctx.lineTo(W - 140, H - 108); ctx.stroke();
                return canvas;
            }

            function download(cert) {
                return new Promise(function (resolve) {
                    drawCertificate(cert).toBlob(function (blob) {
                        if (blob) {
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url; a.download = cert.file + '.png';
                            document.body.appendChild(a); a.click(); a.remove();
                            setTimeout(function () { URL.revokeObjectURL(url); }, 1500);
                        }
                        resolve();
                    }, 'image/png');
                });
            }

            const ready = (document.fonts && document.fonts.ready) ? document.fonts.ready : Promise.resolve();

            document.querySelectorAll('[data-cert-index]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const cert = certificates[parseInt(btn.getAttribute('data-cert-index'), 10)];
                    if (cert) ready.then(function () { download(cert); });
                });
            });

            const allBtn = document.getElementById('downloadAllCertsBtn');
            if (allBtn) {
                allBtn.addEventListener('click', function () {
                    ready.then(function () {
                        let chain = Promise.resolve();
                        certificates.forEach(function (c) {
                            chain = chain.then(function () { return download(c); })
                                         .then(function () { return new Promise(function (r) { setTimeout(r, 500); }); });
                        });
                    });
                });
            }
        })();

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

        function confirmLogout() {
            let activeTheme = localStorage.getItem('theme') || 'light';
            let popupBg = activeTheme === 'dark' ? '#1E1E1E' : '#ffffff';
            let popupText = activeTheme === 'dark' ? '#F7FAFC' : '#1A202C';

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
    <script src="pwa-register.js"></script>
</body>
</html>