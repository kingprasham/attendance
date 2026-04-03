<?php
// api/endpoints/auth/refresh.php
// POST /auth/refresh — Refresh access token

require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/jwt_handler.php';

$input = get_json_input();
validate_required($input, ['refresh_token']);

$tokens = refresh_access_token($input['refresh_token']);

success_response('Token refreshed', $tokens);
