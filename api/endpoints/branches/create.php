<?php
// api/endpoints/branches/create.php
// POST /branches/create — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['name', 'address', 'latitude', 'longitude', 'radius_meters']);
validate_positive_number($input['latitude'], 'latitude');
validate_positive_number($input['radius_meters'], 'radius_meters');

$pdo = get_db_connection();
$stmt = $pdo->prepare(
    "INSERT INTO branches (name, address, latitude, longitude, radius_meters)
     VALUES (:name, :address, :lat, :lng, :radius)"
);
$stmt->execute([
    ':name' => sanitize_string($input['name']),
    ':address' => sanitize_string($input['address']),
    ':lat' => $input['latitude'],
    ':lng' => $input['longitude'],
    ':radius' => (int)$input['radius_meters'],
]);

success_response('Branch created', ['id' => $pdo->lastInsertId()], 201);
