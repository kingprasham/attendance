<?php
session_start();
define('BASE_URL', rtrim(str_repeat('../', substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__), '/')), '/') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Holidays';
$db        = get_db_connection();
$msg       = '';
$msgType   = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $branchId = (int)$_POST['branch_id'] ?: null;
        $stmt = $db->prepare("INSERT INTO holidays (name, date, branch_id, is_optional) VALUES (?,?,?,?)");
        $stmt->execute([
            trim($_POST['name']),
            $_POST['date'],
            $branchId,
            isset($_POST['is_optional']) ? 1 : 0,
        ]);
        $msg = 'Holiday added.';

    } elseif ($action === 'delete') {
        $db->prepare("DELETE FROM holidays WHERE id = ?")->execute([(int)$_POST['holiday_id']]);
        $msg = 'Holiday deleted.';
        $msgType = 'warning';
    }
}

$filterYear = (int)($_GET['year'] ?? date('Y'));
$branches   = $db->query("SELECT id, name FROM branches WHERE is_active=1 ORDER BY name")->fetchAll();

$holidays = $db->prepare("
    SELECT h.*, b.name AS branch_name
    FROM holidays h
    LEFT JOIN branches b ON h.branch_id = b.id
    WHERE YEAR(h.date) = ?
    ORDER BY h.date ASC
");
$holidays->execute([$filterYear]);
$holidays = $holidays->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
        <?= htmlspecialchars($msg) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form class="d-flex gap-2">
        <select name="year" class="form-select form-select-sm">
            <?php for ($y = date('Y') + 1; $y >= date('Y') - 1; $y--): ?>
                <option value="<?= $y ?>" <?= $y == $filterYear ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <button class="btn btn-secondary btn-sm">Filter</button>
    </form>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#holidayModal">
        <i class="bi bi-plus-circle me-1"></i>Add Holiday
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Date</th><th>Day</th><th>Name</th><th>Branch</th><th>Type</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($holidays as $h): ?>
                <tr>
                    <td class="fw-semibold"><?= date('d M Y', strtotime($h['date'])) ?></td>
                    <td class="text-muted"><?= date('l', strtotime($h['date'])) ?></td>
                    <td><?= htmlspecialchars($h['name']) ?></td>
                    <td><?= $h['branch_name'] ? htmlspecialchars($h['branch_name']) : '<span class="badge bg-secondary-subtle text-secondary">All Branches</span>' ?></td>
                    <td>
                        <?php if ($h['is_optional']): ?>
                            <span class="badge bg-warning-subtle text-warning">Optional</span>
                        <?php else: ?>
                            <span class="badge bg-primary-subtle text-primary">Mandatory</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="holiday_id" value="<?= $h['id'] ?>">
                            <button class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('Delete this holiday?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($holidays)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No holidays for <?= $filterYear ?>.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="holidayModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Holiday</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Holiday Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Diwali">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date *</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select">
                            <option value="">All Branches</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="is_optional" class="form-check-input" id="isOptional">
                            <label class="form-check-label" for="isOptional">Optional holiday</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Holiday</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
