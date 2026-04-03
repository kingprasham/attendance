<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$id = $_GET['id'] ?? null;
if (!$id) {
    error_response('Holiday ID required', 400);
}

$pdo = get_db_connection();
$stmt = $pdo->prepare("DELETE FROM holidays WHERE id = :id");
$stmt->execute([':id' => (int)$id]);

if ($stmt->rowCount() === 0) {
    error_response('Holiday not found', 404);
}

success_response('Holiday deleted');
