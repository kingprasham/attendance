<?php
// api/endpoints/leaves/pending.php
// GET /leaves/pending — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$pdo = get_db_connection();
$stmt = $pdo->query(
    "SELECT lr.*, e.full_name, e.employee_code, b.name as branch_name
     FROM leave_requests lr
     JOIN employees e ON e.id = lr.employee_id
     JOIN branches b ON b.id = e.branch_id
     WHERE lr.status = 'pending'
     ORDER BY lr.created_at ASC"
);

json_response($stmt->fetchAll());
