<?php
// api/endpoints/employees/reset_device.php
// POST /employees/reset_device — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['employee_id']);

$pdo = get_db_connection();
$stmt = $pdo->prepare("UPDATE employees SET device_id = NULL WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => (int)$input['employee_id']]);

if ($stmt->rowCount() === 0) {
    error_response('Employee not found', 404);
}

success_response('Device binding reset. Employee can register a new device on next login.');
