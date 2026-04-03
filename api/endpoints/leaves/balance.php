<?php
// api/endpoints/leaves/balance.php
// GET /leaves/balance?year=2026

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();
$year = $_GET['year'] ?? date('Y');

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT leave_type, total_quota, used, carried_forward,
            (total_quota + carried_forward - used) as remaining
     FROM leave_balances
     WHERE employee_id = :eid AND year = :year"
);
$stmt->execute([':eid' => $employee_id, ':year' => (int)$year]);

json_response($stmt->fetchAll());
