<?php
// api/endpoints/employees/view.php
// GET /employees/view?id=X — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/encryption.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$id = $_GET['id'] ?? null;
if (!$id) {
    error_response('Employee ID required', 400);
}

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT e.*, b.name as branch_name
     FROM employees e
     JOIN branches b ON b.id = e.branch_id
     WHERE e.id = :id"
);
$stmt->execute([':id' => (int)$id]);
$employee = $stmt->fetch();

if (!$employee) {
    error_response('Employee not found', 404);
}

// Decrypt sensitive fields
$employee['pan_number'] = $employee['pan_number'] ? aes_decrypt($employee['pan_number']) : null;
$employee['aadhar_number'] = $employee['aadhar_number'] ? aes_decrypt($employee['aadhar_number']) : null;

// Remove password hash from response
unset($employee['password']);

json_response($employee);
