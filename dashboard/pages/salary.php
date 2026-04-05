<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Salary & Payroll';
$db        = get_db_connection();
$msg       = '';
$msgType   = 'success';

// Generate payroll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    $month    = (int)$_POST['month'];
    $year     = (int)$_POST['year'];
    $branchId = (int)$_POST['branch_id'];

    // Get all active employees for this branch
    $emps = $db->prepare("SELECT * FROM employees WHERE branch_id = ? AND is_active = 1");
    $emps->execute([$branchId]);
    $employees = $emps->fetchAll();

    // Working days = days in month minus Sundays minus holidays
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $sundays = 0;
    for ($d = 1; $d <= $daysInMonth; $d++) {
        if (date('N', mktime(0,0,0,$month,$d,$year)) == 7) $sundays++;
    }
    $holidayCount = $db->prepare("
        SELECT COUNT(*) FROM holidays
        WHERE (branch_id IS NULL OR branch_id = ?)
        AND MONTH(date) = ? AND YEAR(date) = ?
        AND is_optional = 0 AND DAYOFWEEK(date) != 1
    ");
    $holidayCount->execute([$branchId, $month, $year]);
    $holidays    = (int)$holidayCount->fetchColumn();
    $workingDays = $daysInMonth - $sundays - $holidays;
    if ($workingDays < 1) $workingDays = 1;

    $generated = 0;
    $skipped   = 0;

    foreach ($employees as $emp) {
        // Skip if slip already exists
        $exists = $db->prepare("SELECT id FROM salary_slips WHERE employee_id = ? AND month = ? AND year = ?");
        $exists->execute([$emp['id'], $month, $year]);
        if ($exists->fetchColumn()) { $skipped++; continue; }

        // Present days (clock_in records)
        $presentStmt = $db->prepare("
            SELECT COUNT(*) FROM attendance_logs
            WHERE employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
            AND status IN ('present','late')
        ");
        $presentStmt->execute([$emp['id'], $month, $year]);
        $presentDays = (int)$presentStmt->fetchColumn();

        $halfDayStmt = $db->prepare("
            SELECT COUNT(*) FROM attendance_logs
            WHERE employee_id = ? AND MONTH(date) = ? AND YEAR(date) = ?
            AND status = 'half_day'
        ");
        $halfDayStmt->execute([$emp['id'], $month, $year]);
        $halfDays = (int)$halfDayStmt->fetchColumn();
        $presentDays += $halfDays * 0.5;

        // Approved paid leave days
        $paidLeaveStmt = $db->prepare("
            SELECT SUM(DATEDIFF(
                LEAST(end_date, LAST_DAY(CONCAT(?,'-',LPAD(?,2,'0'),'-01'))),
                GREATEST(start_date, CONCAT(?,'-',LPAD(?,2,'0'),'-01'))
            ) + 1)
            FROM leave_requests
            WHERE employee_id = ? AND status = 'approved'
            AND leave_type != 'LWP'
            AND start_date <= LAST_DAY(CONCAT(?,'-',LPAD(?,2,'0'),'-01'))
            AND end_date   >= CONCAT(?,'-',LPAD(?,2,'0'),'-01')
        ");
        $yStr = $year; $mStr = $month;
        $paidLeaveStmt->execute([$yStr,$mStr,$yStr,$mStr,$emp['id'],$yStr,$mStr,$yStr,$mStr]);
        $paidLeaveDays = (float)($paidLeaveStmt->fetchColumn() ?? 0);

        // LWP days
        $lwpStmt = $db->prepare("
            SELECT SUM(DATEDIFF(
                LEAST(end_date, LAST_DAY(CONCAT(?,'-',LPAD(?,2,'0'),'-01'))),
                GREATEST(start_date, CONCAT(?,'-',LPAD(?,2,'0'),'-01'))
            ) + 1)
            FROM leave_requests
            WHERE employee_id = ? AND status = 'approved' AND leave_type = 'LWP'
            AND start_date <= LAST_DAY(CONCAT(?,'-',LPAD(?,2,'0'),'-01'))
            AND end_date   >= CONCAT(?,'-',LPAD(?,2,'0'),'-01')
        ");
        $lwpStmt->execute([$yStr,$mStr,$yStr,$mStr,$emp['id'],$yStr,$mStr,$yStr,$mStr]);
        $lwpDays = (float)($lwpStmt->fetchColumn() ?? 0);

        $monthlySalary = (float)$emp['monthly_salary'];
        $perDay        = $monthlySalary / $workingDays;
        $grossSalary   = $perDay * ($presentDays + $paidLeaveDays);
        $deductions    = $perDay * $lwpDays;
        $netSalary     = max(0, $grossSalary - $deductions);

        $db->prepare("INSERT INTO salary_slips
            (employee_id, month, year, total_days, present_days, leave_days, lwp_days,
             gross_salary, deductions, net_salary)
            VALUES (?,?,?,?,?,?,?,?,?,?)")
           ->execute([
               $emp['id'], $month, $year, $workingDays,
               $presentDays, $paidLeaveDays, $lwpDays,
               round($grossSalary,2), round($deductions,2), round($netSalary,2),
           ]);

        // Notify employee
        $monthName = date('F Y', mktime(0,0,0,$month,1,$year));
        $db->prepare("INSERT INTO notifications (employee_id, title, body, type) VALUES (?,?,?,'salary')")
           ->execute([$emp['id'], 'Salary Slip Available', "Your salary slip for {$monthName} has been generated. Net: ₹" . number_format($netSalary, 2)]);

        $generated++;
    }

    $msg = "Payroll generated: {$generated} slip(s) created, {$skipped} already existed.";
}

// View slips
$filterMonth  = (int)($_GET['month']  ?? date('n'));
$filterYear   = (int)($_GET['year']   ?? date('Y'));
$filterBranch = (int)($_GET['branch'] ?? 0);

$branches = $db->query("SELECT id, name FROM branches WHERE is_active=1 ORDER BY name")->fetchAll();

$slipSql = "
    SELECT ss.*, e.full_name, e.employee_code, b.name AS branch_name
    FROM salary_slips ss
    JOIN employees e ON ss.employee_id = e.id
    JOIN branches b ON e.branch_id = b.id
    WHERE ss.month = ? AND ss.year = ?
";
$slipParams = [$filterMonth, $filterYear];
if ($filterBranch) { $slipSql .= ' AND e.branch_id = ?'; $slipParams[] = $filterBranch; }
$slipSql .= ' ORDER BY e.employee_code';

$slipStmt = $db->prepare($slipSql);
$slipStmt->execute($slipParams);
$slips = $slipStmt->fetchAll();

$totalNet = array_sum(array_column($slips, 'net_salary'));

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
        <?= htmlspecialchars($msg) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Generate Payroll Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-calculator me-2 text-primary"></i>Generate Payroll
    </div>
    <div class="card-body">
        <form method="POST" class="row g-3 align-items-end filter-row">
            <input type="hidden" name="action" value="generate">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Branch *</label>
                <select name="branch_id" class="form-select" required>
                    <option value="">Select branch</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Month</label>
                <select name="month" class="form-select">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>
                            <?= date('F', mktime(0,0,0,$m,1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Year</label>
                <select name="year" class="form-select">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>"><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"
                        onclick="return confirm('Generate payroll for selected branch and month?')">
                    <i class="bi bi-play-circle me-1"></i>Generate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Slips -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <form class="row g-2 align-items-center filter-row">
            <div class="col-auto fw-semibold">
                <i class="bi bi-receipt me-1 text-primary"></i>Salary Slips
            </div>
            <div class="col-md-2">
                <select name="month" class="form-select form-select-sm">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $filterMonth ? 'selected' : '' ?>>
                            <?= date('F', mktime(0,0,0,$m,1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="year" class="form-select form-select-sm">
                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == $filterYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="branch" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $filterBranch == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm">Filter</button>
            </div>
            <?php if (!empty($slips)): ?>
            <div class="col-auto ms-auto">
                <span class="fw-bold text-success">Total: ₹<?= number_format($totalNet, 2) ?></span>
            </div>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th><th>Branch</th><th>Working Days</th>
                        <th>Present</th><th>Leave</th><th>LWP</th>
                        <th>Gross</th><th>Deductions</th><th class="text-success">Net Salary</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($slips as $s): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($s['full_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($s['employee_code']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($s['branch_name']) ?></td>
                    <td><?= $s['total_days'] ?></td>
                    <td><?= $s['present_days'] ?></td>
                    <td><?= $s['leave_days'] ?></td>
                    <td><?= $s['lwp_days'] ?></td>
                    <td>₹<?= number_format($s['gross_salary'], 2) ?></td>
                    <td class="text-danger">₹<?= number_format($s['deductions'], 2) ?></td>
                    <td class="fw-bold text-success">₹<?= number_format($s['net_salary'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($slips)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No salary slips for this period.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
