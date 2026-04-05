<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../../api/helpers/encryption.php';

$pageTitle = 'Employees';
$db        = get_db_connection();
$msg       = '';
$msgType   = 'success';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        // Auto-generate employee code
        $last = $db->query("SELECT employee_code FROM employees ORDER BY id DESC LIMIT 1")->fetchColumn();
        $num  = $last ? ((int)substr($last, strlen('KE-')) + 1) : 1;
        $code = 'KE-' . str_pad($num, 3, '0', STR_PAD_LEFT);

        // username = lowercase email prefix (before @), suffixed with code if taken
        $baseUsername = strtolower(preg_replace('/[^a-z0-9]/', '', explode('@', trim($_POST['email']))[0]));
        $username = $baseUsername;
        $uCheck = $db->prepare("SELECT id FROM employees WHERE username = ?");
        $uCheck->execute([$username]);
        if ($uCheck->fetchColumn()) {
            $username = $baseUsername . strtolower(str_replace('-', '', $code));
        }
        $stmt = $db->prepare("INSERT INTO employees
            (employee_code, full_name, email, phone, branch_id, designation, department,
             employment_type, date_of_joining, monthly_salary, bank_account, ifsc_code,
             pan_number, aadhar_number, username, password)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $code,
            trim($_POST['full_name']),
            trim($_POST['email']),
            trim($_POST['phone']),
            (int)$_POST['branch_id'],
            trim($_POST['designation']),
            trim($_POST['department']),
            $_POST['employment_type'],
            $_POST['date_of_joining'],
            (float)$_POST['monthly_salary'],
            trim($_POST['bank_account']),
            trim($_POST['ifsc_code']),
            $_POST['pan_number'] ? aes_encrypt(trim($_POST['pan_number'])) : null,
            $_POST['aadhar_number'] ? aes_encrypt(trim($_POST['aadhar_number'])) : null,
            $username,
            password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        // Seed leave balances from branch policy
        $empId = $db->lastInsertId();
        $policies = $db->prepare("SELECT * FROM leave_policies WHERE branch_id = ?");
        $policies->execute([(int)$_POST['branch_id']]);
        $ins = $db->prepare("INSERT INTO leave_balances (employee_id, leave_type, year, total_quota, used, carried_forward) VALUES (?,?,?,?,0,0)");
        $currentYear = (int)date('Y');
        foreach ($policies->fetchAll() as $p) {
            $ins->execute([$empId, $p['leave_type'], $currentYear, $p['annual_quota']]);
        }
        $msg = "Employee {$code} created successfully.";

    } elseif ($action === 'update') {
        $fields = "full_name=?, email=?, phone=?, branch_id=?, designation=?,
                   department=?, employment_type=?, date_of_joining=?, monthly_salary=?,
                   bank_account=?, ifsc_code=?";
        $vals = [
            trim($_POST['full_name']),
            trim($_POST['email']),
            trim($_POST['phone']),
            (int)$_POST['branch_id'],
            trim($_POST['designation']),
            trim($_POST['department']),
            $_POST['employment_type'],
            $_POST['date_of_joining'],
            (float)$_POST['monthly_salary'],
            trim($_POST['bank_account']),
            trim($_POST['ifsc_code']),
        ];
        if (!empty($_POST['pan_number'])) {
            $fields .= ', pan_number=?';
            $vals[]  = aes_encrypt(trim($_POST['pan_number']));
        }
        if (!empty($_POST['aadhar_number'])) {
            $fields .= ', aadhar_number=?';
            $vals[]  = aes_encrypt(trim($_POST['aadhar_number']));
        }
        if (!empty($_POST['password'])) {
            $fields .= ', password=?';
            $vals[]  = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $vals[] = (int)$_POST['emp_id'];
        $db->prepare("UPDATE employees SET {$fields} WHERE id=?")->execute($vals);
        $msg = 'Employee updated.';

    } elseif ($action === 'delete') {
        $db->prepare("UPDATE employees SET is_active=0 WHERE id=?")->execute([(int)$_POST['emp_id']]);
        $msg = 'Employee deactivated.'; $msgType = 'warning';

    } elseif ($action === 'reset_device') {
        $db->prepare("UPDATE employees SET device_id=NULL WHERE id=?")->execute([(int)$_POST['emp_id']]);
        $msg = 'Device reset. Employee can log in from a new device.';
    }
}

// Fetch
$search     = trim($_GET['q'] ?? '');
$branchFilter = (int)($_GET['branch'] ?? 0);
$params     = [];
$where      = ['e.is_active = 1'];

if ($search) {
    $where[] = '(e.full_name LIKE ? OR e.employee_code LIKE ? OR e.email LIKE ?)';
    $params  = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($branchFilter) {
    $where[] = 'e.branch_id = ?';
    $params[] = $branchFilter;
}

$sql  = "SELECT e.*, b.name AS branch_name FROM employees e
         JOIN branches b ON e.branch_id = b.id
         WHERE " . implode(' AND ', $where) . " ORDER BY e.employee_code";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

$branches = $db->query("SELECT id, name FROM branches WHERE is_active=1 ORDER BY name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
        <?= htmlspecialchars($msg) ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Search + Filter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="Search name, code, email…" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-4">
                <select name="branch" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $branchFilter == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm">Filter</button>
                <a href="employees.php" class="btn btn-secondary btn-sm">Reset</a>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-primary btn-sm"
                        data-bs-toggle="modal" data-bs-target="#empModal"
                        onclick="openEmpModal()">
                    <i class="bi bi-person-plus me-1"></i>Add Employee
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Code</th><th>Name</th><th>Branch</th>
                        <th>Designation</th><th>Salary</th><th>Device</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($employees as $e): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($e['employee_code']) ?></span></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($e['full_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($e['email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($e['branch_name']) ?></td>
                        <td><?= htmlspecialchars($e['designation']) ?></td>
                        <td>₹<?= number_format($e['monthly_salary'], 0) ?></td>
                        <td>
                            <?php if ($e['device_id']): ?>
                                <span class="badge bg-success-subtle text-success">Bound</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary">Unbound</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#empModal"
                                    onclick="openEmpModal(<?= htmlspecialchars(json_encode($e)) ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($e['device_id']): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="reset_device">
                                <input type="hidden" name="emp_id" value="<?= $e['id'] ?>">
                                <button class="btn btn-outline-warning btn-sm"
                                        onclick="return confirm('Reset device for <?= htmlspecialchars($e['full_name']) ?>?')"
                                        title="Reset Device">
                                    <i class="bi bi-phone-vibrate"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="emp_id" value="<?= $e['id'] ?>">
                                <button class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Deactivate <?= htmlspecialchars($e['full_name']) ?>?')">
                                    <i class="bi bi-person-dash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($employees)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No employees found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Employee Modal -->
<div class="modal fade" id="empModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <input type="hidden" name="action" id="empAction" value="create">
            <input type="hidden" name="emp_id" id="empId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="empModalTitle">Add Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name *</label>
                            <input type="text" name="full_name" id="eName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email *</label>
                            <input type="email" name="email" id="eEmail" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" id="ePhone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch *</label>
                            <select name="branch_id" id="eBranch" class="form-select" required>
                                <option value="">Select branch</option>
                                <?php foreach ($branches as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Designation</label>
                            <input type="text" name="designation" id="eDesig" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text" name="department" id="eDept" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Employment Type</label>
                            <select name="employment_type" id="eEmpType" class="form-select">
                                <option value="full_time">Full Time</option>
                                <option value="part_time">Part Time</option>
                                <option value="contract">Contract</option>
                            </select>
                            <!-- Values match DB ENUM('full_time','part_time','contract') -->
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date of Joining</label>
                            <input type="date" name="date_of_joining" id="eDoj" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Monthly Salary (₹)</label>
                            <input type="number" name="monthly_salary" id="eSalary" class="form-control" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Password
                                <span id="pwdHint" class="text-muted small ms-1">(required for new)</span>
                            </label>
                            <input type="password" name="password" id="ePwd" class="form-control"
                                   placeholder="Enter new password">
                            <div id="pwdExisting" class="d-none mt-1">
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-check-circle me-1"></i>Password is set — leave blank to keep it
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Bank Account</label>
                            <input type="text" name="bank_account" id="eBank" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">IFSC Code</label>
                            <input type="text" name="ifsc_code" id="eIfsc" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">PAN Number</label>
                            <input type="text" name="pan_number" id="ePan" class="form-control"
                                   placeholder="Enter PAN to update">
                            <div id="panExisting" class="d-none mt-1">
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-check-circle me-1"></i>PAN on file — leave blank to keep it
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Aadhar Number</label>
                            <input type="text" name="aadhar_number" id="eAadhar" class="form-control"
                                   placeholder="Enter Aadhar to update">
                            <div id="aadharExisting" class="d-none mt-1">
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-check-circle me-1"></i>Aadhar on file — leave blank to keep it
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openEmpModal(emp = null) {
    const isEdit = emp !== null;
    document.getElementById('empModalTitle').textContent = isEdit ? 'Edit Employee' : 'Add Employee';
    document.getElementById('empAction').value = isEdit ? 'update' : 'create';
    document.getElementById('empId').value     = emp?.id ?? '';
    document.getElementById('eName').value     = emp?.full_name ?? '';
    document.getElementById('eEmail').value    = emp?.email ?? '';
    document.getElementById('ePhone').value    = emp?.phone ?? '';
    document.getElementById('eBranch').value   = emp?.branch_id ?? '';
    document.getElementById('eDesig').value    = emp?.designation ?? '';
    document.getElementById('eDept').value     = emp?.department ?? '';
    document.getElementById('eEmpType').value  = emp?.employment_type ?? 'full_time';
    document.getElementById('eDoj').value      = emp?.date_of_joining ?? '';
    document.getElementById('eSalary').value   = emp?.monthly_salary ?? '';
    document.getElementById('eBank').value     = emp?.bank_account ?? '';
    document.getElementById('eIfsc').value     = emp?.ifsc_code ?? '';
    document.getElementById('ePan').value      = '';
    document.getElementById('eAadhar').value   = '';
    document.getElementById('ePwd').value      = '';

    // Password hint
    document.getElementById('pwdHint').textContent = isEdit ? '' : '(required)';
    document.getElementById('pwdExisting').classList.toggle('d-none', !isEdit);

    // PAN / Aadhar — show badge if the employee already has a value stored
    document.getElementById('panExisting').classList.toggle('d-none', !(isEdit && emp?.pan_number));
    document.getElementById('aadharExisting').classList.toggle('d-none', !(isEdit && emp?.aadhar_number));
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
