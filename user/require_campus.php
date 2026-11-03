<?php
// Include this near the top of every page, right after session_start().
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/campus_config.php';

// True for background requests (fetch/XHR/POST endpoints) that expect JSON, not a redirect.
function cw_is_background_request(): bool {
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (in_array($script, ['index.php', 'login.php', 'register.php'], true)) return false;
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
        || isset($_GET['action'])
        || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
        || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
}

function cw_campus_deny(string $message, string $redirectUrl): void {
    if (cw_is_background_request()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'status' => 'error', 'campus_required' => true, 'message' => $message]);
    } else {
        header('Location: ' . $redirectUrl);
    }
    exit;
}

// 1. The visitor must have a fresh, verified on-campus location.
$cw_verified = !empty($_SESSION['campus_verified_at'])
            && !empty($_SESSION['campus_name'])
            && (time() - (int)$_SESSION['campus_verified_at']) <= VERIFY_TTL_SEC;

if (!$cw_verified) {
    cw_campus_deny(
        'Please confirm your campus location to continue.',
        'location_gate.php?next=' . urlencode(basename($_SERVER['SCRIPT_NAME']))
    );
}

// 2. If logged in, the detected campus must match the campus registered on the account.
if (isset($_SESSION['user_token'])) {
    $cw_registered = $_SESSION['user_profile']['campus'] ?? null;
    if (!campus_matches($cw_registered, $_SESSION['campus_name'])) {
        $cw_msg = 'This account is registered to the ' . ($cw_registered ?: 'unassigned') .
                  '. You can only use CarbonWise while on that campus.';
        // Log the user out but keep the verified location so login.php can show the message.
        unset($_SESSION['user_token'], $_SESSION['user_id'], $_SESSION['user_email'],
              $_SESSION['g_suite'], $_SESSION['user_name'], $_SESSION['user_profile'],
              $_SESSION['user_role'], $_SESSION['user_campus']);
        $_SESSION['flash_error'] = $cw_msg;
        cw_campus_deny($cw_msg, 'login.php');
    }
}