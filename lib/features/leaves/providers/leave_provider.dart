import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/constants/api_endpoints.dart';

class LeaveState {
  final bool isLoading;
  final String? error;
  final String? successMessage;
  final List<Map<String, dynamic>> balances;
  final List<Map<String, dynamic>> history;

  const LeaveState({
    this.isLoading = false,
    this.error,
    this.successMessage,
    this.balances = const [],
    this.history = const [],
  });

  LeaveState copyWith({
    bool? isLoading,
    String? error,
    String? successMessage,
    List<Map<String, dynamic>>? balances,
    List<Map<String, dynamic>>? history,
  }) {
    return LeaveState(
      isLoading: isLoading ?? this.isLoading,
      error: error,
      successMessage: successMessage,
      balances: balances ?? this.balances,
      history: history ?? this.history,
    );
  }
}

class LeaveNotifier extends StateNotifier<LeaveState> {
  final ApiClient _api;

  LeaveNotifier(this._api) : super(const LeaveState());

  Future<void> loadAll() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final results = await Future.wait([
        _api.get(ApiEndpoints.leavesBalance),
        _api.get(ApiEndpoints.leavesHistory),
      ]);
      state = state.copyWith(
        isLoading: false,
        balances: List<Map<String, dynamic>>.from(results[0]['data'] ?? []),
        history: List<Map<String, dynamic>>.from(results[1]['data'] ?? []),
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  Future<bool> applyLeave({
    required String leaveType,
    required String startDate,
    required String endDate,
    bool isHalfDay = false,
    String reason = '',
  }) async {
    state = state.copyWith(isLoading: true, error: null, successMessage: null);
    try {
      await _api.post(ApiEndpoints.leavesApply, data: {
        'leave_type': leaveType,
        'start_date': startDate,
        'end_date': endDate,
        'is_half_day': isHalfDay ? 1 : 0,
        'reason': reason,
      });
      await loadAll();
      state = state.copyWith(
        isLoading: false,
        successMessage: 'Leave application submitted successfully',
      );
      return true;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  Future<void> cancelLeave(int leaveId) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      await _api.post(ApiEndpoints.leavesCancel, data: {'leave_id': leaveId});
      await loadAll();
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  void clearMessages() {
    state = state.copyWith(error: null, successMessage: null);
  }
}

final leaveProvider = StateNotifierProvider<LeaveNotifier, LeaveState>((ref) {
  return LeaveNotifier(ApiClient.instance);
});
