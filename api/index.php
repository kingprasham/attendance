<?php
// api/index.php
// Simple file-based router

require_once __DIR__ . '/config/cors.php';
set_cors_headers();

require_once __DIR__ . '/helpers/response.php';

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string and base path
$uri = parse_url($uri, PHP_URL_PATH);

// Remove everything up to and including /api/ (works for any subdirectory depth)
$uri = preg_replace('#^.*/api/#', '/', $uri);
$uri = '/' . trim($uri, '/');

// Map URI to endpoint file
$endpoint_map = [
    // Auth
    'POST /auth/login'             => 'auth/login.php',
    'POST /auth/admin_login'       => 'auth/admin_login.php',
    'POST /auth/refresh'           => 'auth/refresh.php',
    'POST /auth/register_device'   => 'auth/register_device.php',

    // Branches
    'POST /branches/create'        => 'branches/create.php',
    'GET /branches/list'           => 'branches/list.php',
    'PUT /branches/update'         => 'branches/update.php',
    'DELETE /branches/delete'      => 'branches/delete.php',

    // Employees
    'POST /employees/create'       => 'employees/create.php',
    'GET /employees/list'          => 'employees/list.php',
    'GET /employees/view'          => 'employees/view.php',
    'PUT /employees/update'        => 'employees/update.php',
    'DELETE /employees/delete'     => 'employees/delete.php',
    'POST /employees/reset_device' => 'employees/reset_device.php',
    'GET /employees/profile'       => 'employees/profile.php',

    // Attendance
    'POST /attendance/clock_in'    => 'attendance/clock_in.php',
    'POST /attendance/clock_out'   => 'attendance/clock_out.php',
    'GET /attendance/today'        => 'attendance/today.php',
    'GET /attendance/history'      => 'attendance/history.php',
    'GET /attendance/report'       => 'attendance/report.php',

    // Leaves
    'POST /leaves/apply'           => 'leaves/apply.php',
    'POST /leaves/cancel'          => 'leaves/cancel.php',
    'GET /leaves/balance'          => 'leaves/balance.php',
    'GET /leaves/history'          => 'leaves/history.php',
    'GET /leaves/pending'          => 'leaves/pending.php',
    'POST /leaves/approve'         => 'leaves/approve.php',
    'POST /leaves/reject'          => 'leaves/reject.php',

    // Salary
    'POST /salary/generate'        => 'salary/generate.php',
    'GET /salary/slips'            => 'salary/slips.php',
    'GET /salary/slip_detail'      => 'salary/slip_detail.php',

    // Holidays
    'POST /holidays/create'        => 'holidays/create.php',
    'GET /holidays/list'           => 'holidays/list.php',
    'DELETE /holidays/delete'      => 'holidays/delete.php',

    // Notifications
    'GET /notifications/list'      => 'notifications/list.php',
    'POST /notifications/mark_read' => 'notifications/mark_read.php',

    // Leave Policies
    'POST /leave_policies/set'     => 'leave_policies/set.php',
    'GET /leave_policies/view'     => 'leave_policies/view.php',
];

$route_key = $method . ' ' . $uri;

if (isset($endpoint_map[$route_key])) {
    $file = __DIR__ . '/endpoints/' . $endpoint_map[$route_key];
    if (file_exists($file)) {
        require $file;
    } else {
        error_response('Endpoint not implemented', 501);
    }
} else {
    error_response('Route not found: ' . $method . ' ' . $uri, 404);
}
