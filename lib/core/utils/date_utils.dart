import 'package:intl/intl.dart';

class AppDateUtils {
  static final _timeFormatter = DateFormat('hh:mm a');
  static final _dateFormatter = DateFormat('dd MMM yyyy');
  static final _monthYearFormatter = DateFormat('MMMM yyyy');
  static final _dayMonthFormatter = DateFormat('dd MMM');
  static final _apiDateFormatter = DateFormat('yyyy-MM-dd');

  static String formatTime(String? isoString) {
    if (isoString == null || isoString.isEmpty) return '--:--';
    try {
      final dt = DateTime.parse(isoString).toLocal();
      return _timeFormatter.format(dt);
    } catch (_) {
      return isoString;
    }
  }

  static String formatDate(String? dateString) {
    if (dateString == null || dateString.isEmpty) return '';
    try {
      return _dateFormatter.format(DateTime.parse(dateString));
    } catch (_) {
      return dateString;
    }
  }

  static String formatMonthYear(int month, int year) {
    return _monthYearFormatter.format(DateTime(year, month));
  }

  static String formatDayMonth(DateTime date) {
    return _dayMonthFormatter.format(date);
  }

  static String toApiDate(DateTime date) {
    return _apiDateFormatter.format(date);
  }

  static String monthName(int month) {
    return DateFormat('MMMM').format(DateTime(2000, month));
  }
}
