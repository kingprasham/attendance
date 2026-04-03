<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$year = $_GET['year'] ?? date('Y');

// Get employee's branch
$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT branch_id FROM employees WHERE id = :id");
$stmt->execute([':id' => $employee_id]);
$emp = $stmt->fetch();

$stmt2 = $pdo->prepare(
    "SELECT id, name, date, is_optional FROM holidays
     WHERE branch_id = :bid AND YEAR(date) = :year
     ORDER BY date ASC"
);
$stmt2->execute([':bid' => $emp['branch_id'], ':year' => (int)$year]);

json_response($stmt2->fetchAll());
