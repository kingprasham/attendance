<?php
// api/middleware/admin_auth.php
// Verifies JWT and checks admin type.

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/jwt_handler.php';

function require_admin() {
    $payload = decode_access_token();

    if ($payload['type'] !== 'admin') {
        error_response('Admin access required', 403);
    }

    return $payload['sub']; // admin_id
}
