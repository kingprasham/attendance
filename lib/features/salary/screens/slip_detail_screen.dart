import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../providers/salary_provider.dart';
import '../../../core/constants/app_colors.dart';

class SlipDetailScreen extends ConsumerStatefulWidget {
  final int slipId;
  const SlipDetailScreen({super.key, required this.slipId});

  @override
  ConsumerState<SlipDetailScreen> createState() => _SlipDetailScreenState();
}

class _SlipDetailScreenState extends ConsumerState<SlipDetailScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(
        () => ref.read(salaryProvider.notifier).loadSlipDetail(widget.slipId));
  }

  String _fmt(dynamic amount) {
    final num v = (amount is num) ? amount : num.tryParse(amount.toString()) ?? 0;
    return '₹${NumberFormat('#,##,###.##').format(v)}';
  }

  String _monthName(dynamic month) {
    const names = ['', 'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    final m = (month is num) ? month.toInt() : int.tryParse(month.toString()) ?? 0;
    return m >= 1 && m <= 12 ? names[m] : '';
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(salaryProvider);
    final slip = state.selectedSlip;

    return Scaffold(
      appBar: AppBar(
        title: slip != null
            ? Text('${_monthName(slip['month'])} ${slip['year']}')
            : const Text('Salary Slip'),
      ),
      body: state.isLoading
          ? const Center(child: CircularProgressIndicator())
          : slip == null
              ? const Center(child: Text('Slip not found'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [AppColors.primaryDark, AppColors.primaryLight],
                          ),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Column(
                          children: [
                            const Icon(Icons.business, color: Colors.white, size: 40),
                            const SizedBox(height: 8),
                            const Text('Kalina Engineering Pvt. Ltd.',
                                style: TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 16)),
                            const SizedBox(height: 16),
                            Text(slip['full_name'] ?? '',
                                style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold)),
                            Text(
                              '${slip['employee_code'] ?? ''} • ${slip['designation'] ?? ''}',
                              style: const TextStyle(color: Color(0xCCFFFFFF)),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),

                      _SectionCard(
                        title: 'Attendance',
                        rows: [
                          ('Working Days', '${slip['total_days']} days'),
                          ('Days Present', '${slip['present_days']} days'),
                          ('Leave Days', '${slip['leave_days']} days'),
                          ('LWP Days', '${slip['lwp_days']} days'),
                        ],
                      ),
                      const SizedBox(height: 12),

                      _SectionCard(
                        title: 'Earnings',
                        rows: [
                          ('Gross Salary', _fmt(slip['gross_salary'])),
                        ],
                      ),
                      const SizedBox(height: 12),

                      _SectionCard(
                        title: 'Deductions',
                        rows: [
                          ('LWP Deduction', _fmt(slip['deductions'])),
                        ],
                      ),
                      const SizedBox(height: 12),

                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: AppColors.success.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.success.withOpacity(0.3)),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('Net Salary',
                                style: TextStyle(
                                    fontWeight: FontWeight.bold, fontSize: 16)),
                            Text(
                              _fmt(slip['net_salary']),
                              style: const TextStyle(
                                  color: AppColors.success,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 22),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final String title;
  final List<(String, String)> rows;

  const _SectionCard({required this.title, required this.rows});

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
            const SizedBox(height: 12),
            ...rows.map((row) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(row.$1),
                      Text(row.$2, style: const TextStyle(fontWeight: FontWeight.w500)),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }
}
