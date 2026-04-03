<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Leave Policies';
$db        = get_db_connection();
$msg       = '';
$msgType   = 'success';

$leaveTypes = ['CL' => 'Casual Leave', 'SL' => 'Sick Leave', 'EL' => 'Earned Leave', 'CO' => 'Compensatory Off', 'LWP' => 'Leave Without Pay'];
$branches   = $db->query("SELECT id, name FROM branches WHERE is_active=1 ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $branchId = (int)$_POST['branch_id'];
    if ($branchId) {
        foreach ($leaveTypes as $type => $label) {
            $quota    = (int)($_POST["quota_{$type}"] ?? 0);
            $carryFwd = (int)($_POST["carry_{$type}"] ?? 0);
            $db->prepare("
                INSERT INTO leave_policies (branch_id, leave_type, annual_quota, carry_forward_limit)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE annual_quota = VALUES(annual_quota), carry_forward_limit = VALUES(carry_forward_limit)
            ")->execute([$branchId, $type, $quota, $carryFwd]);
        }
        $msg = 'Leave policies saved for selected branch.';
    }
}

$selectedBranch = (int)($_GET['branch'] ?? ($_POST['branch_id'] ?? 0));
if (!$selectedBranch && !empty($branches)) {
    $selectedBranch = $branches[0]['id'];
}

$policies = [];
if ($selectedBranch) {
    $stmt = $db->prepare("SELECT * FROM leave_policies WHERE branch_id = ?");
    $stmt->execute([$selectedBranch]);
    foreach ($stmt->fetchAll() as $p) {
        $policies[$p['leave_type']] = $p;
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
        <?= htmlspecialchars($msg) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Branch Selector -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <label class="fw-semibold col-form-label small">Branch</label>
            <select name="branch" class="form-select form-select-sm" style="max-width:260px"
                    onchange="this.form.submit()">
                <?php foreach ($branches as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $selectedBranch == $b['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($selectedBranch): ?>
<form method="POST">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="branch_id" value="<?= $selectedBranch ?>">

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-journal-text me-2 text-primary"></i>
            Leave Quotas — <?= htmlspecialchars(array_column($branches, 'name', 'id')[$selectedBranch] ?? '') ?>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40%">Leave Type</th>
                        <th>Annual Quota (days)</th>
                        <th>Carry Forward Limit</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($leaveTypes as $type => $label): ?>
                    <tr>
                        <td class="fw-semibold align-middle">
                            <?= $label ?>
                            <span class="badge bg-secondary ms-1"><?= $type ?></span>
                        </td>
                        <td>
                            <input type="number" name="quota_<?= $type ?>"
                                   class="form-control form-control-sm"
                                   style="max-width:100px" min="0" max="365"
                                   value="<?= $policies[$type]['annual_quota'] ?? 0 ?>">
                        </td>
                        <td>
                            <input type="number" name="carry_<?= $type ?>"
                                   class="form-control form-control-sm"
                                   style="max-width:100px" min="0" max="365"
                                   value="<?= $policies[$type]['carry_forward_limit'] ?? 0 ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent border-0">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i>Save Policies
            </button>
            <small class="text-muted ms-3">Changes apply to new employees. Existing balances are not retroactively adjusted.</small>
        </div>
    </div>
</form>
<?php else: ?>
    <div class="alert alert-info">Please create at least one branch first.</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
