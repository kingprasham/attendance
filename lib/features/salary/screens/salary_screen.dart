import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../providers/salary_provider.dart';
import '../../../core/constants/app_colors.dart';

class SalaryScreen extends ConsumerStatefulWidget {
  const SalaryScreen({super.key});

  @override
  ConsumerState<SalaryScreen> createState() => _SalaryScreenState();
}

class _SalaryScreenState extends ConsumerState<SalaryScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(salaryProvider.notifier).loadSlips());
  }

  String _formatCurrency(dynamic amount) {
    final num value = (amount is num) ? amount : num.tryParse(amount.toString()) ?? 0;
    return '₹${NumberFormat('#,##,###.##').format(value)}';
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

    return Scaffold(
      appBar: AppBar(title: const Text('Salary Slips')),
      body: state.isLoading
          ? const Center(child: CircularProgressIndicator())
          : state.slips.isEmpty
              ? const Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.receipt_long, size: 64, color: AppColors.cardBorder),
                      SizedBox(height: 16),
                      Text('No salary slips yet',
                          style: TextStyle(color: AppColors.textSecondary)),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: () => ref.read(salaryProvider.notifier).loadSlips(),
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: state.slips.length,
                    itemBuilder: (context, i) {
                      final slip = state.slips[i];
                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        child: InkWell(
                          borderRadius: BorderRadius.circular(16),
                          onTap: () => context.push(
                            '/salary/detail',
                            extra: slip['id'],
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Row(
                              children: [
                                Container(
                                  width: 48,
                                  height: 48,
                                  decoration: BoxDecoration(
                                    color: AppColors.primaryDark.withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: const Icon(
                                    Icons.receipt_long,
                                    color: AppColors.primaryDark,
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        '${_monthName(slip['month'])} ${slip['year']}',
                                        style: const TextStyle(
                                          fontWeight: FontWeight.w600,
                                          fontSize: 15,
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        '${slip['present_days']} days present • ${slip['total_days']} working days',
                                        style: const TextStyle(
                                          fontSize: 12,
                                          color: AppColors.textSecondary,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    Text(
                                      _formatCurrency(slip['net_salary']),
                                      style: const TextStyle(
                                        fontWeight: FontWeight.bold,
                                        color: AppColors.primaryDark,
                                        fontSize: 16,
                                      ),
                                    ),
                                    const Icon(Icons.chevron_right,
                                        color: AppColors.textSecondary, size: 20),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
    );
  }
}
