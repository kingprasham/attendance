import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:geolocator/geolocator.dart';
import '../providers/attendance_provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../../features/auth/providers/auth_provider.dart';
import '../../../features/notifications/providers/notification_provider.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  String _greeting() {
    final hour = DateTime.now().hour;
    if (hour < 12) return 'Good Morning';
    if (hour < 17) return 'Good Afternoon';
    return 'Good Evening';
  }

  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref.read(attendanceProvider.notifier).loadToday();
      ref.read(notificationProvider.notifier).loadNotifications();
    });
    WidgetsBinding.instance.addPostFrameCallback((_) => _checkLocationPermission());
  }

  Future<void> _checkLocationPermission() async {
    if (!mounted) return;

    // Check permission
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }

    if (!mounted) return;

    if (permission == LocationPermission.deniedForever) {
      _showLocationDialog(
        title: 'Location Permission Required',
        message:
            'This app needs location access to record attendance. Please enable it in Settings.',
        actionLabel: 'Open Settings',
        onAction: () => Geolocator.openAppSettings(),
      );
      return;
    }

    if (permission == LocationPermission.denied) {
      _showLocationDialog(
        title: 'Location Permission Required',
        message:
            'Location permission is required to clock in and out. Please grant it to continue.',
        actionLabel: 'Grant Permission',
        onAction: () {
          Navigator.of(context).pop();
          _checkLocationPermission();
        },
      );
      return;
    }

    // Check GPS service
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!mounted) return;
    if (!serviceEnabled) {
      _showLocationDialog(
        title: 'GPS is Disabled',
        message:
            'Please turn on GPS (Location Services) to use attendance features.',
        actionLabel: 'Enable GPS',
        onAction: () async {
          await Geolocator.openLocationSettings();
          if (mounted) Navigator.of(context).pop();
          await Future.delayed(const Duration(seconds: 1));
          _checkLocationPermission();
        },
      );
    }
  }

  void _showLocationDialog({
    required String title,
    required String message,
    required String actionLabel,
    required VoidCallback onAction,
  }) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            const Icon(Icons.location_off, color: AppColors.error),
            const SizedBox(width: 8),
            Expanded(child: Text(title, style: const TextStyle(fontSize: 16))),
          ],
        ),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: onAction,
            style: TextButton.styleFrom(foregroundColor: AppColors.primaryDark),
            child: Text(actionLabel),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final attendanceState = ref.watch(attendanceProvider);
    final authState = ref.watch(authProvider);
    final today = attendanceState.todayRecord;
    final isClockedIn = today?['clocked_in'] == true;
    final isClockedOut = today?['clocked_out'] == true;

    ref.listen(attendanceProvider, (_, state) {
      if (state.error != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(state.error!),
            backgroundColor: AppColors.error,
            behavior: SnackBarBehavior.floating,
          ),
        );
        ref.read(attendanceProvider.notifier).clearMessages();
      }
      if (state.successMessage != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(state.successMessage!),
            backgroundColor: AppColors.success,
            behavior: SnackBarBehavior.floating,
          ),
        );
        ref.read(attendanceProvider.notifier).clearMessages();
      }
    });

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(_greeting(), style: const TextStyle(fontSize: 12, color: Color(0xCCFFFFFF))),
            Text(
              authState.fullName ?? 'Employee',
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
            ),
          ],
        ),
        actions: [
          Consumer(builder: (context, ref, _) {
            final notifState = ref.watch(notificationProvider);
            final unread = notifState.notifications
                .where((n) => n['is_read'] == 0)
                .length;
            return Stack(
              children: [
                IconButton(
                  icon: const Icon(Icons.notifications_outlined),
                  onPressed: () => context.push('/notifications'),
                ),
                if (unread > 0)
                  Positioned(
                    right: 8,
                    top: 8,
                    child: Container(
                      width: 16,
                      height: 16,
                      decoration: const BoxDecoration(
                        color: AppColors.error,
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          unread > 9 ? '9+' : unread.toString(),
                          style: const TextStyle(color: Colors.white, fontSize: 9),
                        ),
                      ),
                    ),
                  ),
              ],
            );
          }),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(attendanceProvider.notifier).loadToday(),
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _StatusCard(today: today),
              const SizedBox(height: 20),

              if (attendanceState.isLoading)
                const Center(child: CircularProgressIndicator())
              else if (!isClockedIn)
                _AttendanceButton(
                  label: 'Clock In',
                  icon: Icons.login,
                  color: AppColors.success,
                  onTap: () => ref.read(attendanceProvider.notifier).clockIn(),
                )
              else if (!isClockedOut)
                _AttendanceButton(
                  label: 'Clock Out',
                  icon: Icons.logout,
                  color: AppColors.error,
                  onTap: () => ref.read(attendanceProvider.notifier).clockOut(),
                )
              else
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: AppColors.success.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.success.withOpacity(0.3)),
                  ),
                  child: const Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.check_circle, color: AppColors.success),
                      SizedBox(width: 8),
                      Text(
                        'Attendance marked for today',
                        style: TextStyle(
                          color: AppColors.success,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),

              const SizedBox(height: 20),

              Row(
                children: [
                  Expanded(
                    child: _QuickActionCard(
                      icon: Icons.calendar_month,
                      label: 'History',
                      color: AppColors.primaryLight,
                      onTap: () => context.push('/history'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _QuickActionCard(
                      icon: Icons.beach_access,
                      label: 'Apply Leave',
                      color: AppColors.warning,
                      onTap: () => context.push('/apply-leave'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _QuickActionCard(
                      icon: Icons.receipt_long,
                      label: 'Salary',
                      color: AppColors.success,
                      onTap: () => context.go('/salary'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusCard extends StatelessWidget {
  final Map<String, dynamic>? today;
  const _StatusCard({this.today});

  @override
  Widget build(BuildContext context) {
    final isClockedIn = today?['clocked_in'] == true;
    final isClockedOut = today?['clocked_out'] == true;
    final status = today?['status'] as String?;

    Color statusColor = AppColors.textSecondary;
    String statusLabel = 'Not clocked in';
    IconData statusIcon = Icons.radio_button_unchecked;

    if (isClockedIn && !isClockedOut) {
      statusColor = AppColors.success;
      statusLabel = status == 'late' ? 'Late' : 'Present';
      statusIcon = Icons.radio_button_checked;
    } else if (isClockedOut) {
      statusColor = AppColors.primaryDark;
      statusLabel = 'Completed';
      statusIcon = Icons.check_circle;
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppColors.primaryDark, AppColors.primaryLight],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            _formattedDate(),
            style: const TextStyle(color: Color(0xCCFFFFFF), fontSize: 13),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Icon(statusIcon, color: Colors.white, size: 20),
              const SizedBox(width: 8),
              Text(
                statusLabel,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          if (isClockedIn) ...[
            const SizedBox(height: 16),
            Row(
              children: [
                _TimeInfo(
                  label: 'Clock In',
                  time: today?['clock_in_time'] ?? '--:--',
                ),
                const SizedBox(width: 32),
                if (isClockedOut)
                  _TimeInfo(
                    label: 'Clock Out',
                    time: today?['clock_out_time'] ?? '--:--',
                  ),
                if (isClockedOut) ...[
                  const SizedBox(width: 32),
                  _TimeInfo(
                    label: 'Hours',
                    time: '${today?['work_hours'] ?? '0'}h',
                  ),
                ],
              ],
            ),
          ],
        ],
      ),
    );
  }

  String _formattedDate() {
    final now = DateTime.now();
    final days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    final months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return '${days[now.weekday - 1]}, ${now.day} ${months[now.month - 1]} ${now.year}';
  }
}

class _TimeInfo extends StatelessWidget {
  final String label;
  final String time;
  const _TimeInfo({required this.label, required this.time});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(color: Color(0xCCFFFFFF), fontSize: 11)),
        Text(time, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600)),
      ],
    );
  }
}

class _AttendanceButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  const _AttendanceButton({
    required this.label,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: 20),
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: color.withOpacity(0.3),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: Colors.white, size: 24),
            const SizedBox(width: 12),
            Text(
              label,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _QuickActionCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;
  const _QuickActionCard({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 16),
        decoration: BoxDecoration(
          color: AppColors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppColors.cardBorder),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 28),
            const SizedBox(height: 6),
            Text(
              label,
              style: const TextStyle(fontSize: 11, color: AppColors.textDark),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
