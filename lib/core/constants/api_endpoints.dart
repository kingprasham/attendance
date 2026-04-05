class ApiEndpoints {
  static const String baseUrl = 'https://mehrgrewal.com/attendance/api/';

  // Auth
  static const String login = 'auth/login';
  static const String adminLogin = 'auth/admin_login';
  static const String refresh = 'auth/refresh';
  static const String registerDevice = 'auth/register_device';

  // Attendance
  static const String clockIn = 'attendance/clock_in';
  static const String clockOut = 'attendance/clock_out';
  static const String attendanceToday = 'attendance/today';
  static const String attendanceHistory = 'attendance/history';

  // Leaves
  static const String leavesApply = 'leaves/apply';
  static const String leavesCancel = 'leaves/cancel';
  static const String leavesBalance = 'leaves/balance';
  static const String leavesHistory = 'leaves/history';

  // Salary
  static const String salarySlips = 'salary/slips';
  static const String salarySlipDetail = 'salary/slip_detail';

  // Profile
  static const String profile = 'employees/profile';

  // Notifications
  static const String notificationsList = 'notifications/list';
  static const String notificationsMarkRead = 'notifications/mark_read';

  // Holidays
  static const String holidaysList = 'holidays/list';

  // Leave policies
  static const String leavePoliciesView = 'leave_policies/view';
}
