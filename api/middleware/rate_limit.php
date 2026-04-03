<?php
// api/middleware/rate_limit.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../helpers/response.php';

function check_rate_limit($endpoint, $max_hits, $window_seconds) {
    $pdo = get_db_connection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $now = time();
    $window_start = date('Y-m-d H:i:s', $now - $window_seconds);

    // Clean old entries
    $pdo->prepare("DELETE FROM rate_limits WHERE window_start < :cutoff")
        ->execute([':cutoff' => $window_start]);

    // Count hits in current window
    $stmt = $pdo->prepare(
        "SELECT SUM(hits) as total FROM rate_limits
         WHERE ip_address = :ip AND endpoint = :ep AND window_start >= :ws"
    );
    $stmt->execute([':ip' => $ip, ':ep' => $endpoint, ':ws' => $window_start]);
    $total = (int)($stmt->fetch()['total'] ?? 0);

    if ($total >= $max_hits) {
        $retry_after = $window_seconds - ($now - strtotime($window_start));
        header("Retry-After: {$retry_after}");
        error_response('Rate limit exceeded. Try again later.', 429);
    }

    // Record this hit
    $stmt2 = $pdo->prepare(
        "INSERT INTO rate_limits (ip_address, endpoint, hits, window_start)
         VALUES (:ip, :ep, 1, NOW())
         ON DUPLICATE KEY UPDATE hits = hits + 1"
    );
    $stmt2->execute([':ip' => $ip, ':ep' => $endpoint]);
}

function rate_limit_login() {
    check_rate_limit('login', RATE_LIMIT_LOGIN, RATE_LIMIT_LOGIN_WINDOW);
}

function rate_limit_attendance() {
    check_rate_limit('attendance', RATE_LIMIT_ATTENDANCE, RATE_LIMIT_ATTENDANCE_WINDOW);
}

function rate_limit_general() {
    check_rate_limit('general', RATE_LIMIT_GENERAL, RATE_LIMIT_GENERAL_WINDOW);
}
