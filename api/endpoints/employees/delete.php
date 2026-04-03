<?php
// api/endpoints/employees/delete.php
// DELETE /employees/delete?id=X — Admin only (soft delete)

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$id = $_GET['id'] ?? null;
if (!$id) {
    error_response('Employee ID required', 400);
}

$pdo = get_db_connection();
$stmt = $pdo->prepare("UPDATE employees SET is_active = 0 WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => (int)$id]);

if ($stmt->rowCount() === 0) {
    error_response('Employee not found', 404);
}

success_response('Employee deactivated');
