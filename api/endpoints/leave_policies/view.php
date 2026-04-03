<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

// Get employee's branch
$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT branch_id FROM employees WHERE id = :id");
$stmt->execute([':id' => $employee_id]);
$emp = $stmt->fetch();

$branch_id = $_GET['branch_id'] ?? $emp['branch_id'];

$stmt2 = $pdo->prepare(
    "SELECT leave_type, annual_quota, carry_forward, max_carry
     FROM leave_policies WHERE branch_id = :bid"
);
$stmt2->execute([':bid' => (int)$branch_id]);

json_response($stmt2->fetchAll());
