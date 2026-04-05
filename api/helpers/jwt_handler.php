<?php
// api/helpers/jwt_handler.php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

// Load php-jwt library
require_once __DIR__ . '/../lib/php-jwt/JWTExceptionWithPayloadInterface.php';
require_once __DIR__ . '/../lib/php-jwt/BeforeValidException.php';
require_once __DIR__ . '/../lib/php-jwt/ExpiredException.php';
require_once __DIR__ . '/../lib/php-jwt/SignatureInvalidException.php';
require_once __DIR__ . '/../lib/php-jwt/JWT.php';
require_once __DIR__ . '/../lib/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

/**
 * Generate access + refresh token pair.
 */
function generate_tokens($user_id, $user_type = 'employee') {
    $now = time();

    // Access token
    $access_payload = [
        'iss' => 'kalina-attendance',
        'sub' => $user_id,
        'type' => $user_type,
        'iat' => $now,
        'exp' => $now + JWT_ACCESS_EXPIRY,
    ];
    $access_token = JWT::encode($access_payload, JWT_SECRET, JWT_ALGORITHM);

    // Refresh token (random string stored hashed in DB)
    $refresh_token = bin2hex(random_bytes(32));
    $refresh_hash = hash('sha256', $refresh_token);

    // Store refresh token in DB
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        "INSERT INTO refresh_tokens (user_id, user_type, token_hash, expires_at)
         VALUES (:user_id, :user_type, :hash, :expires)"
    );
    $stmt->execute([
        ':user_id' => $user_id,
        ':user_type' => $user_type,
        ':hash' => $refresh_hash,
        ':expires' => date('Y-m-d H:i:s', $now + JWT_REFRESH_EXPIRY),
    ]);

    return [
        'access_token' => $access_token,
        'refresh_token' => $refresh_token,
        'expires_in' => JWT_ACCESS_EXPIRY,
    ];
}

/**
 * Decode and validate access token from Authorization header.
 * Returns decoded payload or calls error_response.
 */
function decode_access_token() {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($auth_header) || !preg_match('/Bearer\s+(.+)/', $auth_header, $matches)) {
        error_response('Missing or invalid Authorization header', 401);
    }

    $token = $matches[1];

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
        return (array)$decoded;
    } catch (ExpiredException $e) {
        error_response('Token expired', 401);
    } catch (SignatureInvalidException $e) {
        error_response('Invalid token signature', 401);
    } catch (\Exception $e) {
        error_response('Invalid token', 401);
    }
}

/**
 * Validate refresh token and issue new token pair (rotation).
 */
function refresh_access_token($refresh_token) {
    $pdo = get_db_connection();
    $hash = hash('sha256', $refresh_token);

    $stmt = $pdo->prepare(
        "SELECT id, user_id, user_type, expires_at, revoked
         FROM refresh_tokens WHERE token_hash = :hash LIMIT 1"
    );
    $stmt->execute([':hash' => $hash]);
    $row = $stmt->fetch();

    if (!$row) {
        error_response('Invalid refresh token', 401);
    }
    if ($row['revoked']) {
        // Possible token reuse — revoke all tokens for this user
        $stmt2 = $pdo->prepare(
            "UPDATE refresh_tokens SET revoked = 1
             WHERE user_id = :uid AND user_type = :utype"
        );
        $stmt2->execute([':uid' => $row['user_id'], ':utype' => $row['user_type']]);
        error_response('Refresh token reused — all sessions revoked', 401);
    }
    if (strtotime($row['expires_at']) < time()) {
        error_response('Refresh token expired', 401);
    }

    // Revoke old refresh token
    $stmt3 = $pdo->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE id = :id");
    $stmt3->execute([':id' => $row['id']]);

    // Generate new pair
    return generate_tokens($row['user_id'], $row['user_type']);
}
