import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/constants/app_colors.dart';
import '../../features/auth/providers/auth_provider.dart';

class AppDrawer extends ConsumerWidget {
  const AppDrawer({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);

    return Drawer(
      child: Column(
        children: [
          DrawerHeader(
            decoration: const BoxDecoration(color: AppColors.primaryDark),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                const CircleAvatar(
                  radius: 28,
                  backgroundColor: AppColors.primaryLight,
                  child: Icon(Icons.person, color: Colors.white, size: 32),
                ),
                const SizedBox(height: 12),
                Text(
                  authState.fullName ?? 'Employee',
                  style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 16),
                ),
                const Text('Kalina Engineering',
                    style: TextStyle(color: Color(0xCCFFFFFF), fontSize: 12)),
              ],
            ),
          ),
          _DrawerItem(
            icon: Icons.home_outlined,
            label: 'Home',
            onTap: () { Navigator.pop(context); context.go('/home'); },
          ),
          _DrawerItem(
            icon: Icons.calendar_month,
            label: 'Attendance History',
            onTap: () { Navigator.pop(context); context.go('/history'); },
          ),
          _DrawerItem(
            icon: Icons.beach_access,
            label: 'Leaves',
            onTap: () { Navigator.pop(context); context.go('/leaves'); },
          ),
          _DrawerItem(
            icon: Icons.receipt_long,
            label: 'Salary',
            onTap: () { Navigator.pop(context); context.go('/salary'); },
          ),
          _DrawerItem(
            icon: Icons.celebration,
            label: 'Holidays',
            onTap: () { Navigator.pop(context); context.go('/holidays'); },
          ),
          _DrawerItem(
            icon: Icons.person_outline,
            label: 'My Profile',
            onTap: () { Navigator.pop(context); context.go('/profile'); },
          ),
          const Spacer(),
          const Divider(),
          _DrawerItem(
            icon: Icons.logout,
            label: 'Logout',
            color: AppColors.error,
            onTap: () async {
              Navigator.pop(context);
              await ref.read(authProvider.notifier).logout();
            },
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }
}

class _DrawerItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color? color;

  const _DrawerItem({
    required this.icon,
    required this.label,
    required this.onTap,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(icon, color: color ?? AppColors.textDark),
      title: Text(label, style: TextStyle(color: color ?? AppColors.textDark)),
      onTap: onTap,
    );
  }
}
