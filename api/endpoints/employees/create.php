<?php
// api/endpoints/employees/create.php
// POST /employees/create — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/encryption.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['full_name', 'branch_id', 'date_of_joining', 'monthly_salary', 'username', 'password']);
validate_date($input['date_of_joining'], 'date_of_joining');
validate_positive_number($input['monthly_salary'], 'monthly_salary');

$pdo = get_db_connection();

// Check username uniqueness
$stmt = $pdo->prepare("SELECT id FROM employees WHERE username = :u");
$stmt->execute([':u' => sanitize_string($input['username'])]);
if ($stmt->fetch()) {
    error_response('Username already exists', 409);
}

// Generate employee code (KE-001, KE-002, etc.)
$stmt2 = $pdo->query("SELECT MAX(id) as max_id FROM employees");
$max_id = (int)($stmt2->fetch()['max_id'] ?? 0);
$employee_code = EMPLOYEE_CODE_PREFIX . '-' . str_pad($max_id + 1, 3, '0', STR_PAD_LEFT);

// Hash password
$hashed_password = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);

// Encrypt sensitive fields
$pan = isset($input['pan_number']) ? aes_encrypt(sanitize_string($input['pan_number'])) : null;
$aadhar = isset($input['aadhar_number']) ? aes_encrypt(sanitize_string($input['aadhar_number'])) : null;

$stmt3 = $pdo->prepare(
    "INSERT INTO employees (
        employee_code, full_name, email, phone, designation, department,
        branch_id, date_of_joining, employment_type, monthly_salary,
        bank_account, ifsc_code, pan_number, aadhar_number,
        username, password
    ) VALUES (
        :code, :name, :email, :phone, :designation, :department,
        :branch_id, :doj, :emp_type, :salary,
        :bank, :ifsc, :pan, :aadhar,
        :username, :password
    )"
);
$stmt3->execute([
    ':code' => $employee_code,
    ':name' => sanitize_string($input['full_name']),
    ':email' => sanitize_string($input['email'] ?? ''),
    ':phone' => sanitize_string($input['phone'] ?? ''),
    ':designation' => sanitize_string($input['designation'] ?? ''),
    ':department' => sanitize_string($input['department'] ?? ''),
    ':branch_id' => (int)$input['branch_id'],
    ':doj' => $input['date_of_joining'],
    ':emp_type' => $input['employment_type'] ?? 'full',
    ':salary' => $input['monthly_salary'],
    ':bank' => sanitize_string($input['bank_account'] ?? ''),
    ':ifsc' => sanitize_string($input['ifsc_code'] ?? ''),
    ':pan' => $pan,
    ':aadhar' => $aadhar,
    ':username' => sanitize_string($input['username']),
    ':password' => $hashed_password,
]);

$new_id = $pdo->lastInsertId();

// Initialize leave balances for current year from branch policy
$year = date('Y');
$policy_stmt = $pdo->prepare(
    "SELECT leave_type, annual_quota FROM leave_policies WHERE branch_id = :bid"
);
$policy_stmt->execute([':bid' => (int)$input['branch_id']]);
$policies = $policy_stmt->fetchAll();

$balance_stmt = $pdo->prepare(
    "INSERT INTO leave_balances (employee_id, leave_type, year, total_quota, used, carried_forward)
     VALUES (:eid, :type, :year, :quota, 0, 0)"
);
foreach ($policies as $policy) {
    $balance_stmt->execute([
        ':eid' => $new_id,
        ':type' => $policy['leave_type'],
        ':year' => $year,
        ':quota' => $policy['annual_quota'],
    ]);
}

success_response('Employee created', [
    'id' => $new_id,
    'employee_code' => $employee_code,
], 201);
