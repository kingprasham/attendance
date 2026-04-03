<?php
// api/endpoints/employees/update.php
// PUT /employees/update — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/encryption.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['id']);

$pdo = get_db_connection();
$fields = [];
$params = [':id' => (int)$input['id']];

$plain_fields = ['full_name', 'email', 'phone', 'designation', 'department',
                 'branch_id', 'date_of_joining', 'employment_type', 'monthly_salary',
                 'bank_account', 'ifsc_code', 'is_active'];

foreach ($plain_fields as $field) {
    if (isset($input[$field])) {
        $fields[] = "{$field} = :{$field}";
        $params[":{$field}"] = is_numeric($input[$field]) ? $input[$field] : sanitize_string($input[$field]);
    }
}

// Encrypted fields
if (isset($input['pan_number'])) {
    $fields[] = "pan_number = :pan";
    $params[':pan'] = aes_encrypt(sanitize_string($input['pan_number']));
}
if (isset($input['aadhar_number'])) {
    $fields[] = "aadhar_number = :aadhar";
    $params[':aadhar'] = aes_encrypt(sanitize_string($input['aadhar_number']));
}

// Password update
if (isset($input['password']) && !empty($input['password'])) {
    $fields[] = "password = :password";
    $params[':password'] = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
}

if (empty($fields)) {
    error_response('No fields to update', 400);
}

$sql = "UPDATE employees SET " . implode(', ', $fields) . " WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

if ($stmt->rowCount() === 0) {
    error_response('Employee not found', 404);
}

success_response('Employee updated');
