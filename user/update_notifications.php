<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// 1. Security check: Ensures user token matches guard rules
if (!isset($_SESSION['user_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// 2. Resolve target session email dynamically matching dashboard workflow
$user_data = $_SESSION['user_profile'] ?? ($_SESSION['user_data'] ?? []);
$userEmail = $_SESSION['user_email'] ?? ($_SESSION['email'] ?? ($_SESSION['g_suite'] ?? ($user_data['g_suite'] ?? ($user_data['email'] ?? null))));

if (!empty($userEmail)) {
    $userEmail = strtolower(trim($userEmail));
}

$notificationId = isset($_POST['id']) ? intval($_POST['id']) : null;

if (!$notificationId || empty($userEmail)) {
    echo json_encode(['status' => 'error', 'message' => 'Notification context parameters missing']);
    exit;
}

// 3. Supabase Core Properties
$supabaseUrl = 'https://cvlibryzqhoztbutyvbx.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImN2bGlicnl6cWhvenRidXR5dmJ4Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODIyMDgxNTcsImV4cCI6MjA5Nzc4NDE1N30.q0vj8nBE4_SPVs8DDDeBOnzu8rpvGdfA5GXQpGp5rWs';

// 4. Build filter verification url targeting row id and user identity
$url = $supabaseUrl . '/rest/v1/notifications?id=eq.' . $notificationId . '&g_suite=eq.' . urlencode($userEmail);

$data = ['is_read' => true];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); 
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $supabaseKey,
    'Authorization: Bearer ' . $supabaseKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Supabase returns HTTP status 200 or 204 upon successful patch mutations
if ($httpCode === 200 || $httpCode === 204) {
    echo json_encode(['status' => 'success', 'message' => 'Marked as read']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database reject status code', 'code' => $httpCode]);
}
?>