<?php
session_start();
define('BASE_URL', rtrim(str_repeat('../', substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__), '/')), '/') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Dashboard';
$db        = get_db_connection();
$today     = gmdate('Y-m-d');

// Today's attendance summary
$stmt = $db->prepare("
    SELECT
        COUNT(*) AS total_clocked_in,
        SUM(status = 'present') AS present,
        SUM(status = 'late') AS late,
        SUM(status = 'half_day') AS half_day
    FROM attendance_logs
    WHERE DATE(clock_in_time) = ?
");
$stmt->execute([$today]);
$todayStats = $stmt->fetch();

// Total active employees
$totalEmp = $db->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn();

// Absent today = active employees - clocked in today
$absentToday = max(0, $totalEmp - ($todayStats['total_clocked_in'] ?? 0));

// Pending leaves
$pendingLeaves = $db->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn();

// Total branches
$totalBranches = $db->query("SELECT COUNT(*) FROM branches WHERE is_active = 1")->fetchColumn();

// Recent attendance (last 10)
$recent = $db->prepare("
    SELECT al.*, e.full_name, e.employee_code, b.name AS branch_name
    FROM attendance_logs al
    JOIN employees e ON al.employee_id = e.id
    JOIN branches b ON e.branch_id = b.id
    WHERE DATE(al.clock_in_time) = ?
    ORDER BY al.clock_in_time DESC
    LIMIT 10
");
$recent->execute([$today]);
$recentRows = $recent->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card border-0 shadow-sm">
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
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card border-0 shadow-sm">
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
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card border-0 shadow-sm">
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
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card card border-0 shadow-sm">
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

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="fs-1 fw-bold text-primary"><?= (int)$totalEmp ?></div>
                <div class="text-muted">Total Employees</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="fs-1 fw-bold text-primary"><?= (int)$totalBranches ?></div>
                <div class="text-muted">Active Branches</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center py-4">
                <div class="fs-1 fw-bold" style="color:#0055A4">
                    <?= date('d M Y') ?>
                </div>
                <div class="text-muted">Today</div>
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
                            <td><?= $r['clock_in_time'] ? date('h:i A', strtotime($r['clock_in_time'] . ' +5:30 hours +30 minutes')) : '—' ?></td>
                            <td><?= $r['clock_out_time'] ? date('h:i A', strtotime($r['clock_out_time'] . ' +5:30 hours +30 minutes')) : '—' ?></td>
                            <td><?php
                                $badges = ['present'=>'success','late'=>'warning','half_day'=>'info'];
                                $badge = $badges[$r['status']] ?? 'secondary';
                                echo "<span class=\"badge bg-{$badge}\">" . ucfirst(str_replace('_', ' ', $r['status'])) . "</span>";
                            ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
