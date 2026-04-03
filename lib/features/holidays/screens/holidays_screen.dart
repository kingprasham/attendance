import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/holiday_provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/date_utils.dart';

class HolidaysScreen extends ConsumerStatefulWidget {
  const HolidaysScreen({super.key});

  @override
  ConsumerState<HolidaysScreen> createState() => _HolidaysScreenState();
}

class _HolidaysScreenState extends ConsumerState<HolidaysScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(holidayProvider.notifier).load());
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(holidayProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Holidays')),
      body: state.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(e.toString(), style: const TextStyle(color: AppColors.error)),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => ref.read(holidayProvider.notifier).load(),
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
        data: (holidays) => holidays.isEmpty
            ? const Center(
                child: Text('No holidays this year',
                    style: TextStyle(color: AppColors.textSecondary)))
            : ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: holidays.length,
                itemBuilder: (context, i) {
                  final h = holidays[i];
                  final isOptional = h['is_optional'] == 1 || h['is_optional'] == '1';
                  return Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: ListTile(
                      leading: Container(
                        width: 48,
                        height: 48,
                        decoration: BoxDecoration(
                          color: AppColors.primaryDark.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              h['date'] != null
                                  ? '${DateTime.parse(h['date']).day}'
                                  : '',
                              style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.primaryDark,
                                  fontSize: 16),
                            ),
                            Text(
                              h['date'] != null
                                  ? AppDateUtils.formatDate(h['date'])
                                      .split(' ')[1]
                                  : '',
                              style: const TextStyle(
                                  color: AppColors.primaryDark, fontSize: 10),
                            ),
                          ],
                        ),
                      ),
                      title: Text(h['name'] ?? '',
                          style: const TextStyle(fontWeight: FontWeight.w600)),
                      subtitle: Text(AppDateUtils.formatDate(h['date']),
                          style: const TextStyle(fontSize: 12)),
                      trailing: isOptional
                          ? Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: AppColors.warning.withOpacity(0.1),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: const Text('Optional',
                                  style: TextStyle(
                                      color: AppColors.warning, fontSize: 11)),
                            )
                          : null,
                    ),
                  );
                },
              ),
      ),
    );
  }
}
