<?php
// api/endpoints/salary/slips.php
// GET /salary/slips — Employee's salary slips list

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT id, month, year, total_days, present_days, leave_days, lwp_days,
            gross_salary, deductions, net_salary, generated_at
     FROM salary_slips
     WHERE employee_id = :eid
     ORDER BY year DESC, month DESC"
);
$stmt->execute([':eid' => $employee_id]);

json_response($stmt->fetchAll());
