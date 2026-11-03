<?php
// ---------------------------------------------------------------
// CarbonWise campus geofence configuration
// ---------------------------------------------------------------
// NOTE: The coordinates below are APPROXIMATE (town level only).
// Replace each one with the real campus location:
//   Google Maps -> right-click the campus -> click the "lat, lng" line to copy it.
// Wrong coordinates will block legitimate users who are on campus.
//
// 'name' must match EXACTLY what is stored in users.campus
// (it is the same text used in the register.php dropdown).
const CAMPUS_LOCATIONS = [
    ['name' => 'Lipa Campus',           'lat' => 13.9546, 'lng' => 121.1630, 'radius_m' => 500],
    ['name' => 'Pablo Borbon Campus',   'lat' => 13.7565, 'lng' => 121.0583, 'radius_m' => 500],
    ['name' => 'Alangilan Campus',      'lat' => 13.7847, 'lng' => 121.0743, 'radius_m' => 500],
    ['name' => 'LIMA Campus',           'lat' => 14.0108, 'lng' => 121.1588, 'radius_m' => 500],
    ['name' => 'ARASOF Nasugbu Campus', 'lat' => 14.0717, 'lng' => 120.6313, 'radius_m' => 500],
    ['name' => 'JPLPC Malvar Campus',   'lat' => 14.0430, 'lng' => 121.1590, 'radius_m' => 500],
    ['name' => 'Lemery Campus',         'lat' => 13.8830, 'lng' => 120.9140, 'radius_m' => 500],
    ['name' => 'Rosario Campus',        'lat' => 13.8450, 'lng' => 121.2060, 'radius_m' => 500],
    ['name' => 'San Juan Campus',       'lat' => 13.8270, 'lng' => 121.3960, 'radius_m' => 500],
    ['name' => 'Balayan Campus',        'lat' => 13.9390, 'lng' => 120.7320, 'radius_m' => 500],
    ['name' => 'Lobo Campus',           'lat' => 13.6500, 'lng' => 121.2090, 'radius_m' => 500],
    ['name' => 'Mabini Campus',         'lat' => 13.7230, 'lng' => 120.9040, 'radius_m' => 500],
    // Temporary test entry (remove before going live):
    // ['name' => 'Lipa Campus', 'lat' => YOUR_LAT, 'lng' => YOUR_LNG, 'radius_m' => 300],
];

const MAX_ACCURACY_M = 300;   // reject vague location fixes (meters)
const VERIFY_TTL_SEC = 1800;  // re-verify location every 30 minutes

function distance_m(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $r  = 6371000;
    $p1 = deg2rad($lat1); $p2 = deg2rad($lat2);
    $dp = $p2 - $p1;      $dl = deg2rad($lng2 - $lng1);
    $a  = sin($dp / 2) ** 2 + cos($p1) * cos($p2) * sin($dl / 2) ** 2;
    return 2 * $r * asin(sqrt($a));
}

// Returns the name of the campus the coordinates fall inside, or null.
function find_campus(float $lat, float $lng): ?string {
    foreach (CAMPUS_LOCATIONS as $c) {
        if (distance_m($lat, $lng, $c['lat'], $c['lng']) <= $c['radius_m']) {
            return $c['name'];
        }
    }
    return null;
}

// Case/whitespace-insensitive comparison of two campus names.
function campus_matches(?string $a, ?string $b): bool {
    $a = strtolower(trim((string)$a));
    $b = strtolower(trim((string)$b));
    return $a !== '' && $a === $b;
}