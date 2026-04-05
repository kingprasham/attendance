<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Dashboard';
$db        = get_db_connection();
$today     = gmdate('Y-m-d');

// Today's attendance summary
$stmt = $db->prepare("
    SELECT
        COUNT(*) AS total_clocked_in,
        SUM(status = 'present')  AS present,
        SUM(status = 'late')     AS late,
        SUM(status = 'half_day') AS half_day
    FROM attendance_logs
    WHERE date = ?
");
$stmt->execute([$today]);
$todayStats = $stmt->fetch();

// Total active employees
$totalEmp = $db->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn();

// Absent today = active employees − clocked in today
$absentToday = max(0, $totalEmp - ($todayStats['total_clocked_in'] ?? 0));

// Pending leaves count
$pendingLeaves = $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn();

// Total branches
$totalBranches = $db->query("SELECT COUNT(*) FROM branches WHERE is_active = 1")->fetchColumn();

// ── Detail data for modals ──────────────────────────────

// Present employees today
$presentStmt = $db->prepare("
    SELECT e.full_name, e.employee_code, b.name AS branch_name,
           al.clock_in, al.clock_out, al.status
    FROM attendance_logs al
    JOIN employees e ON al.employee_id = e.id
    JOIN branches  b ON e.branch_id = b.id
    WHERE al.date = ? AND al.status = 'present'
    ORDER BY al.clock_in ASC
");
$presentStmt->execute([$today]);
$presentEmployees = $presentStmt->fetchAll();

// Late employees today
$lateStmt = $db->prepare("
    SELECT e.full_name, e.employee_code, b.name AS branch_name,
           al.clock_in, al.clock_out, al.status
    FROM attendance_logs al
    JOIN employees e ON al.employee_id = e.id
    JOIN branches  b ON e.branch_id = b.id
    WHERE al.date = ? AND al.status = 'late'
    ORDER BY al.clock_in ASC
");
$lateStmt->execute([$today]);
$lateEmployees = $lateStmt->fetchAll();

// Half-day employees today
$halfDayStmt = $db->prepare("
    SELECT e.full_name, e.employee_code, b.name AS branch_name,
           al.clock_in, al.clock_out, al.status
    FROM attendance_logs al
    JOIN employees e ON al.employee_id = e.id
    JOIN branches  b ON e.branch_id = b.id
    WHERE al.date = ? AND al.status = 'half_day'
    ORDER BY al.clock_in ASC
");
$halfDayStmt->execute([$today]);
$halfDayEmployees = $halfDayStmt->fetchAll();

// Absent employees today (active, not in attendance_logs for today)
$absentStmt = $db->prepare("
    SELECT e.full_name, e.employee_code, b.name AS branch_name
    FROM employees e
    JOIN branches b ON e.branch_id = b.id
    WHERE e.is_active = 1
      AND e.id NOT IN (
          SELECT employee_id FROM attendance_logs WHERE date = ?
      )
    ORDER BY e.full_name ASC
");
$absentStmt->execute([$today]);
$absentEmployees = $absentStmt->fetchAll();

// Pending leave requests
$pendingStmt = $db->query("
    SELECT lr.*, e.full_name, e.employee_code, b.name AS branch_name
    FROM leave_requests lr
    JOIN employees e ON lr.employee_id = e.id
    JOIN branches  b ON e.branch_id = b.id
    WHERE lr.status = 'pending'
    ORDER BY lr.created_at DESC
");
$pendingLeavesData = $pendingStmt->fetchAll();

// Recent attendance (last 10)
$recent = $db->prepare("
    SELECT al.*, e.full_name, e.employee_code, b.name AS branch_name
    FROM attendance_logs al
    JOIN employees e ON al.employee_id = e.id
    JOIN branches  b ON e.branch_id = b.id
    WHERE al.date = ?
    ORDER BY al.clock_in DESC
    LIMIT 10
");
$recent->execute([$today]);
$recentRows = $recent->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Helper: format UTC time to IST
function istTime(?string $utc): string {
    if (!$utc) return '—';
    return date('h:i A', strtotime($utc) + 19800);
}

// Helper: attendance rows table
function attendanceTable(array $rows): string {
    if (empty($rows)) return '<p class="text-muted text-center py-3 mb-0">No records.</p>';
    $html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">';
    $html .= '<thead class="table-light"><tr>
        <th>Employee</th><th>Branch</th><th>Clock In</th><th>Clock Out</th>
    </tr></thead><tbody>';
    foreach ($rows as $r) {
        $name = htmlspecialchars($r['full_name']);
        $code = htmlspecialchars($r['employee_code']);
        $branch = htmlspecialchars($r['branch_name']);
        $in  = istTime($r['clock_in']);
        $out = istTime($r['clock_out']);
        $html .= "<tr>
            <td><div class='fw-semibold'>$name</div><small class='text-muted'>$code</small></td>
            <td>$branch</td>
            <td>$in</td>
            <td>$out</td>
        </tr>";
    }
    $html .= '</tbody></table></div>';
    return $html;
}
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <!-- Present -->
    <div class="col-6 col-xl-3">
        <div class="stat-card card border-0 shadow-sm h-100 clickable-card"
             data-bs-toggle="modal" data-bs-target="#detailModal"
             data-modal-title='<i class="bi bi-person-check text-success me-2"></i>Present Today'
             data-modal-body="present"
             style="cursor:pointer">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-person-check fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Present Today</div>
                    <div class="fs-3 fw-bold"><?= (int)($todayStats['present'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Late -->
    <div class="col-6 col-xl-3">
        <div class="stat-card card border-0 shadow-sm h-100 clickable-card"
             data-bs-toggle="modal" data-bs-target="#detailModal"
             data-modal-title='<i class="bi bi-clock text-warning me-2"></i>Late Today'
             data-modal-body="late"
             style="cursor:pointer">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-clock fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Late Today</div>
                    <div class="fs-3 fw-bold"><?= (int)($todayStats['late'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Absent -->
    <div class="col-6 col-xl-3">
        <div class="stat-card card border-0 shadow-sm h-100 clickable-card"
             data-bs-toggle="modal" data-bs-target="#detailModal"
             data-modal-title='<i class="bi bi-person-x text-danger me-2"></i>Absent Today'
             data-modal-body="absent"
             style="cursor:pointer">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger-subtle text-danger">
                    <i class="bi bi-person-x fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Absent Today</div>
                    <div class="fs-3 fw-bold"><?= (int)$absentToday ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Leaves -->
    <div class="col-6 col-xl-3">
        <div class="stat-card card border-0 shadow-sm h-100 clickable-card"
             data-bs-toggle="modal" data-bs-target="#detailModal"
             data-modal-title='<i class="bi bi-calendar-x text-primary me-2"></i>Pending Leaves'
             data-modal-body="leaves"
             style="cursor:pointer">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-calendar-x fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small">Pending Leaves</div>
                    <div class="fs-3 fw-bold"><?= (int)$pendingLeaves ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary row -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="fs-2 fw-bold text-primary"><?= (int)$totalEmp ?></div>
                <div class="text-muted small">Total Employees</div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-3">
                <div class="fs-2 fw-bold text-primary"><?= (int)$totalBranches ?></div>
                <div class="text-muted small">Active Branches</div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm h-100 clickable-card"
             data-bs-toggle="modal" data-bs-target="#detailModal"
             data-modal-title='<i class="bi bi-clock-history text-info me-2"></i>Half Day Today'
             data-modal-body="halfday"
             style="cursor:pointer">
            <div class="card-body text-center py-3">
                <div class="fs-2 fw-bold" style="color:#0284c7"><?= (int)($todayStats['half_day'] ?? 0) ?></div>
                <div class="text-muted small">Half Day</div>
            </div>
        </div>
    </div>
</div>

<!-- Today's attendance table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-clock-history me-2 text-primary"></i>Today's Attendance
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentRows)): ?>
            <p class="text-muted text-center py-4 mb-0">No attendance records for today yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Branch</th>
                            <th>Clock In</th>
                            <th>Clock Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentRows as $r): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($r['full_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($r['employee_code']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($r['branch_name']) ?></td>
                            <td><?= istTime($r['clock_in']) ?></td>
                            <td><?= istTime($r['clock_out']) ?></td>
                            <td><?php
                                $badges = ['present'=>'success','late'=>'warning','half_day'=>'info'];
                                $badge  = $badges[$r['status']] ?? 'secondary';
                                echo "<span class='badge bg-{$badge}'>" . ucfirst(str_replace('_', ' ', $r['status'])) . "</span>";
                            ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Detail Modal ──────────────────────────────────────── -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalTitle">Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="detailModalBody">
                <!-- filled by JS -->
            </div>
        </div>
    </div>
</div>

<!-- Pre-rendered modal content panels (hidden) -->
<div id="modal-data" class="d-none">
    <div id="body-present"><?= attendanceTable($presentEmployees) ?></div>

    <div id="body-late"><?= attendanceTable($lateEmployees) ?></div>

    <div id="body-halfday"><?= attendanceTable($halfDayEmployees) ?></div>

    <div id="body-absent">
    <?php if (empty($absentEmployees)): ?>
        <p class="text-muted text-center py-3 mb-0">No absent employees today.</p>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light"><tr><th>Employee</th><th>Branch</th></tr></thead>
            <tbody>
            <?php foreach ($absentEmployees as $e): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($e['full_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($e['employee_code']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($e['branch_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
    </div>

    <div id="body-leaves">
    <?php if (empty($pendingLeavesData)): ?>
        <p class="text-muted text-center py-3 mb-0">No pending leave requests.</p>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr><th>Employee</th><th>Branch</th><th>Type</th><th>Dates</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php foreach ($pendingLeavesData as $lr): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($lr['full_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($lr['employee_code']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($lr['branch_name']) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $lr['leave_type']))) ?></span></td>
                    <td>
                        <small><?= date('d M', strtotime($lr['start_date'])) ?> – <?= date('d M', strtotime($lr['end_date'])) ?></small>
                    </td>
                    <td>
                        <a href="leaves.php" class="btn btn-sm btn-outline-primary py-0">Review</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('detailModal').addEventListener('show.bs.modal', function (e) {
    const card  = e.relatedTarget;
    const key   = card.dataset.modalBody;
    const title = card.dataset.modalTitle;

    document.getElementById('detailModalTitle').innerHTML = title;
    const src = document.getElementById('body-' + key);
    document.getElementById('detailModalBody').innerHTML = src ? src.innerHTML : '<p class="p-3 text-muted">No data.</p>';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
