<?php
// api/endpoints/employees/profile.php
// GET /employees/profile — Employee's own profile

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/encryption.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT e.id, e.employee_code, e.full_name, e.email, e.phone, e.profile_photo,
            e.designation, e.department, e.date_of_joining, e.employment_type,
            e.monthly_salary, e.bank_account, e.ifsc_code, e.pan_number, e.aadhar_number,
            b.name as branch_name
     FROM employees e
     JOIN branches b ON b.id = e.branch_id
     WHERE e.id = :id"
);
$stmt->execute([':id' => $employee_id]);
$employee = $stmt->fetch();

// Decrypt sensitive fields
$employee['pan_number'] = $employee['pan_number'] ? aes_decrypt($employee['pan_number']) : null;
$employee['aadhar_number'] = $employee['aadhar_number'] ? aes_decrypt($employee['aadhar_number']) : null;

json_response($employee);
