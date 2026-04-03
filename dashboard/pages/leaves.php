<?php
session_start();
define('BASE_URL', rtrim(str_repeat('../', substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__), '/')), '/') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Leave Requests';
$db        = get_db_connection();
$msg       = '';
$msgType   = 'success';

// Handle approve / reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $leaveId = (int)($_POST['leave_id'] ?? 0);

    if ($leaveId && in_array($action, ['approve', 'reject'])) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $db->beginTransaction();
        try {
            $leave = $db->prepare("SELECT * FROM leave_requests WHERE id = ? AND status = 'pending'")->execute([$leaveId]);
            $leave = $db->prepare("SELECT * FROM leave_requests WHERE id = ? AND status = 'pending'");
            $leave->execute([$leaveId]);
            $leaveRow = $leave->fetch();

            if ($leaveRow) {
                $db->prepare("UPDATE leave_requests SET status = ?, reviewed_at = UTC_TIMESTAMP() WHERE id = ?")
                   ->execute([$newStatus, $leaveId]);

                if ($newStatus === 'approved') {
                    // Increment used balance
                    $days = (strtotime($leaveRow['end_date']) - strtotime($leaveRow['start_date'])) / 86400 + 1;
                    if ($leaveRow['is_half_day']) $days = 0.5;
                    $db->prepare("UPDATE leave_balances SET used = used + ? WHERE employee_id = ? AND leave_type = ?")
                       ->execute([$days, $leaveRow['employee_id'], $leaveRow['leave_type']]);
                }

                // Notify employee
                $notifTitle = $newStatus === 'approved' ? 'Leave Approved' : 'Leave Rejected';
                $notifBody  = "Your {$leaveRow['leave_type']} leave from {$leaveRow['start_date']} to {$leaveRow['end_date']} has been {$newStatus}.";
                $db->prepare("INSERT INTO notifications (employee_id, title, body, type) VALUES (?,?,?,'leave')")
                   ->execute([$leaveRow['employee_id'], $notifTitle, $notifBody]);

                $db->commit();
                $msg = "Leave request {$newStatus}.";
                if ($newStatus === 'rejected') $msgType = 'warning';
            } else {
                $db->rollBack();
                $msg = 'Leave request not found or already reviewed.';
                $msgType = 'danger';
            }
        } catch (Exception $e) {
            $db->rollBack();
            $msg = 'Error: ' . $e->getMessage();
            $msgType = 'danger';
        }
    }
}

$tab = $_GET['tab'] ?? 'pending';

// Pending
$pending = $db->query("
    SELECT lr.*, e.full_name, e.employee_code, b.name AS branch_name
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    JOIN branches b ON e.branch_id = b.id
    WHERE lr.status = 'pending'
    ORDER BY lr.created_at ASC
")->fetchAll();

// History (recent 100)
$history = $db->query("
    SELECT lr.*, e.full_name, e.employee_code, b.name AS branch_name
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    JOIN branches b ON e.branch_id = b.id
    WHERE lr.status != 'pending'
    ORDER BY lr.reviewed_at DESC
    LIMIT 100
")->fetchAll();

$leaveTypeNames = ['CL'=>'Casual','SL'=>'Sick','EL'=>'Earned','CO'=>'Comp Off','LWP'=>'LWP'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
        <?= htmlspecialchars($msg) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'pending' ? 'active' : '' ?>" href="?tab=pending">
            Pending
            <?php if (count($pending)): ?>
                <span class="badge bg-danger ms-1"><?= count($pending) ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'history' ? 'active' : '' ?>" href="?tab=history">History</a>
    </li>
</ul>

<?php if ($tab === 'pending'): ?>
    <?php if (empty($pending)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>
                No pending leave requests.
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
        <?php foreach ($pending as $lr): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($lr['full_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($lr['employee_code']) ?> · <?= htmlspecialchars($lr['branch_name']) ?></small>
                            </div>
                            <span class="badge bg-primary">
                                <?= $leaveTypeNames[$lr['leave_type']] ?? $lr['leave_type'] ?>
                            </span>
                        </div>
                        <div class="mb-2">
                            <i class="bi bi-calendar-range me-1 text-muted"></i>
                            <?= date('d M', strtotime($lr['start_date'])) ?>
                            <?= $lr['start_date'] !== $lr['end_date'] ? ' → ' . date('d M Y', strtotime($lr['end_date'])) : ', ' . date('Y') ?>
                            <?php if ($lr['is_half_day']): ?>
                                <span class="badge bg-info-subtle text-info ms-1">Half Day</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($lr['reason']): ?>
                            <p class="text-muted small mb-2"><?= htmlspecialchars($lr['reason']) ?></p>
                        <?php endif; ?>
                        <small class="text-muted">Applied: <?= date('d M Y', strtotime($lr['created_at'])) ?></small>
                    </div>
                    <div class="card-footer bg-transparent border-0 d-flex gap-2">
                        <form method="POST" class="flex-fill">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="leave_id" value="<?= $lr['id'] ?>">
                            <input type="hidden" name="tab" value="pending">
                            <button class="btn btn-success btn-sm w-100"
                                    onclick="return confirm('Approve this leave request?')">
                                <i class="bi bi-check-lg me-1"></i>Approve
                            </button>
                        </form>
                        <form method="POST" class="flex-fill">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="leave_id" value="<?= $lr['id'] ?>">
                            <input type="hidden" name="tab" value="pending">
                            <button class="btn btn-outline-danger btn-sm w-100"
                                    onclick="return confirm('Reject this leave request?')">
                                <i class="bi bi-x-lg me-1"></i>Reject
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th><th>Type</th><th>From</th><th>To</th>
                            <th>Status</th><th>Reviewed</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history as $lr):
                        $badges = ['approved'=>'success','rejected'=>'danger','cancelled'=>'secondary'];
                        $badge  = $badges[$lr['status']] ?? 'secondary';
                    ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($lr['full_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($lr['employee_code']) ?></small>
                            </td>
                            <td><?= $leaveTypeNames[$lr['leave_type']] ?? $lr['leave_type'] ?></td>
                            <td><?= date('d M Y', strtotime($lr['start_date'])) ?></td>
                            <td><?= date('d M Y', strtotime($lr['end_date'])) ?></td>
                            <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($lr['status']) ?></span></td>
                            <td><?= $lr['reviewed_at'] ? date('d M Y', strtotime($lr['reviewed_at'])) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No history yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
