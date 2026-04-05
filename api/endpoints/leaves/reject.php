<?php
// api/endpoints/leaves/reject.php
// POST /leaves/reject — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['leave_id']);

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT * FROM leave_requests WHERE id = :id AND status = 'pending'"
);
$stmt->execute([':id' => (int)$input['leave_id']]);
$leave = $stmt->fetch();

if (!$leave) {
    error_response('Leave request not found or already processed', 404);
}

// Update status
$stmt2 = $pdo->prepare(
    "UPDATE leave_requests SET status = 'rejected', admin_remarks = :remarks, reviewed_at = NOW() WHERE id = :id"
);
$stmt2->execute([
    ':id' => $leave['id'],
    ':remarks' => sanitize_string($input['remarks'] ?? ''),
]);

// Notify employee
$stmt3 = $pdo->prepare(
    "INSERT INTO notifications (employee_id, title, body, type)
     VALUES (:eid, :title, :body, 'leave')"
);
$stmt3->execute([
    ':eid' => $leave['employee_id'],
    ':title' => 'Leave Rejected',
    ':body' => "{$leave['leave_type']} leave from {$leave['start_date']} to {$leave['end_date']} has been rejected. Reason: " . ($input['remarks'] ?? 'No reason provided'),
]);

success_response('Leave rejected');
