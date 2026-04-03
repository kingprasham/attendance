<?php
session_start();
define('BASE_URL', rtrim(str_repeat('../', substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__), '/')), '/') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Attendance Report';
$db        = get_db_connection();

$selectedDate   = $_GET['date']   ?? gmdate('Y-m-d');
$selectedBranch = (int)($_GET['branch'] ?? 0);

$branches = $db->query("SELECT id, name FROM branches WHERE is_active=1 ORDER BY name")->fetchAll();

// Build query
$params = [$selectedDate];
$branchJoin = '';
$branchWhere = '';
if ($selectedBranch) {
    $branchWhere = 'AND e.branch_id = ?';
    $params[] = $selectedBranch;
}

// All active employees for the selected branch
$empSql = "SELECT e.id, e.full_name, e.employee_code, b.name AS branch_name
           FROM employees e JOIN branches b ON e.branch_id = b.id
           WHERE e.is_active = 1 $branchWhere
           ORDER BY e.employee_code";
$empStmt = $db->prepare($empSql);
$empStmt->execute($selectedBranch ? [$selectedBranch] : []);
$employees = $empStmt->fetchAll();

// Attendance logs for that date
$logSql = "SELECT al.*, e.id AS eid
           FROM attendance_logs al
           JOIN employees e ON al.employee_id = e.id
           WHERE DATE(al.clock_in_time) = ? $branchWhere";
$logStmt = $db->prepare($logSql);
$logStmt->execute($params);
$logs = [];
foreach ($logStmt->fetchAll() as $row) {
    $logs[$row['eid']] = $row;
}

// Summary
$present  = 0; $late = 0; $halfDay = 0; $absent = 0;
foreach ($employees as $emp) {
    if (!isset($logs[$emp['id']])) { $absent++; continue; }
    $s = $logs[$emp['id']]['status'];
    if ($s === 'present') $present++;
    elseif ($s === 'late') $late++;
    elseif ($s === 'half_day') $halfDay++;
}

// Check if it's a holiday
$holiday = $db->prepare("SELECT name FROM holidays WHERE date = ? AND (branch_id IS NULL OR branch_id = ?) LIMIT 1");
$holiday->execute([$selectedDate, $selectedBranch ?: 0]);
$holidayName = $holiday->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="col-form-label fw-semibold small">Date</label>
            </div>
            <div class="col-md-3">
                <input type="date" name="date" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($selectedDate) ?>" max="<?= gmdate('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <select name="branch" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $selectedBranch == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm">View Report</button>
            </div>
        </form>
    </div>
</div>

<?php if ($holidayName): ?>
    <div class="alert alert-info py-2">
        <i class="bi bi-umbrella me-2"></i>
        <strong><?= htmlspecialchars($holidayName) ?></strong> — This day is a holiday.
    </div>
<?php endif; ?>

<!-- Summary -->
<div class="row g-3 mb-3">
    <?php foreach ([
        ['Present', $present,  'success'],
        ['Late',    $late,     'warning'],
        ['Half Day',$halfDay,  'info'],
        ['Absent',  $absent,   'danger'],
    ] as [$label, $count, $color]): ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-<?= $color ?>"><?= $count ?></div>
            <div class="text-muted small"><?= $label ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Report Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">
            <i class="bi bi-table me-2 text-primary"></i>
            Report for <?= date('d M Y', strtotime($selectedDate)) ?>
        </span>
        <small class="text-muted"><?= count($employees) ?> employee(s)</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Employee</th>
                        <th>Branch</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($employees as $emp):
                    $log    = $logs[$emp['id']] ?? null;
                    $status = $log ? $log['status'] : 'absent';
                    $badges = ['present'=>'success','late'=>'warning','half_day'=>'info','absent'=>'danger'];
                    $badge  = $badges[$status] ?? 'secondary';
                    // Convert UTC clock times to IST for display
                    $cinIST  = $log && $log['clock_in_time']  ? date('h:i A', strtotime($log['clock_in_time'])  + 19800) : '—';
                    $coutIST = $log && $log['clock_out_time'] ? date('h:i A', strtotime($log['clock_out_time']) + 19800) : '—';
                ?>
                <tr>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($emp['employee_code']) ?></span></td>
                    <td class="fw-semibold"><?= htmlspecialchars($emp['full_name']) ?></td>
                    <td><?= htmlspecialchars($emp['branch_name']) ?></td>
                    <td><?= $cinIST ?></td>
                    <td><?= $coutIST ?></td>
                    <td><?= $log ? number_format((float)$log['work_hours'], 1) . 'h' : '—' ?></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= ucfirst(str_replace('_',' ',$status)) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($employees)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No employees found for this selection.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
