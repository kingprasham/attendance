import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/constants/api_endpoints.dart';
import '../../../core/services/biometric_service.dart';
import '../../../core/services/location_service.dart';
import '../../../core/services/device_service.dart';

class AttendanceState {
  final bool isLoading;
  final String? error;
  final String? successMessage;
  final Map<String, dynamic>? todayRecord;
  final List<Map<String, dynamic>> history;
  final Map<String, dynamic>? summary;

  const AttendanceState({
    this.isLoading = false,
    this.error,
    this.successMessage,
    this.todayRecord,
    this.history = const [],
    this.summary,
  });

  AttendanceState copyWith({
    bool? isLoading,
    String? error,
    String? successMessage,
    Map<String, dynamic>? todayRecord,
    List<Map<String, dynamic>>? history,
    Map<String, dynamic>? summary,
  }) {
    return AttendanceState(
      isLoading: isLoading ?? this.isLoading,
      error: error,
      successMessage: successMessage,
      todayRecord: todayRecord ?? this.todayRecord,
      history: history ?? this.history,
      summary: summary ?? this.summary,
    );
  }
}

class AttendanceNotifier extends StateNotifier<AttendanceState> {
  final ApiClient _api;
  final BiometricService _biometric;
  final LocationService _location;
  final DeviceService _device;

  AttendanceNotifier(this._api, this._biometric, this._location, this._device)
      : super(const AttendanceState());

  Future<void> loadToday() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final response = await _api.get(ApiEndpoints.attendanceToday);
      state = state.copyWith(
        isLoading: false,
        todayRecord: response['data'] as Map<String, dynamic>?,
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  Future<void> loadHistory({int? month, int? year}) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final now = DateTime.now();
      final response = await _api.get(
        ApiEndpoints.attendanceHistory,
        queryParams: {
          'month': month ?? now.month,
          'year': year ?? now.year,
        },
      );
      final data = response['data'] as Map<String, dynamic>;
      state = state.copyWith(
        isLoading: false,
        history: List<Map<String, dynamic>>.from(data['records'] ?? []),
        summary: data['summary'] as Map<String, dynamic>?,
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  Future<bool> clockIn() async {
    state = state.copyWith(isLoading: true, error: null, successMessage: null);
    try {
      final authenticated = await _biometric.authenticate(
        reason: 'Verify your identity to clock in',
      );
      if (!authenticated) {
        state = state.copyWith(
          isLoading: false,
          error: 'Biometric authentication failed.',
        );
        return false;
      }

      final location = await _location.getCurrentLocation();
      final deviceId = await _device.getDeviceId();

      final response = await _api.post(
        ApiEndpoints.clockIn,
        data: {
          'latitude': location.latitude,
          'longitude': location.longitude,
          'device_id': deviceId,
        },
      );

      await loadToday();
      state = state.copyWith(
        isLoading: false,
        successMessage: response['message'] ?? 'Clocked in successfully',
      );
      return true;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  Future<bool> clockOut() async {
    state = state.copyWith(isLoading: true, error: null, successMessage: null);
    try {
      final authenticated = await _biometric.authenticate(
        reason: 'Verify your identity to clock out',
      );
      if (!authenticated) {
        state = state.copyWith(
          isLoading: false,
          error: 'Biometric authentication failed.',
        );
        return false;
      }

      final location = await _location.getCurrentLocation();
      final deviceId = await _device.getDeviceId();

      final response = await _api.post(
        ApiEndpoints.clockOut,
        data: {
          'latitude': location.latitude,
          'longitude': location.longitude,
          'device_id': deviceId,
        },
      );

      await loadToday();
      state = state.copyWith(
        isLoading: false,
        successMessage: response['message'] ?? 'Clocked out successfully',
      );
      return true;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  void clearMessages() {
    state = state.copyWith(error: null, successMessage: null);
  }
}

final attendanceProvider =
    StateNotifierProvider<AttendanceNotifier, AttendanceState>((ref) {
  return AttendanceNotifier(
    ApiClient.instance,
    BiometricService(),
    LocationService(),
    DeviceService(),
  );
});
