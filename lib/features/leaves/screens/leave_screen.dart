import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/leave_provider.dart';
import '../../../core/constants/app_colors.dart';

class LeaveScreen extends ConsumerStatefulWidget {
  const LeaveScreen({super.key});

  @override
  ConsumerState<LeaveScreen> createState() => _LeaveScreenState();
}

class _LeaveScreenState extends ConsumerState<LeaveScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    Future.microtask(() => ref.read(leaveProvider.notifier).loadAll());
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'approved':
        return AppColors.success;
      case 'rejected':
        return AppColors.error;
      default:
        return AppColors.warning;
    }
  }

  String _leaveTypeName(dynamic type) {
    switch (type) {
      case 'CL':
        return 'Casual Leave';
      case 'SL':
        return 'Sick Leave';
      case 'EL':
        return 'Earned Leave';
      case 'CO':
        return 'Compensatory Off';
      case 'LWP':
        return 'Leave Without Pay';
      default:
        return type?.toString() ?? '';
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(leaveProvider);

    ref.listen(leaveProvider, (_, s) {
      if (s.error != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(s.error!), backgroundColor: AppColors.error),
        );
        ref.read(leaveProvider.notifier).clearMessages();
      }
    });

    return Scaffold(
      appBar: AppBar(
        title: const Text('Leaves'),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: AppColors.white,
          labelColor: AppColors.white,
          unselectedLabelColor: const Color(0xCCFFFFFF),
          tabs: const [
            Tab(text: 'Balance'),
            Tab(text: 'History'),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => context.push('/apply-leave'),
            tooltip: 'Apply Leave',
          ),
        ],
      ),
      body: state.isLoading
          ? const Center(child: CircularProgressIndicator())
          : TabBarView(
              controller: _tabController,
              children: [
                // Balance tab
                RefreshIndicator(
                  onRefresh: () => ref.read(leaveProvider.notifier).loadAll(),
                  child: state.balances.isEmpty
                      ? const Center(
                          child: Text('No leave policy configured',
                              style: TextStyle(color: AppColors.textSecondary)))
                      : ListView(
                          padding: const EdgeInsets.all(16),
                          children: state.balances.map((b) {
                            final remaining = (b['remaining'] as num?)?.toInt() ?? 0;
                            final total = ((b['total_quota'] as num?)?.toInt() ?? 0) +
                                ((b['carried_forward'] as num?)?.toInt() ?? 0);
                            final used = (b['used'] as num?)?.toInt() ?? 0;
                            return Card(
                              margin: const EdgeInsets.only(bottom: 12),
                              child: Padding(
                                padding: const EdgeInsets.all(16),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        Text(
                                          _leaveTypeName(b['leave_type']),
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w600,
                                            fontSize: 15,
                                          ),
                                        ),
                                        Container(
                                          padding: const EdgeInsets.symmetric(
                                              horizontal: 12, vertical: 4),
                                          decoration: BoxDecoration(
                                            color: remaining > 0
                                                ? AppColors.success.withOpacity(0.1)
                                                : AppColors.error.withOpacity(0.1),
                                            borderRadius: BorderRadius.circular(20),
                                          ),
                                          child: Text(
                                            '$remaining days left',
                                            style: TextStyle(
                                              color: remaining > 0
                                                  ? AppColors.success
                                                  : AppColors.error,
                                              fontWeight: FontWeight.w600,
                                              fontSize: 13,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 12),
                                    LinearProgressIndicator(
                                      value: total > 0 ? used / total : 0,
                                      backgroundColor: AppColors.cardBorder,
                                      valueColor: AlwaysStoppedAnimation<Color>(
                                        remaining > 0 ? AppColors.primaryDark : AppColors.error,
                                      ),
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                    const SizedBox(height: 8),
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        Text('Used: $used',
                                            style: const TextStyle(
                                                fontSize: 12,
                                                color: AppColors.textSecondary)),
                                        Text('Total: $total',
                                            style: const TextStyle(
                                                fontSize: 12,
                                                color: AppColors.textSecondary)),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            );
                          }).toList(),
                        ),
                ),

                // History tab
                RefreshIndicator(
                  onRefresh: () => ref.read(leaveProvider.notifier).loadAll(),
                  child: state.history.isEmpty
                      ? const Center(
                          child: Text('No leave requests yet',
                              style: TextStyle(color: AppColors.textSecondary)))
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: state.history.length,
                          itemBuilder: (context, i) {
                            final h = state.history[i];
                            final status = h['status'] as String? ?? 'pending';
                            return Card(
                              margin: const EdgeInsets.only(bottom: 8),
                              child: ListTile(
                                leading: Container(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 10, vertical: 6),
                                  decoration: BoxDecoration(
                                    color: AppColors.primaryDark.withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Text(
                                    h['leave_type'] ?? '',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      color: AppColors.primaryDark,
                                    ),
                                  ),
                                ),
                                title: Text(
                                  '${h['start_date']} → ${h['end_date']}',
                                  style: const TextStyle(fontSize: 14),
                                ),
                                subtitle: Text(
                                  h['reason'] ?? '',
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(fontSize: 12),
                                ),
                                trailing: Container(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 10, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: _statusColor(status).withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                  child: Text(
                                    status[0].toUpperCase() + status.substring(1),
                                    style: TextStyle(
                                      color: _statusColor(status),
                                      fontWeight: FontWeight.w600,
                                      fontSize: 12,
                                    ),
                                  ),
                                ),
                              ),
                            );
                          },
                        ),
                ),
              ],
            ),
    );
  }
}
