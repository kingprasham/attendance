import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/profile_provider.dart';
import '../../auth/providers/auth_provider.dart';
import '../../../core/constants/app_colors.dart';

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(profileProvider.notifier).load());
  }

  @override
  Widget build(BuildContext context) {
    final profileAsync = ref.watch(profileProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('My Profile'),
        actions: [
          TextButton.icon(
            icon: const Icon(Icons.logout, color: Colors.white, size: 18),
            label: const Text('Logout', style: TextStyle(color: Colors.white)),
            onPressed: () async {
              final confirmed = await showDialog<bool>(
                context: context,
                builder: (context) => AlertDialog(
                  title: const Text('Log out?'),
                  content: const Text('Are you sure you want to log out?'),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: const Text('Cancel'),
                    ),
                    ElevatedButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: const Text('Log out'),
                    ),
                  ],
                ),
              );
              if (confirmed == true && mounted) {
                await ref.read(authProvider.notifier).logout();
              }
            },
          ),
        ],
      ),
      body: profileAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(e.toString(), style: const TextStyle(color: AppColors.error)),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => ref.read(profileProvider.notifier).load(),
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
        data: (profile) => SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  color: AppColors.primaryDark,
                  borderRadius: BorderRadius.circular(40),
                ),
                child: const Icon(Icons.person, color: Colors.white, size: 48),
              ),
              const SizedBox(height: 12),
              Text(
                profile['full_name'] ?? '',
                style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
              ),
              Text(
                '${profile['employee_code'] ?? ''} • ${profile['branch_name'] ?? ''}',
                style: const TextStyle(color: AppColors.textSecondary),
              ),
              const SizedBox(height: 24),

              _InfoCard(title: 'Employment Details', fields: [
                ('Designation', profile['designation'] ?? '-'),
                ('Department', profile['department'] ?? '-'),
                ('Employment Type', profile['employment_type'] ?? '-'),
                ('Date of Joining', profile['date_of_joining'] ?? '-'),
              ]),
              const SizedBox(height: 12),

              _InfoCard(title: 'Contact Information', fields: [
                ('Email', profile['email'] ?? '-'),
                ('Phone', profile['phone'] ?? '-'),
              ]),
              const SizedBox(height: 12),

              _InfoCard(title: 'Bank Details', fields: [
                ('Bank Account', profile['bank_account'] ?? '-'),
                ('IFSC Code', profile['ifsc_code'] ?? '-'),
              ]),
              const SizedBox(height: 12),

              _InfoCard(title: 'KYC', fields: [
                ('PAN Number', profile['pan_number'] ?? '-'),
                ('Aadhar Number', profile['aadhar_number'] != null
                    ? 'XXXX XXXX ${profile['aadhar_number'].toString().replaceAll(' ', '').substring(profile['aadhar_number'].toString().length > 4 ? profile['aadhar_number'].toString().length - 4 : 0)}'
                    : '-'),
              ]),
            ],
          ),
        ),
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  final String title;
  final List<(String, String)> fields;

  const _InfoCard({required this.title, required this.fields});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title,
                style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    color: AppColors.textSecondary,
                    fontSize: 12)),
            const Divider(height: 16),
            ...fields.map((f) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      SizedBox(
                        width: 120,
                        child: Text(f.$1,
                            style: const TextStyle(
                                color: AppColors.textSecondary, fontSize: 13)),
                      ),
                      Expanded(
                        child: Text(f.$2,
                            style: const TextStyle(fontWeight: FontWeight.w500)),
                      ),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }
}
