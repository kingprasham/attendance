<?php
// api/endpoints/auth/login.php
// POST /auth/login — Employee login

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/jwt_handler.php';
require_once __DIR__ . '/../../middleware/rate_limit.php';

rate_limit_login();

$input = get_json_input();
validate_required($input, ['username', 'password']);

$username = sanitize_string($input['username']);
$password = $input['password'];
$device_id = sanitize_string($input['device_id'] ?? '');

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "SELECT id, full_name, password, device_id, branch_id, is_active
     FROM employees WHERE username = :username LIMIT 1"
);
$stmt->execute([':username' => $username]);
$employee = $stmt->fetch();

if (!$employee || !password_verify($password, $employee['password'])) {
    error_response('Invalid username or password', 401);
}

if (!$employee['is_active']) {
    error_response('Account is deactivated. Contact admin.', 403);
}

// Device binding check
$needs_device_registration = false;
if (empty($employee['device_id'])) {
    // First login — device will be bound
    $needs_device_registration = true;
} elseif (!empty($device_id) && $employee['device_id'] !== $device_id) {
    error_response('Device not registered. This account is bound to another device. Contact admin to reset.', 403);
}

// Generate tokens
$tokens = generate_tokens($employee['id'], 'employee');

success_response('Login successful', [
    'employee_id' => $employee['id'],
    'full_name' => $employee['full_name'],
    'branch_id' => $employee['branch_id'],
    'needs_device_registration' => $needs_device_registration,
    'access_token' => $tokens['access_token'],
    'refresh_token' => $tokens['refresh_token'],
    'expires_in' => $tokens['expires_in'],
]);
