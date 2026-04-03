<?php
// api/endpoints/leaves/cancel.php
// POST /leaves/cancel

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$input = get_json_input();
validate_required($input, ['leave_id']);

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "DELETE FROM leave_requests
     WHERE id = :id AND employee_id = :eid AND status = 'pending'"
);
$stmt->execute([':id' => (int)$input['leave_id'], ':eid' => $employee_id]);

if ($stmt->rowCount() === 0) {
    error_response('Leave request not found or already processed', 404);
}

success_response('Leave request cancelled');
