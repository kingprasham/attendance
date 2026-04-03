<?php
// api/endpoints/attendance/history.php
// GET /attendance/history?month=4&year=2026 — Monthly attendance

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT date, clock_in, clock_out, status, work_hours
     FROM attendance_logs
     WHERE employee_id = :eid AND MONTH(date) = :month AND YEAR(date) = :year
     ORDER BY date ASC"
);
$stmt->execute([':eid' => $employee_id, ':month' => (int)$month, ':year' => (int)$year]);
$records = $stmt->fetchAll();

// Convert times to IST
foreach ($records as &$r) {
    if ($r['clock_in']) {
        $dt = new DateTime($r['clock_in'], new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
        $r['clock_in'] = $dt->format('h:i A');
    }
    if ($r['clock_out']) {
        $dt = new DateTime($r['clock_out'], new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
        $r['clock_out'] = $dt->format('h:i A');
    }
}

// Summary
$present = count(array_filter($records, fn($r) => in_array($r['status'], ['present', 'late'])));
$late = count(array_filter($records, fn($r) => $r['status'] === 'late'));
$half_day = count(array_filter($records, fn($r) => $r['status'] === 'half_day'));

json_response([
    'month' => (int)$month,
    'year' => (int)$year,
    'summary' => [
        'present' => $present,
        'late' => $late,
        'half_day' => $half_day,
        'total_records' => count($records),
    ],
    'records' => $records,
]);
