import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/services/auth_service.dart';
import '../../../core/services/device_service.dart';
import '../../../core/services/secure_storage.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthState {
  final AuthStatus status;
  final String? fullName;
  final int? employeeId;
  final String? error;
  final bool isLoading;

  const AuthState({
    this.status = AuthStatus.unknown,
    this.fullName,
    this.employeeId,
    this.error,
    this.isLoading = false,
  });

  AuthState copyWith({
    AuthStatus? status,
    String? fullName,
    int? employeeId,
    String? error,
    bool? isLoading,
  }) {
    return AuthState(
      status: status ?? this.status,
      fullName: fullName ?? this.fullName,
      employeeId: employeeId ?? this.employeeId,
      error: error,
      isLoading: isLoading ?? this.isLoading,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final AuthService _authService;
  final DeviceService _deviceService;
  final SecureStorageService _storage;

  AuthNotifier(this._authService, this._deviceService, this._storage)
      : super(const AuthState());

  Future<void> checkAuth() async {
    final isLoggedIn = await _authService.isLoggedIn();
    if (isLoggedIn) {
      final info = await _storage.getUserInfo();
      state = AuthState(
        status: AuthStatus.authenticated,
        fullName: info['full_name'],
        employeeId: int.tryParse(info['employee_id'] ?? ''),
      );
    } else {
      state = const AuthState(status: AuthStatus.unauthenticated);
    }
  }

  Future<bool> login(String username, String password) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final deviceId = await _deviceService.getDeviceId();
      final data = await _authService.login(
        email: username,
        password: password,
        deviceId: deviceId,
      );

      // Register device if first login
      if (data['needs_device_registration'] == true) {
        await _authService.registerDevice(deviceId: deviceId);
      }

      state = AuthState(
        status: AuthStatus.authenticated,
        fullName: data['full_name'],
        employeeId: data['employee_id'],
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        status: AuthStatus.unauthenticated,
        error: e.toString(),
      );
      return false;
    }
  }

  Future<void> logout() async {
    await _authService.logout();
    state = const AuthState(status: AuthStatus.unauthenticated);
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(
    AuthService(),
    DeviceService(),
    SecureStorageService(),
  );
});
