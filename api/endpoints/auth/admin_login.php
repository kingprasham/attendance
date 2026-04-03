<?php
// api/endpoints/auth/admin_login.php
// POST /auth/admin_login — Admin login

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/jwt_handler.php';
require_once __DIR__ . '/../../middleware/rate_limit.php';

rate_limit_login();

$input = get_json_input();
validate_required($input, ['email', 'password']);

$email = sanitize_string($input['email']);
$password = $input['password'];

$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT id, name, password FROM admins WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password'])) {
    error_response('Invalid email or password', 401);
}

$tokens = generate_tokens($admin['id'], 'admin');

success_response('Admin login successful', [
    'admin_id' => $admin['id'],
    'name' => $admin['name'],
    'access_token' => $tokens['access_token'],
    'refresh_token' => $tokens['refresh_token'],
    'expires_in' => $tokens['expires_in'],
]);
