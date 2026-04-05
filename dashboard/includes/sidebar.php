<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

function navLink(string $href, string $icon, string $label, string $current): string {
    $active = (basename($href) === $current) ? 'active' : '';
    return "<a href=\"{$href}\" class=\"nav-link {$active}\"><i class=\"bi {$icon} me-2\"></i>{$label}</a>";
}
?>
<div id="sidebar-backdrop"></div>
<div id="sidebar" class="d-flex flex-column flex-shrink-0 p-0">
    <!-- Brand -->
    <a href="<?= BASE_URL ?>/pages/dashboard.php"
       class="d-flex align-items-center gap-2 text-decoration-none sidebar-brand p-3">
        <i class="bi bi-building fs-4"></i>
        <div>
            <div class="fw-bold lh-1">Kalina Engineering</div>
            <small class="opacity-75" style="font-size:11px">Admin Panel</small>
        </div>
    </a>
    <hr class="text-white opacity-25 my-0">

    <nav class="nav flex-column px-2 py-3 gap-1 flex-grow-1">
        <?= navLink(BASE_URL . '/pages/dashboard.php',      'bi-speedometer2',   'Dashboard',       $currentPage) ?>
        <?= navLink(BASE_URL . '/pages/branches.php',       'bi-geo-alt',        'Branches',        $currentPage) ?>
        <?= navLink(BASE_URL . '/pages/employees.php',      'bi-people',         'Employees',       $currentPage) ?>
        <?= navLink(BASE_URL . '/pages/attendance.php',     'bi-clock-history',  'Attendance',      $currentPage) ?>
        <?= navLink(BASE_URL . '/pages/leaves.php',         'bi-calendar-x',     'Leave Requests',  $currentPage) ?>
        <?= navLink(BASE_URL . '/pages/salary.php',         'bi-cash-coin',      'Salary',          $currentPage) ?>
        <?= navLink(BASE_URL . '/pages/holidays.php',       'bi-umbrella',       'Holidays',        $currentPage) ?>
        <?= navLink(BASE_URL . '/pages/leave_policies.php', 'bi-journal-text',   'Leave Policies',  $currentPage) ?>
        <?= navLink(BASE_URL . '/pages/settings.php',       'bi-gear',           'Settings',        $currentPage) ?>
    </nav>

    <hr class="text-white opacity-25 my-0">
    <div class="px-3 py-2">
        <small class="text-white opacity-50">
            Logged in as <strong><?= htmlspecialchars($_SESSION['admin_email'] ?? 'Admin') ?></strong>
        </small>
    </div>
    <a href="<?= BASE_URL ?>/logout.php" class="nav-link text-danger px-3 py-2">
        <i class="bi bi-box-arrow-left me-2"></i>Logout
    </a>
</div>

<!-- Page content wrapper -->
<div id="page-content-wrapper" class="flex-grow-1 d-flex flex-column">
    <!-- Top bar -->
    <nav class="navbar navbar-expand-lg topbar px-3">
        <button class="btn btn-sm" id="sidebarToggle">
            <i class="bi bi-list fs-5"></i>
        </button>
        <span class="ms-3 fw-semibold text-dark"><?= htmlspecialchars($pageTitle ?? '') ?></span>
    </nav>
    <!-- Main content -->
    <main class="flex-grow-1 p-4">
