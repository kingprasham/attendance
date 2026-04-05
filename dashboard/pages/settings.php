<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Settings';
$db        = get_db_connection();
$msg       = '';
$msgType   = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current    = $_POST['current_password'] ?? '';
        $newPwd     = $_POST['new_password'] ?? '';
        $confirmPwd = $_POST['confirm_password'] ?? '';

        if (!$current || !$newPwd || !$confirmPwd) {
            $msg = 'All fields are required.'; $msgType = 'danger';
        } elseif ($newPwd !== $confirmPwd) {
            $msg = 'New passwords do not match.'; $msgType = 'danger';
        } elseif (strlen($newPwd) < 8) {
            $msg = 'Password must be at least 8 characters.'; $msgType = 'danger';
        } else {
            $admin = $db->prepare("SELECT password FROM admins WHERE id = ?");
            $admin->execute([$_SESSION['admin_id']]);
            $row = $admin->fetch();

            if (!$row || !password_verify($current, $row['password'])) {
                $msg = 'Current password is incorrect.'; $msgType = 'danger';
            } else {
                $hash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare("UPDATE admins SET password = ? WHERE id = ?")
                   ->execute([$hash, $_SESSION['admin_id']]);
                $msg = 'Password updated successfully.';
            }
        }
    }
}

// Fetch admin info
$admin = $db->prepare("SELECT email, created_at FROM admins WHERE id = ?");
$admin->execute([$_SESSION['admin_id']]);
$adminInfo = $admin->fetch();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
        <?= htmlspecialchars($msg) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Admin Info -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-person-circle me-2 text-primary"></i>Admin Account
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Email</div>
                    <div class="fw-semibold"><?= htmlspecialchars($adminInfo['email'] ?? '') ?></div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Account Created</div>
                    <div><?= $adminInfo['created_at'] ? date('d M Y', strtotime($adminInfo['created_at'])) : '—' ?></div>
                </div>
                <div>
                    <div class="text-muted small">Role</div>
                    <span class="badge bg-primary">Super Admin</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-lock me-2 text-primary"></i>Change Password
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="new_password" class="form-control"
                               minlength="8" required>
                        <div class="form-text">Minimum 8 characters.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-info-circle me-2 text-primary"></i>System Information
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php
                    $counts = [
                        ['Branches',   $db->query("SELECT COUNT(*) FROM branches WHERE is_active=1")->fetchColumn(), 'bi-geo-alt', 'primary'],
                        ['Employees',  $db->query("SELECT COUNT(*) FROM employees WHERE is_active=1")->fetchColumn(), 'bi-people', 'success'],
                        ['Attendance Logs', $db->query("SELECT COUNT(*) FROM attendance_logs")->fetchColumn(), 'bi-clock-history', 'info'],
                        ['Leave Requests',  $db->query("SELECT COUNT(*) FROM leave_requests")->fetchColumn(), 'bi-calendar-x', 'warning'],
                        ['Salary Slips',    $db->query("SELECT COUNT(*) FROM salary_slips")->fetchColumn(), 'bi-receipt', 'secondary'],
                    ];
                    foreach ($counts as [$label, $count, $icon, $color]): ?>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="text-center p-3 rounded bg-light">
                            <i class="bi <?= $icon ?> fs-4 text-<?= $color ?>"></i>
                            <div class="fs-4 fw-bold mt-1"><?= number_format($count) ?></div>
                            <div class="text-muted small"><?= $label ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
