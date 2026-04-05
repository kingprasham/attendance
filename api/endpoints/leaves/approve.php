<?php
// api/endpoints/leaves/approve.php
// POST /leaves/approve — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['leave_id']);

$pdo = get_db_connection();

// Get leave request
$stmt = $pdo->prepare(
    "SELECT * FROM leave_requests WHERE id = :id AND status = 'pending'"
);
$stmt->execute([':id' => (int)$input['leave_id']]);
$leave = $stmt->fetch();

if (!$leave) {
    error_response('Leave request not found or already processed', 404);
}

// Calculate days
$start = new DateTime($leave['start_date']);
$end = new DateTime($leave['end_date']);
$days = $start->diff($end)->days + 1;
if ($leave['is_half_day']) {
    $days = 0.5;
}

$year = date('Y', strtotime($leave['start_date']));

// Begin transaction
$pdo->beginTransaction();
try {
    // Update request status
    $stmt2 = $pdo->prepare(
        "UPDATE leave_requests SET status = 'approved', admin_remarks = :remarks, reviewed_at = NOW() WHERE id = :id"
    );
    $stmt2->execute([
        ':id' => $leave['id'],
        ':remarks' => sanitize_string($input['remarks'] ?? ''),
    ]);

    // Update leave balance (skip for LWP)
    if ($leave['leave_type'] !== 'LWP') {
        $stmt3 = $pdo->prepare(
            "UPDATE leave_balances SET used = used + :days
             WHERE employee_id = :eid AND leave_type = :type AND year = :year"
        );
        $stmt3->execute([
            ':days' => $days,
            ':eid' => $leave['employee_id'],
            ':type' => $leave['leave_type'],
            ':year' => $year,
        ]);
    }

    // Create notification for employee
    $stmt4 = $pdo->prepare(
        "INSERT INTO notifications (employee_id, title, body, type)
         VALUES (:eid, :title, :body, 'leave')"
    );
    $stmt4->execute([
        ':eid' => $leave['employee_id'],
        ':title' => 'Leave Approved',
        ':body' => "{$leave['leave_type']} leave from {$leave['start_date']} to {$leave['end_date']} has been approved.",
    ]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_response('Failed to approve leave', 500);
}

success_response('Leave approved');
