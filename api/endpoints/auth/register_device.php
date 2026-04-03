<?php
// api/endpoints/auth/register_device.php
// POST /auth/register_device — Bind device to employee on first login

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/auth.php';

$employee_id = require_auth();

$input = get_json_input();
validate_required($input, ['device_id']);
$device_id = sanitize_string($input['device_id']);

$pdo = get_db_connection();

// Check if device is already bound
$stmt = $pdo->prepare("SELECT device_id FROM employees WHERE id = :id");
$stmt->execute([':id' => $employee_id]);
$employee = $stmt->fetch();

if (!empty($employee['device_id'])) {
    error_response('Device already registered. Contact admin to reset.', 409);
}

// Bind device
$stmt2 = $pdo->prepare("UPDATE employees SET device_id = :device_id WHERE id = :id");
$stmt2->execute([':device_id' => $device_id, ':id' => $employee_id]);

// Also store FCM token if provided
$fcm_token = sanitize_string($input['fcm_token'] ?? '');
if (!empty($fcm_token)) {
    $stmt3 = $pdo->prepare("UPDATE employees SET fcm_token = :token WHERE id = :id");
    $stmt3->execute([':token' => $fcm_token, ':id' => $employee_id]);
}

success_response('Device registered successfully');
