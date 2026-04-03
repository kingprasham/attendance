<?php
// api/endpoints/attendance/report.php
// GET /attendance/report?date=2026-04-01&branch_id=1 — Daily report (admin)

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$date = $_GET['date'] ?? gmdate('Y-m-d');
$branch_id = $_GET['branch_id'] ?? null;

$pdo = get_db_connection();

// Get all active employees (optionally filtered by branch)
$emp_sql = "SELECT e.id, e.employee_code, e.full_name, e.designation, e.department, b.name as branch_name
            FROM employees e
            JOIN branches b ON b.id = e.branch_id
            WHERE e.is_active = 1";
$params = [];
if ($branch_id) {
    $emp_sql .= " AND e.branch_id = :bid";
    $params[':bid'] = (int)$branch_id;
}
$emp_sql .= " ORDER BY b.name, e.full_name";
$emp_stmt = $pdo->prepare($emp_sql);
$emp_stmt->execute($params);
$employees = $emp_stmt->fetchAll();

// Get attendance records for the date
$att_stmt = $pdo->prepare(
    "SELECT employee_id, clock_in, clock_out, status, work_hours
     FROM attendance_logs WHERE date = :date"
);
$att_stmt->execute([':date' => $date]);
$attendance = [];
foreach ($att_stmt->fetchAll() as $a) {
    $attendance[$a['employee_id']] = $a;
}

// Get approved leaves for the date
$leave_stmt = $pdo->prepare(
    "SELECT lr.employee_id, lr.leave_type, lr.reason
     FROM leave_requests lr
     WHERE lr.status = 'approved' AND :date BETWEEN lr.start_date AND lr.end_date"
);
$leave_stmt->execute([':date' => $date]);
$leaves = [];
foreach ($leave_stmt->fetchAll() as $l) {
    $leaves[$l['employee_id']] = $l;
}

// Build report
$report = [];
$counts = ['present' => 0, 'late' => 0, 'half_day' => 0, 'on_leave' => 0, 'absent' => 0];

foreach ($employees as $emp) {
    $entry = [
        'employee_id' => $emp['id'],
        'employee_code' => $emp['employee_code'],
        'full_name' => $emp['full_name'],
        'designation' => $emp['designation'],
        'branch_name' => $emp['branch_name'],
    ];

    if (isset($attendance[$emp['id']])) {
        $att = $attendance[$emp['id']];
        $entry['status'] = $att['status'];
        $entry['clock_in'] = $att['clock_in'];
        $entry['clock_out'] = $att['clock_out'];
        $entry['work_hours'] = $att['work_hours'];

        // Convert to IST
        if ($entry['clock_in']) {
            $dt = new DateTime($entry['clock_in'], new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
            $entry['clock_in'] = $dt->format('h:i A');
        }
        if ($entry['clock_out']) {
            $dt = new DateTime($entry['clock_out'], new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone(APP_TIMEZONE));
            $entry['clock_out'] = $dt->format('h:i A');
        }

        $counts[$att['status']]++;
    } elseif (isset($leaves[$emp['id']])) {
        $entry['status'] = 'on_leave';
        $entry['leave_type'] = $leaves[$emp['id']]['leave_type'];
        $entry['reason'] = $leaves[$emp['id']]['reason'];
        $counts['on_leave']++;
    } else {
        $entry['status'] = 'absent';
        $counts['absent']++;
    }

    $report[] = $entry;
}

json_response([
    'date' => $date,
    'summary' => $counts,
    'total_employees' => count($employees),
    'records' => $report,
]);
