<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['branch_id', 'name', 'date']);
validate_date($input['date']);

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "INSERT INTO holidays (branch_id, name, date, is_optional)
     VALUES (:bid, :name, :date, :optional)"
);
$stmt->execute([
    ':bid' => (int)$input['branch_id'],
    ':name' => sanitize_string($input['name']),
    ':date' => $input['date'],
    ':optional' => (int)($input['is_optional'] ?? 0),
]);

success_response('Holiday added', ['id' => $pdo->lastInsertId()], 201);
