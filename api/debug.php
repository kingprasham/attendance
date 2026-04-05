<?php
// debug.php — Upload to api/ folder, visit in browser, DELETE after done
// Access: https://yoursite.com/attendance/api/debug.php?key=debug2026

if (($_GET['key'] ?? '') !== 'debug2026') {
    http_response_code(403);
    die('403 Forbidden. Add ?key=debug2026 to the URL.');
}

// Show ALL PHP errors on screen
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

$out = '';

function row_pass($label, $detail = '') {
    return "<tr><td><b>" . htmlspecialchars($label) . "</b></td><td style='color:green'>PASS</td><td>" . htmlspecialchars($detail) . "</td></tr>\n";
}
function row_fail($label, $detail = '') {
    return "<tr><td><b>" . htmlspecialchars($label) . "</b></td><td style='color:red'>FAIL</td><td><pre style='color:red;margin:0'>" . htmlspecialchars($detail) . "</pre></td></tr>\n";
}
function row_info($label, $detail = '') {
    return "<tr><td><b>" . htmlspecialchars($label) . "</b></td><td style='color:#555'>INFO</td><td>" . htmlspecialchars($detail) . "</td></tr>\n";
}
function section($title) {
    return "<h2 style='background:#0055A4;color:#fff;padding:6px 12px;margin-top:28px'>$title</h2><table style='width:100%;border-collapse:collapse;background:#fff;margin-bottom:8px'>\n";
}

// ── 1. PHP ENVIRONMENT ────────────────────────────────────────────────────
$out .= section('1. PHP Environment');
$out .= row_info('PHP version', PHP_VERSION);
$out .= row_info('Server', $_SERVER['SERVER_SOFTWARE'] ?? 'unknown');
foreach (['pdo', 'pdo_mysql', 'openssl', 'json', 'mbstring'] as $ext) {
    $out .= extension_loaded($ext) ? row_pass("ext: $ext") : row_fail("ext: $ext", "Not loaded");
}
$out .= '</table>';

// ── 2. DATABASE ───────────────────────────────────────────────────────────
$out .= section('2. Database Connection');
$pdo = null;
try {
    require_once __DIR__ . '/config/database.php';
    $pdo = get_db_connection();
    $out .= row_pass('DB connect');
    $row = $pdo->query("SELECT VERSION() as v, DATABASE() as d")->fetch();
    $out .= row_info('MySQL version', $row['v']);
    $out .= row_info('Database', $row['d']);
} catch (Throwable $e) {
    $out .= row_fail('DB connect', $e->getMessage());
}
$out .= '</table>';

if ($pdo !== null) {

    // ── 3. TABLES ─────────────────────────────────────────────────────────
    $out .= section('3. Tables');
    $needed = ['admins','branches','employees','attendance_logs','leave_policies',
               'leave_balances','leave_requests','salary_slips','holidays',
               'notifications','rate_limits','refresh_tokens'];
    $existing = array_column($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM), 0);
    foreach ($needed as $t) {
        if (in_array($t, $existing)) {
            $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            $out .= row_pass($t, $cnt . ' rows');
        } else {
            $out .= row_fail($t, 'Table does not exist');
        }
    }
    $out .= '</table>';

    // ── 4. COLUMNS ────────────────────────────────────────────────────────
    $out .= section('4. Schema Columns');
    $col_checks = [
        'branches'       => ['late_threshold_hour', 'late_threshold_minute'],
        'employees'      => ['employment_type', 'email'],
        'leave_requests' => ['reviewed_at'],
        'holidays'       => ['branch_id'],
    ];
    foreach ($col_checks as $tbl => $cols) {
        if (!in_array($tbl, $existing)) {
            $out .= row_fail($tbl, 'Table missing');
            continue;
        }
        $have = array_column($pdo->query("DESCRIBE `$tbl`")->fetchAll(), 'Field');
        foreach ($cols as $col) {
            $out .= in_array($col, $have) ? row_pass("$tbl.$col") : row_fail("$tbl.$col", 'Column missing — run ALTER');
        }
    }
    $out .= '</table>';

    // ── 5. EMPLOYEES ──────────────────────────────────────────────────────
    $out .= section('5. Employees in DB');
    $emps = $pdo->query("SELECT id, full_name, email, is_active, device_id FROM employees LIMIT 10")->fetchAll();
    if (empty($emps)) {
        $out .= row_fail('Employees', 'Table is empty');
    } else {
        foreach ($emps as $e) {
            $out .= row_info("ID {$e['id']}: {$e['full_name']}", "email={$e['email']} | active={$e['is_active']} | device=" . ($e['device_id'] ? 'bound' : 'NULL'));
        }
    }
    $out .= '</table>';

    // ── 6. JWT ────────────────────────────────────────────────────────────
    $out .= section('6. JWT Library');
    try {
        require_once __DIR__ . '/config/constants.php';
        require_once __DIR__ . '/lib/php-jwt/JWT.php';
        require_once __DIR__ . '/lib/php-jwt/Key.php';
        $out .= row_pass('php-jwt files load');

        $payload = ['sub' => 1, 'exp' => time() + 60, 'iat' => time()];
        $token = Firebase\JWT\JWT::encode($payload, JWT_SECRET, JWT_ALGORITHM);
        $out .= row_pass('JWT encode', substr($token, 0, 40) . '...');

        $decoded = Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key(JWT_SECRET, JWT_ALGORITHM));
        $out .= ((int)$decoded->sub === 1) ? row_pass('JWT decode') : row_fail('JWT decode', 'sub mismatch');
    } catch (Throwable $e) {
        $out .= row_fail('JWT', $e->getMessage());
    }
    $out .= '</table>';

    // ── 7. RATE LIMITS WRITE ──────────────────────────────────────────────
    $out .= section('7. Rate Limits Write Test');
    try {
        $pdo->exec("INSERT INTO rate_limits (ip_address, endpoint, hits, window_start) VALUES ('0.0.0.0','debug_test',1,NOW())");
        $pdo->exec("DELETE FROM rate_limits WHERE ip_address='0.0.0.0' AND endpoint='debug_test'");
        $out .= row_pass('rate_limits INSERT + DELETE');
    } catch (Throwable $e) {
        $out .= row_fail('rate_limits write', $e->getMessage());
    }
    $out .= '</table>';

    // ── 8. LOGIN SIMULATION ───────────────────────────────────────────────
    $out .= '<h2 style="background:#0055A4;color:#fff;padding:6px 12px;margin-top:28px">8. Login Simulation</h2>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sim_email'])) {
        $out .= '<table style="width:100%;border-collapse:collapse;background:#fff;margin-bottom:8px">';
        $sim_email = trim($_POST['sim_email']);
        $sim_pass  = $_POST['sim_pass'];

        try {
            $stmt = $pdo->prepare("SELECT id, full_name, password, device_id, branch_id, is_active FROM employees WHERE email = :e LIMIT 1");
            $stmt->execute([':e' => $sim_email]);
            $emp = $stmt->fetch();

            if (!$emp) {
                $all_emails = implode(', ', array_column($pdo->query("SELECT email FROM employees")->fetchAll(), 'email'));
                $out .= row_fail('Find by email', "No employee with email: $sim_email\nAll emails in DB: $all_emails");
            } else {
                $out .= row_pass('Find by email', "id={$emp['id']} name={$emp['full_name']}");
                $out .= $emp['is_active'] ? row_pass('is_active') : row_fail('is_active', 'Account deactivated');

                if (password_verify($sim_pass, $emp['password'])) {
                    $out .= row_pass('password_verify');
                } else {
                    $new_hash = password_hash($sim_pass, PASSWORD_BCRYPT, ['cost' => 12]);
                    $out .= row_fail('password_verify', "Password does not match hash in DB.\n\nTo fix, run in phpMyAdmin:\nUPDATE employees SET password='$new_hash' WHERE id={$emp['id']};");
                }

                try {
                    require_once __DIR__ . '/helpers/jwt_handler.php';
                    $tokens = generate_tokens($emp['id'], 'employee');
                    $out .= row_pass('generate_tokens', 'refresh_tokens row written OK');
                } catch (Throwable $e) {
                    $out .= row_fail('generate_tokens', $e->getMessage());
                }
            }
        } catch (Throwable $e) {
            $out .= row_fail('DB query', $e->getMessage());
        }
        $out .= '</table>';
    }

    $out .= '<form method="POST" action="?key=debug2026" style="background:#fff;padding:16px;border:1px solid #ccc;max-width:420px">
        <b>Simulate Login</b><br><br>
        Email<br><input type="email" name="sim_email" style="width:100%;padding:6px;box-sizing:border-box;margin:4px 0 10px" value="' . htmlspecialchars($_POST['sim_email'] ?? '') . '"><br>
        Password (plain text)<br><input type="text" name="sim_pass" style="width:100%;padding:6px;box-sizing:border-box;margin:4px 0 10px" value="' . htmlspecialchars($_POST['sim_pass'] ?? '') . '"><br>
        <button type="submit" style="padding:8px 20px;background:#0055A4;color:#fff;border:none;cursor:pointer">Run Login Test</button>
    </form>';
}

// ── OUTPUT ────────────────────────────────────────────────────────────────
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>API Debug</title>
<style>
body{font-family:monospace;padding:20px;background:#f0f0f0}
td{padding:7px 10px;border:1px solid #ccc;vertical-align:top}
td:first-child{width:260px} td:nth-child(2){width:60px;text-align:center}
pre{margin:0;white-space:pre-wrap;word-break:break-all;font-size:12px}
</style></head><body>
<h1>Kalina Engineering — API Debug</h1>
<p>Time: ' . date('Y-m-d H:i:s T') . '</p>' . $out . '
<p style="margin-top:40px;color:#888;font-size:12px">Delete this file once done debugging.</p>
</body></html>';
