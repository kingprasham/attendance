<?php
// api/endpoints/leaves/history.php
// GET /leaves/history

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT id, leave_type, start_date, end_date, is_half_day, reason, status, admin_remarks, created_at
     FROM leave_requests
     WHERE employee_id = :eid
     ORDER BY created_at DESC"
);
$stmt->execute([':eid' => $employee_id]);

json_response($stmt->fetchAll());
