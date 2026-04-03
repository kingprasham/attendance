<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$input = get_json_input();
validate_required($input, ['notification_id']);

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "UPDATE notifications SET is_read = 1 WHERE id = :id AND employee_id = :eid"
);
$stmt->execute([':id' => (int)$input['notification_id'], ':eid' => $employee_id]);

success_response('Notification marked as read');
