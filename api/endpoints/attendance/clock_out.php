<?php
// api/endpoints/attendance/clock_out.php
// POST /attendance/clock_out — Geofence-verified clock out

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

// 1. Verify device
$stmt = $pdo->prepare("SELECT device_id, branch_id FROM employees WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => $employee_id]);
$employee = $stmt->fetch();

if (!$employee || $employee['device_id'] !== $device_id) {
    error_response('Device not registered. Contact admin.', 403);
}

// 2. Find today's clock-in record
$today = gmdate('Y-m-d');
$stmt2 = $pdo->prepare(
    "SELECT id, clock_in, clock_out FROM attendance_logs
     WHERE employee_id = :eid AND date = :date"
);
$stmt2->execute([':eid' => $employee_id, ':date' => $today]);
$record = $stmt2->fetch();

if (!$record) {
    error_response('No clock-in found for today. Clock in first.', 400);
}
if ($record['clock_out'] !== null) {
    error_response('Already clocked out today', 409);
}

// 3. Geofence check
$stmt3 = $pdo->prepare(
    "SELECT latitude, longitude, radius_meters FROM branches WHERE id = :bid AND is_active = 1"
);
$stmt3->execute([':bid' => $employee['branch_id']]);
$branch = $stmt3->fetch();

if (!is_within_geofence($lat, $lng, $branch['latitude'], $branch['longitude'], $branch['radius_meters'])) {
    $distance = round(haversine_distance($lat, $lng, $branch['latitude'], $branch['longitude']));
    error_response("Cannot clock out. You are {$distance}m from office. Required: within {$branch['radius_meters']}m.", 403);
}

// 4. Calculate work hours
$now_utc = gmdate('Y-m-d H:i:s');
$clock_in_time = new DateTime($record['clock_in'], new DateTimeZone('UTC'));
$clock_out_time = new DateTime($now_utc, new DateTimeZone('UTC'));
$diff = $clock_out_time->diff($clock_in_time);
$work_hours = round($diff->h + ($diff->i / 60), 2);

// Check if half day (less than 4 hours)
$status_update = '';
if ($work_hours < 4) {
    $status_update = ", status = 'half_day'";
}

// 5. Update record
$stmt4 = $pdo->prepare(
    "UPDATE attendance_logs SET clock_out = :out, clock_out_lat = :lat,
     clock_out_lng = :lng, work_hours = :hours {$status_update}
     WHERE id = :id"
);
$stmt4->execute([
    ':out' => $now_utc,
    ':lat' => $lat,
    ':lng' => $lng,
    ':hours' => $work_hours,
    ':id' => $record['id'],
]);

$now_ist = new DateTime($now_utc, new DateTimeZone('UTC'));
$now_ist->setTimezone(new DateTimeZone(APP_TIMEZONE));

success_response('Clocked out', [
    'clock_out' => $now_ist->format('h:i A'),
    'work_hours' => $work_hours,
]);
