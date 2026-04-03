import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/constants/api_endpoints.dart';

class SalaryState {
  final bool isLoading;
  final String? error;
  final List<Map<String, dynamic>> slips;
  final Map<String, dynamic>? selectedSlip;

  const SalaryState({
    this.isLoading = false,
    this.error,
    this.slips = const [],
    this.selectedSlip,
  });

  SalaryState copyWith({
    bool? isLoading,
    String? error,
    List<Map<String, dynamic>>? slips,
    Map<String, dynamic>? selectedSlip,
  }) {
    return SalaryState(
      isLoading: isLoading ?? this.isLoading,
      error: error,
      slips: slips ?? this.slips,
      selectedSlip: selectedSlip ?? this.selectedSlip,
    );
  }
}

class SalaryNotifier extends StateNotifier<SalaryState> {
  final ApiClient _api;

  SalaryNotifier(this._api) : super(const SalaryState());

  Future<void> loadSlips() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final response = await _api.get(ApiEndpoints.salarySlips);
      state = state.copyWith(
        isLoading: false,
        slips: List<Map<String, dynamic>>.from(response['data'] ?? []),
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  Future<void> loadSlipDetail(int slipId) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final response = await _api.get(
        ApiEndpoints.salarySlipDetail,
        queryParams: {'id': slipId},
      );
      state = state.copyWith(
        isLoading: false,
        selectedSlip: response['data'] as Map<String, dynamic>?,
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }
}

final salaryProvider = StateNotifierProvider<SalaryNotifier, SalaryState>((ref) {
  return SalaryNotifier(ApiClient.instance);
});
