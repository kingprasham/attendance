<?php
// api/endpoints/attendance/today.php
// GET /attendance/today — Today's attendance status

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();
$today = gmdate('Y-m-d');

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT * FROM attendance_logs WHERE employee_id = :eid AND date = :date"
);
$stmt->execute([':eid' => $employee_id, ':date' => $today]);
$record = $stmt->fetch();

if (!$record) {
    json_response([
        'date' => $today,
        'clocked_in' => false,
        'clocked_out' => false,
    ]);
}

// Convert UTC times to IST for display
$clock_in_ist = null;
$clock_out_ist = null;

if ($record['clock_in']) {
    $dt = new DateTime($record['clock_in'], new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
    $clock_in_ist = $dt->format('h:i A');
}
if ($record['clock_out']) {
    $dt = new DateTime($record['clock_out'], new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
    $clock_out_ist = $dt->format('h:i A');
}

json_response([
    'date' => $today,
    'clocked_in' => true,
    'clocked_out' => $record['clock_out'] !== null,
    'clock_in_time' => $clock_in_ist,
    'clock_out_time' => $clock_out_ist,
    'status' => $record['status'],
    'work_hours' => $record['work_hours'],
]);
