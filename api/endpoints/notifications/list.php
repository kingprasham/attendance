<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT id, title, body, type, is_read, created_at
     FROM notifications
     WHERE employee_id = :eid
     ORDER BY created_at DESC
     LIMIT 50"
);
$stmt->execute([':eid' => $employee_id]);

json_response($stmt->fetchAll());
