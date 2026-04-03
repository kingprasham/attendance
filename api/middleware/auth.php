<?php
// api/middleware/auth.php
// Verifies JWT and returns the employee_id. Call at top of protected endpoints.

require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/jwt_handler.php';

function require_auth() {
    $payload = decode_access_token();

    if ($payload['type'] !== 'employee') {
        error_response('Employee access required', 403);
    }

    return $payload['sub']; // employee_id
}
