<?php
// api/endpoints/branches/delete.php
// DELETE /branches/delete — Admin only (soft delete)

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$id = $_GET['id'] ?? null;
if (!$id) {
    error_response('Branch ID required', 400);
}

$pdo = get_db_connection();
$stmt = $pdo->prepare("UPDATE branches SET is_active = 0 WHERE id = :id AND is_active = 1");
$stmt->execute([':id' => (int)$id]);

if ($stmt->rowCount() === 0) {
    error_response('Branch not found', 404);
}

success_response('Branch deactivated');
