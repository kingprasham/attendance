<?php
// api/endpoints/salary/generate.php
// POST /salary/generate — Admin only

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../middleware/admin_auth.php';

require_admin();

$input = get_json_input();
validate_required($input, ['branch_id', 'month', 'year']);

$branch_id = (int)$input['branch_id'];
$month = (int)$input['month'];
$year = (int)$input['year'];

if ($month < 1 || $month > 12) {
    error_response('Invalid month', 400);
}

$pdo = get_db_connection();

// Get all active employees in this branch
$stmt = $pdo->prepare(
    "SELECT id, full_name, monthly_salary FROM employees
     WHERE branch_id = :bid AND is_active = 1"
);
$stmt->execute([':bid' => $branch_id]);
$employees = $stmt->fetchAll();

if (empty($employees)) {
    error_response('No active employees in this branch', 400);
}

// Calculate total working days in the month (excluding Sundays and holidays)
$total_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$working_days = 0;
for ($d = 1; $d <= $total_days_in_month; $d++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
    $day_of_week = date('w', strtotime($date)); // 0=Sunday
    if ($day_of_week != 0) {
        $working_days++;
    }
}

// Subtract mandatory holidays (branch-specific + global)
$hol_stmt = $pdo->prepare(
    "SELECT COUNT(*) as cnt FROM holidays
     WHERE (branch_id = :bid OR branch_id IS NULL) AND MONTH(date) = :m AND YEAR(date) = :y
     AND is_optional = 0 AND DAYOFWEEK(date) != 1"
);
$hol_stmt->execute([':bid' => $branch_id, ':m' => $month, ':y' => $year]);
$holidays = (int)$hol_stmt->fetch()['cnt'];
$working_days -= $holidays;

if ($working_days <= 0) {
    error_response('No working days in selected month', 400);
}

$pdo->beginTransaction();
$generated = [];

try {
    foreach ($employees as $emp) {
        // Check if already generated
        $check = $pdo->prepare(
            "SELECT id FROM salary_slips
             WHERE employee_id = :eid AND month = :m AND year = :y"
        );
        $check->execute([':eid' => $emp['id'], ':m' => $month, ':y' => $year]);
        if ($check->fetch()) {
            continue; // Skip already generated
        }

        // Count present days (half_day = 0.5, present/late = 1.0)
        $att_stmt = $pdo->prepare(
            "SELECT
               SUM(CASE WHEN status = 'half_day' THEN 0.5 ELSE 1.0 END) as cnt
             FROM attendance_logs
             WHERE employee_id = :eid AND MONTH(date) = :m AND YEAR(date) = :y"
        );
        $att_stmt->execute([':eid' => $emp['id'], ':m' => $month, ':y' => $year]);
        $present_days = (float)($att_stmt->fetch()['cnt'] ?? 0);

        // Count approved paid leave days
        $first_of_month = sprintf('%04d-%02d-01', $year, $month);
        $leave_stmt = $pdo->prepare(
            "SELECT SUM(
                CASE WHEN is_half_day = 1 THEN 0.5
                ELSE DATEDIFF(LEAST(end_date, LAST_DAY(:date1)), GREATEST(start_date, :date2)) + 1
                END
             ) as leave_days
             FROM leave_requests
             WHERE employee_id = :eid AND status = 'approved'
             AND leave_type != 'LWP'
             AND start_date <= LAST_DAY(:date3) AND end_date >= :date4"
        );
        $leave_stmt->execute([
            ':eid' => $emp['id'],
            ':date1' => $first_of_month,
            ':date2' => $first_of_month,
            ':date3' => $first_of_month,
            ':date4' => $first_of_month,
        ]);
        $leave_days = (float)($leave_stmt->fetch()['leave_days'] ?? 0);

        // Count LWP days
        $lwp_stmt = $pdo->prepare(
            "SELECT SUM(
                CASE WHEN is_half_day = 1 THEN 0.5
                ELSE DATEDIFF(LEAST(end_date, LAST_DAY(:date1)), GREATEST(start_date, :date2)) + 1
                END
             ) as lwp_days
             FROM leave_requests
             WHERE employee_id = :eid AND status = 'approved'
             AND leave_type = 'LWP'
             AND start_date <= LAST_DAY(:date3) AND end_date >= :date4"
        );
        $lwp_stmt->execute([
            ':eid' => $emp['id'],
            ':date1' => $first_of_month,
            ':date2' => $first_of_month,
            ':date3' => $first_of_month,
            ':date4' => $first_of_month,
        ]);
        $lwp_days = (float)($lwp_stmt->fetch()['lwp_days'] ?? 0);

        // Calculate salary
        $per_day = $emp['monthly_salary'] / $working_days;
        $paid_days = $present_days + $leave_days;
        $gross_salary = round($per_day * $paid_days, 2);
        $deductions = round($per_day * $lwp_days, 2);
        $net_salary = round($gross_salary - $deductions, 2);

        // Insert salary slip
        $ins_stmt = $pdo->prepare(
            "INSERT INTO salary_slips
             (employee_id, month, year, total_days, present_days, leave_days, lwp_days, gross_salary, deductions, net_salary)
             VALUES (:eid, :m, :y, :total, :present, :leave, :lwp, :gross, :ded, :net)"
        );
        $ins_stmt->execute([
            ':eid' => $emp['id'],
            ':m' => $month,
            ':y' => $year,
            ':total' => $working_days,
            ':present' => $present_days,
            ':leave' => $leave_days,
            ':lwp' => $lwp_days,
            ':gross' => $gross_salary,
            ':ded' => $deductions,
            ':net' => $net_salary,
        ]);

        // Notify employee
        $notif_stmt = $pdo->prepare(
            "INSERT INTO notifications (employee_id, title, body, type)
             VALUES (:eid, :title, :body, 'salary')"
        );
        $month_name = date('F', mktime(0, 0, 0, $month, 1));
        $notif_stmt->execute([
            ':eid' => $emp['id'],
            ':title' => "Salary Slip Generated",
            ':body' => "Your salary slip for {$month_name} {$year} is now available. Net salary: ₹" . number_format($net_salary, 2),
        ]);

        $generated[] = [
            'employee_id' => $emp['id'],
            'full_name' => $emp['full_name'],
            'net_salary' => $net_salary,
        ];
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_response('Payroll generation failed: ' . $e->getMessage(), 500);
}

success_response('Payroll generated', [
    'branch_id' => $branch_id,
    'month' => $month,
    'year' => $year,
    'working_days' => $working_days,
    'employees_processed' => count($generated),
    'slips' => $generated,
]);
