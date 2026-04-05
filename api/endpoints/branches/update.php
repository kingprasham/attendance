<?php
// api/endpoints/branches/update.php
// PUT /branches/update — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['id']);

$pdo = get_db_connection();

// Build dynamic update
$fields = [];
$params = [':id' => (int)$input['id']];

$allowed = ['name', 'address', 'latitude', 'longitude', 'radius_meters'];
foreach ($allowed as $field) {
    if (isset($input[$field])) {
        $fields[] = "{$field} = :{$field}";
        if ($field === 'radius_meters') {
            $params[":{$field}"] = (int)$input[$field];
        } elseif (in_array($field, ['latitude', 'longitude'])) {
            $params[":{$field}"] = (float)$input[$field];
        } else {
            $params[":{$field}"] = sanitize_string($input[$field]);
        }
    }
}

if (empty($fields)) {
    error_response('No fields to update', 400);
}

$sql = "UPDATE branches SET " . implode(', ', $fields) . " WHERE id = :id AND is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

if ($stmt->rowCount() === 0) {
    error_response('Branch not found', 404);
}

success_response('Branch updated');
