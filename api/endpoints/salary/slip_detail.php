<?php
// api/endpoints/salary/slip_detail.php
// GET /salary/slip_detail?id=X

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$slip_id = $_GET['id'] ?? null;
if (!$slip_id) {
    error_response('Slip ID required', 400);
}

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT ss.*, e.full_name, e.employee_code, e.designation, e.department,
            e.monthly_salary, e.bank_account, e.ifsc_code, b.name as branch_name
     FROM salary_slips ss
     JOIN employees e ON e.id = ss.employee_id
     JOIN branches b ON b.id = e.branch_id
     WHERE ss.id = :id AND ss.employee_id = :eid"
);
$stmt->execute([':id' => (int)$slip_id, ':eid' => $employee_id]);
$slip = $stmt->fetch();

if (!$slip) {
    error_response('Salary slip not found', 404);
}

json_response($slip);
