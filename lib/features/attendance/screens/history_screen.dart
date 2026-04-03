import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:table_calendar/table_calendar.dart';
import '../providers/attendance_provider.dart';
import '../../../core/constants/app_colors.dart';

class HistoryScreen extends ConsumerStatefulWidget {
  const HistoryScreen({super.key});

  @override
  ConsumerState<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends ConsumerState<HistoryScreen> {
  DateTime _focusedDay = DateTime.now();
  DateTime? _selectedDay;

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(attendanceProvider.notifier).loadHistory(
          month: _focusedDay.month,
          year: _focusedDay.year,
        ));
  }

  void _onPageChanged(DateTime focusedDay) {
    setState(() => _focusedDay = focusedDay);
    ref.read(attendanceProvider.notifier).loadHistory(
          month: focusedDay.month,
          year: focusedDay.year,
        );
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'present':
        return AppColors.success;
      case 'late':
        return AppColors.warning;
      case 'half_day':
        return AppColors.primaryLight;
      default:
        return AppColors.textSecondary;
    }
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'present':
        return 'Present';
      case 'late':
        return 'Late';
      case 'half_day':
        return 'Half Day';
      default:
        return status;
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(attendanceProvider);
    final records = state.history;
    final summary = state.summary;

    final markedDates = <DateTime, String>{};
    for (final r in records) {
      if (r['date'] != null) {
        try {
          final d = DateTime.parse(r['date']);
          markedDates[DateTime(d.year, d.month, d.day)] = r['status'] ?? 'present';
        } catch (_) {}
      }
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Attendance History')),
      body: state.isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                if (summary != null)
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    child: Row(
                      children: [
                        _SummaryChip('Present', '${summary['present'] ?? 0}', AppColors.success),
                        const SizedBox(width: 8),
                        _SummaryChip('Late', '${summary['late'] ?? 0}', AppColors.warning),
                        const SizedBox(width: 8),
                        _SummaryChip('Half Day', '${summary['half_day'] ?? 0}', AppColors.primaryLight),
                      ],
                    ),
                  ),
                TableCalendar(
                  firstDay: DateTime(2024),
                  lastDay: DateTime(2027),
                  focusedDay: _focusedDay,
                  selectedDayPredicate: (d) => isSameDay(d, _selectedDay),
                  onDaySelected: (selected, focused) =>
                      setState(() { _selectedDay = selected; _focusedDay = focused; }),
                  onPageChanged: _onPageChanged,
                  calendarStyle: CalendarStyle(
                    todayDecoration: BoxDecoration(
                      color: AppColors.primaryLight.withOpacity(0.3),
                      shape: BoxShape.circle,
                    ),
                    selectedDecoration: const BoxDecoration(
                      color: AppColors.primaryDark,
                      shape: BoxShape.circle,
                    ),
                    markerDecoration: const BoxDecoration(
                      color: AppColors.success,
                      shape: BoxShape.circle,
                    ),
                  ),
                  headerStyle: const HeaderStyle(
                    formatButtonVisible: false,
                    titleCentered: true,
                    titleTextStyle: TextStyle(
                      fontWeight: FontWeight.w600,
                      color: AppColors.textDark,
                    ),
                  ),
                  calendarBuilders: CalendarBuilders(
                    defaultBuilder: (context, day, focusedDay) {
                      final key = DateTime(day.year, day.month, day.day);
                      final status = markedDates[key];
                      if (status != null) {
                        return Container(
                          margin: const EdgeInsets.all(4),
                          decoration: BoxDecoration(
                            color: _statusColor(status).withOpacity(0.15),
                            shape: BoxShape.circle,
                            border: Border.all(color: _statusColor(status), width: 1.5),
                          ),
                          child: Center(
                            child: Text(
                              '${day.day}',
                              style: TextStyle(
                                color: _statusColor(status),
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        );
                      }
                      return null;
                    },
                  ),
                ),
                const Divider(),
                Expanded(
                  child: records.isEmpty
                      ? const Center(
                          child: Text('No records this month',
                              style: TextStyle(color: AppColors.textSecondary)),
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          itemCount: records.length,
                          itemBuilder: (context, i) {
                            final r = records[i];
                            return ListTile(
                              contentPadding: const EdgeInsets.symmetric(vertical: 4),
                              leading: Container(
                                width: 40,
                                height: 40,
                                decoration: BoxDecoration(
                                  color: _statusColor(r['status'] ?? '').withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Center(
                                  child: Text(
                                    r['date'] != null
                                        ? '${DateTime.parse(r['date']).day}'
                                        : '',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      color: _statusColor(r['status'] ?? ''),
                                    ),
                                  ),
                                ),
                              ),
                              title: Text(
                                _statusLabel(r['status'] ?? ''),
                                style: TextStyle(
                                  fontWeight: FontWeight.w600,
                                  color: _statusColor(r['status'] ?? ''),
                                ),
                              ),
                              subtitle: Text(
                                '${r['clock_in'] ?? '--'} → ${r['clock_out'] ?? '--'}',
                                style: const TextStyle(fontSize: 13),
                              ),
                              trailing: r['work_hours'] != null
                                  ? Text(
                                      '${r['work_hours']}h',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w500,
                                        color: AppColors.textSecondary,
                                      ),
                                    )
                                  : null,
                            );
                          },
                        ),
                ),
              ],
            ),
    );
  }
}

class _SummaryChip extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  const _SummaryChip(this.label, this.value, this.color);

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withOpacity(0.3)),
        ),
        child: Column(
          children: [
            Text(value, style: TextStyle(fontWeight: FontWeight.bold, color: color, fontSize: 20)),
            Text(label, style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
          ],
        ),
      ),
    );
  }
}
