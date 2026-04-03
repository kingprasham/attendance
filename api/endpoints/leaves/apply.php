<?php
// api/endpoints/leaves/apply.php
// POST /leaves/apply

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$input = get_json_input();
validate_required($input, ['leave_type', 'start_date', 'end_date']);
validate_enum($input['leave_type'], ['CL', 'SL', 'EL', 'CO', 'LWP'], 'leave_type');
validate_date($input['start_date'], 'start_date');
validate_date($input['end_date'], 'end_date');

if ($input['start_date'] > $input['end_date']) {
    error_response('Start date must be before or equal to end date', 400);
}

$leave_type = $input['leave_type'];
$is_half_day = (int)($input['is_half_day'] ?? 0);
$year = date('Y', strtotime($input['start_date']));

// Calculate days
$start = new DateTime($input['start_date']);
$end = new DateTime($input['end_date']);
$days = $start->diff($end)->days + 1;
if ($is_half_day) {
    $days = 0.5;
}

$pdo = get_db_connection();

// Check balance (skip for LWP — unpaid is unlimited)
if ($leave_type !== 'LWP') {
    $stmt = $pdo->prepare(
        "SELECT total_quota, used, carried_forward FROM leave_balances
         WHERE employee_id = :eid AND leave_type = :type AND year = :year"
    );
    $stmt->execute([':eid' => $employee_id, ':type' => $leave_type, ':year' => $year]);
    $balance = $stmt->fetch();

    if (!$balance) {
        error_response("No {$leave_type} policy configured for your branch this year", 400);
    }

    $remaining = ($balance['total_quota'] + $balance['carried_forward']) - $balance['used'];
    if ($days > $remaining) {
        error_response("Insufficient {$leave_type} balance. Remaining: {$remaining} days", 400);
    }
}

// Check for overlapping pending/approved leaves
$stmt2 = $pdo->prepare(
    "SELECT id FROM leave_requests
     WHERE employee_id = :eid AND status IN ('pending','approved')
     AND start_date <= :end AND end_date >= :start"
);
$stmt2->execute([
    ':eid' => $employee_id,
    ':start' => $input['start_date'],
    ':end' => $input['end_date'],
]);
if ($stmt2->fetch()) {
    error_response('Overlapping leave request exists for these dates', 409);
}

// Create request
$stmt3 = $pdo->prepare(
    "INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, is_half_day, reason)
     VALUES (:eid, :type, :start, :end, :half, :reason)"
);
$stmt3->execute([
    ':eid' => $employee_id,
    ':type' => $leave_type,
    ':start' => $input['start_date'],
    ':end' => $input['end_date'],
    ':half' => $is_half_day,
    ':reason' => sanitize_string($input['reason'] ?? ''),
]);

success_response('Leave application submitted', ['id' => $pdo->lastInsertId()], 201);
