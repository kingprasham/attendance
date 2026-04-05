<?php
// api/endpoints/employees/list.php
// GET /employees/list — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$pdo = get_db_connection();

$branch_id = $_GET['branch_id'] ?? null;

$sql = "SELECT e.id, e.employee_code, e.full_name, e.email, e.phone,
               e.designation, e.department, e.date_of_joining, e.employment_type,
               e.monthly_salary, e.is_active, e.device_id IS NOT NULL as device_bound,
               b.name as branch_name
        FROM employees e
        JOIN branches b ON b.id = e.branch_id
        WHERE e.is_active = 1";
$params = [];

if ($branch_id) {
    $sql .= " AND e.branch_id = :bid";
    $params[':bid'] = (int)$branch_id;
}

$sql .= " ORDER BY e.full_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

json_response($stmt->fetchAll());
