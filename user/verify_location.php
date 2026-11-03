<?php
session_start();
require_once __DIR__ . '/campus_config.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Invalid request.']); exit;
}

$in  = json_decode(file_get_contents('php://input'), true);
$lat = (is_array($in) && isset($in['lat']) && is_numeric($in['lat'])) ? (float)$in['lat'] : null;
$lng = (is_array($in) && isset($in['lng']) && is_numeric($in['lng'])) ? (float)$in['lng'] : null;
$acc = (is_array($in) && isset($in['accuracy']) && is_numeric($in['accuracy'])) ? (float)$in['accuracy'] : 99999;

if ($lat === null || $lng === null || abs($lat) > 90 || abs($lng) > 180) {
    echo json_encode(['ok' => false, 'message' => 'Invalid location data.']); exit;
}
if ($acc > MAX_ACCURACY_M) {
    echo json_encode(['ok' => false, 'message' => 'Your location is too imprecise. Turn on GPS/Wi-Fi location and try again.']); exit;
}

$campus = find_campus($lat, $lng);
if (!$campus) {
    unset($_SESSION['campus_verified_at'], $_SESSION['campus_name']);
    echo json_encode(['ok' => false, 'message' => 'CarbonWise is only available inside a BatStateU campus.']); exit;
}

session_regenerate_id(true);
$_SESSION['campus_verified_at'] = time();
$_SESSION['campus_name']        = $campus;
echo json_encode(['ok' => true, 'campus' => $campus]);