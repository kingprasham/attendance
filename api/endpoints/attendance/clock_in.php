<?php
// api/endpoints/attendance/clock_in.php
// POST /attendance/clock_in — Geofence-verified attendance

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/geofence.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/rate_limit.php';

rate_limit_attendance();
$employee_id = require_auth();

$input = get_json_input();
validate_required($input, ['latitude', 'longitude', 'device_id']);

$lat = (float)$input['latitude'];
$lng = (float)$input['longitude'];
$device_id = sanitize_string($input['device_id']);

$pdo = get_db_connection();

// 1. Verify device binding
$stmt = $pdo->prepare("SELECT device_id, branch_id FROM employees WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $employee_id]);
$employee = $stmt->fetch();

if (!$employee) {
    error_response('Employee not found', 404);
}
if ($employee['device_id'] !== $device_id) {
    error_response('Device not registered. Contact admin.', 403);
}

// 2. Check no duplicate clock-in today
$today = gmdate('Y-m-d'); // UTC date
$stmt2 = $pdo->prepare(
    "SELECT id FROM attendance_logs WHERE employee_id = :eid AND date = :date"
);
$stmt2->execute([':eid' => $employee_id, ':date' => $today]);
if ($stmt2->fetch()) {
    error_response('Already clocked in today', 409);
}

// 3. Geofence check
$stmt3 = $pdo->prepare(
    "SELECT latitude, longitude, radius_meters FROM branches WHERE id = :bid AND is_active = 1"
);
$stmt3->execute([':bid' => $employee['branch_id']]);
$branch = $stmt3->fetch();

if (!$branch) {
    error_response('Branch not found', 404);
}

if (!is_within_geofence($lat, $lng, $branch['latitude'], $branch['longitude'], $branch['radius_meters'])) {
    $distance = round(haversine_distance($lat, $lng, $branch['latitude'], $branch['longitude']));
    error_response("Cannot mark attendance. You are {$distance}m from office. Required: within {$branch['radius_meters']}m.", 403);
}

// 4. Determine if late
$now_utc = gmdate('Y-m-d H:i:s');
$now_ist = new DateTime($now_utc, new DateTimeZone('UTC'));
$now_ist->setTimezone(new DateTimeZone(APP_TIMEZONE));
$hour = (int)$now_ist->format('H');
$minute = (int)$now_ist->format('i');

$status = 'present';
if ($hour > DEFAULT_LATE_HOUR || ($hour === DEFAULT_LATE_HOUR && $minute > DEFAULT_LATE_MINUTE)) {
    $status = 'late';
}

// 5. Record attendance
$stmt4 = $pdo->prepare(
    "INSERT INTO attendance_logs (employee_id, date, clock_in, clock_in_lat, clock_in_lng, device_id, status)
     VALUES (:eid, :date, :clock_in, :lat, :lng, :device, :status)"
);
$stmt4->execute([
    ':eid' => $employee_id,
    ':date' => $today,
    ':clock_in' => $now_utc,
    ':lat' => $lat,
    ':lng' => $lng,
    ':device' => $device_id,
    ':status' => $status,
]);

success_response('Attendance marked', [
    'clock_in' => $now_ist->format('h:i A'),
    'status' => $status,
    'date' => $today,
]);
