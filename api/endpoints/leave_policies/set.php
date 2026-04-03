<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['branch_id', 'leave_type', 'annual_quota']);
validate_enum($input['leave_type'], ['CL', 'SL', 'EL', 'CO', 'LWP'], 'leave_type');

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "INSERT INTO leave_policies (branch_id, leave_type, annual_quota, carry_forward, max_carry)
     VALUES (:bid, :type, :quota, :carry, :max_carry)
     ON DUPLICATE KEY UPDATE annual_quota = :quota2, carry_forward = :carry2, max_carry = :max_carry2"
);
$stmt->execute([
    ':bid' => (int)$input['branch_id'],
    ':type' => $input['leave_type'],
    ':quota' => (int)$input['annual_quota'],
    ':carry' => (int)($input['carry_forward'] ?? 0),
    ':max_carry' => (int)($input['max_carry'] ?? 0),
    ':quota2' => (int)$input['annual_quota'],
    ':carry2' => (int)($input['carry_forward'] ?? 0),
    ':max_carry2' => (int)($input['max_carry'] ?? 0),
]);

success_response('Leave policy set');
