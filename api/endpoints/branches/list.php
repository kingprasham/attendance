<?php
// api/endpoints/branches/list.php
// GET /branches/list — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$pdo = get_db_connection();
$stmt = $pdo->query(
    "SELECT b.*, COUNT(e.id) as employee_count
     FROM branches b
     LEFT JOIN employees e ON e.branch_id = b.id AND e.is_active = 1
     WHERE b.is_active = 1
     GROUP BY b.id
     ORDER BY b.name"
);

json_response($stmt->fetchAll());
