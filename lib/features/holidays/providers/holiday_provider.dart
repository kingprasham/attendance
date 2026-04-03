import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/constants/api_endpoints.dart';

class HolidayNotifier extends StateNotifier<AsyncValue<List<Map<String, dynamic>>>> {
  final ApiClient _api;

  HolidayNotifier(this._api) : super(const AsyncValue.loading());

  Future<void> load({int? year}) async {
    state = const AsyncValue.loading();
    try {
      final response = await _api.get(
        ApiEndpoints.holidaysList,
        queryParams: {'year': year ?? DateTime.now().year},
      );
      state = AsyncValue.data(
          List<Map<String, dynamic>>.from(response['data'] ?? []));
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }
}

final holidayProvider = StateNotifierProvider<HolidayNotifier,
    AsyncValue<List<Map<String, dynamic>>>>((ref) {
  return HolidayNotifier(ApiClient.instance);
});
