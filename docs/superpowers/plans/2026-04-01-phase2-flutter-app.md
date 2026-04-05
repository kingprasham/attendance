# Phase 2: Flutter Mobile App — Implementation Plan

**Goal:** Build the complete employee-facing Flutter app for the Kalina Engineering Attendance System.

**Architecture:** Flutter 3.27+, Dart, Riverpod (StateNotifier), Dio, GoRouter, feature-based folder structure.

**Design Spec:** `docs/superpowers/specs/2026-04-01-kalina-attendance-system-design.md`

---

## File Structure

```
lib/
├── main.dart
├── app/
│   ├── theme.dart
│   └── routes.dart
├── core/
│   ├── constants/
│   │   ├── api_endpoints.dart
│   │   └── app_colors.dart
│   ├── network/
│   │   ├── api_client.dart
│   │   ├── auth_interceptor.dart
│   │   └── api_exceptions.dart
│   ├── services/
│   │   ├── auth_service.dart
│   │   ├── biometric_service.dart
│   │   ├── location_service.dart
│   │   ├── device_service.dart
│   │   └── secure_storage.dart
│   └── utils/
│       └── date_utils.dart
├── features/
│   ├── auth/
│   │   ├── providers/auth_provider.dart
│   │   ├── screens/splash_screen.dart
│   │   └── screens/login_screen.dart
│   ├── attendance/
│   │   ├── providers/attendance_provider.dart
│   │   ├── screens/home_screen.dart
│   │   └── screens/history_screen.dart
│   ├── leaves/
│   │   ├── providers/leave_provider.dart
│   │   ├── screens/leave_screen.dart
│   │   └── screens/apply_leave_screen.dart
│   ├── salary/
│   │   ├── providers/salary_provider.dart
│   │   ├── screens/salary_screen.dart
│   │   └── screens/slip_detail_screen.dart
│   ├── profile/
│   │   ├── providers/profile_provider.dart
│   │   └── screens/profile_screen.dart
│   ├── notifications/
│   │   ├── providers/notification_provider.dart
│   │   └── screens/notifications_screen.dart
│   └── holidays/
│       ├── providers/holiday_provider.dart
│       └── screens/holidays_screen.dart
└── shared/
    └── widgets/
        ├── app_drawer.dart
        ├── loading_overlay.dart
        └── kalina_error_widget.dart
```

---

## Task 1: pubspec.yaml — Add All Packages

**Files:**
- Modify: `pubspec.yaml`

```yaml
name: attendance
description: "Kalina Engineering Attendance Management App"
publish_to: 'none'
version: 1.0.0+1

environment:
  sdk: ^3.8.1

dependencies:
  flutter:
    sdk: flutter
  cupertino_icons: ^1.0.8

  # State management
  flutter_riverpod: ^2.6.1

  # HTTP client
  dio: ^5.7.0

  # Navigation
  go_router: ^14.6.2

  # Biometrics
  local_auth: ^2.3.0

  # GPS
  geolocator: ^13.0.2
  permission_handler: ^11.3.1

  # Device ID
  device_info_plus: ^10.1.2

  # Secure token storage
  flutter_secure_storage: ^9.2.2

  # Firebase
  firebase_core: ^3.8.0
  firebase_messaging: ^15.1.5

  # Local cache
  hive_flutter: ^1.1.0

  # Anti-spoofing
  safe_device: ^1.1.6

  # Date formatting
  intl: ^0.19.0

  # Calendar widget
  table_calendar: ^3.1.2

dev_dependencies:
  flutter_test:
    sdk: flutter
  flutter_lints: ^5.0.0

flutter:
  uses-material-design: true
```

**Commit:** `git add pubspec.yaml && git commit -m "feat: add Flutter dependencies for phase 2"`

---

## Task 2: Core Constants + Theme

**Files:**
- Create: `lib/core/constants/app_colors.dart`
- Create: `lib/core/constants/api_endpoints.dart`
- Create: `lib/core/utils/date_utils.dart`
- Create: `lib/app/theme.dart`

### `lib/core/constants/app_colors.dart`
```dart
import 'package:flutter/material.dart';

class AppColors {
  static const Color primaryDark = Color(0xFF0055A4);
  static const Color primaryLight = Color(0xFF4A9BD9);
  static const Color white = Color(0xFFFFFFFF);
  static const Color background = Color(0xFFF8F9FB);
  static const Color textDark = Color(0xFF1F2937);
  static const Color success = Color(0xFF059669);
  static const Color error = Color(0xFFDC2626);
  static const Color warning = Color(0xFFD97706);
  static const Color cardBorder = Color(0xFFE5E7EB);
  static const Color textSecondary = Color(0xFF6B7280);
}
```

### `lib/core/constants/api_endpoints.dart`
```dart
class ApiEndpoints {
  static const String baseUrl = 'https://yourdomain.com/api';

  // Auth
  static const String login = '/auth/login';
  static const String adminLogin = '/auth/admin_login';
  static const String refresh = '/auth/refresh';
  static const String registerDevice = '/auth/register_device';

  // Attendance
  static const String clockIn = '/attendance/clock_in';
  static const String clockOut = '/attendance/clock_out';
  static const String attendanceToday = '/attendance/today';
  static const String attendanceHistory = '/attendance/history';

  // Leaves
  static const String leavesApply = '/leaves/apply';
  static const String leavesCancel = '/leaves/cancel';
  static const String leavesBalance = '/leaves/balance';
  static const String leavesHistory = '/leaves/history';

  // Salary
  static const String salarySlips = '/salary/slips';
  static const String salarySlipDetail = '/salary/slip_detail';

  // Profile
  static const String profile = '/employees/profile';

  // Notifications
  static const String notificationsList = '/notifications/list';
  static const String notificationsMarkRead = '/notifications/mark_read';

  // Holidays
  static const String holidaysList = '/holidays/list';

  // Leave policies
  static const String leavePoliciesView = '/leave_policies/view';
}
```

### `lib/core/utils/date_utils.dart`
```dart
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
```

### `lib/app/theme.dart`
```dart
import 'package:flutter/material.dart';
import '../core/constants/app_colors.dart';

class AppTheme {
  static ThemeData get light {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.primaryDark,
        primary: AppColors.primaryDark,
        secondary: AppColors.primaryLight,
        surface: AppColors.white,
        error: AppColors.error,
      ),
      scaffoldBackgroundColor: AppColors.background,
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.primaryDark,
        foregroundColor: AppColors.white,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          color: AppColors.white,
          fontSize: 18,
          fontWeight: FontWeight.w600,
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primaryDark,
          foregroundColor: AppColors.white,
          minimumSize: const Size(double.infinity, 52),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.primaryDark,
          side: const BorderSide(color: AppColors.primaryDark),
          minimumSize: const Size(double.infinity, 52),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.cardBorder),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.cardBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primaryDark, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.error),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
      cardTheme: CardThemeData(
        color: AppColors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: AppColors.cardBorder),
        ),
      ),
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: AppColors.white,
        selectedItemColor: AppColors.primaryDark,
        unselectedItemColor: AppColors.textSecondary,
        type: BottomNavigationBarType.fixed,
        elevation: 8,
      ),
      dividerTheme: const DividerThemeData(color: AppColors.cardBorder),
      textTheme: const TextTheme(
        headlineLarge: TextStyle(color: AppColors.textDark, fontWeight: FontWeight.bold),
        headlineMedium: TextStyle(color: AppColors.textDark, fontWeight: FontWeight.bold),
        titleLarge: TextStyle(color: AppColors.textDark, fontWeight: FontWeight.w600),
        titleMedium: TextStyle(color: AppColors.textDark, fontWeight: FontWeight.w500),
        bodyLarge: TextStyle(color: AppColors.textDark),
        bodyMedium: TextStyle(color: AppColors.textDark),
        bodySmall: TextStyle(color: AppColors.textSecondary),
      ),
    );
  }
}
```

**Commit:** `git add lib/core/constants/ lib/core/utils/ lib/app/theme.dart && git commit -m "feat: add app colors, API endpoints, date utils, and theme"`

---

## Task 3: Secure Storage + Network Layer

**Files:**
- Create: `lib/core/services/secure_storage.dart`
- Create: `lib/core/network/api_exceptions.dart`
- Create: `lib/core/network/auth_interceptor.dart`
- Create: `lib/core/network/api_client.dart`

### `lib/core/services/secure_storage.dart`
```dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureStorageService {
  static const _storage = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );

  static const _accessTokenKey = 'access_token';
  static const _refreshTokenKey = 'refresh_token';
  static const _employeeIdKey = 'employee_id';
  static const _fullNameKey = 'full_name';
  static const _branchIdKey = 'branch_id';

  Future<void> saveTokens({
    required String accessToken,
    required String refreshToken,
  }) async {
    await Future.wait([
      _storage.write(key: _accessTokenKey, value: accessToken),
      _storage.write(key: _refreshTokenKey, value: refreshToken),
    ]);
  }

  Future<String?> getAccessToken() => _storage.read(key: _accessTokenKey);
  Future<String?> getRefreshToken() => _storage.read(key: _refreshTokenKey);

  Future<void> saveUserInfo({
    required int employeeId,
    required String fullName,
    required int branchId,
  }) async {
    await Future.wait([
      _storage.write(key: _employeeIdKey, value: employeeId.toString()),
      _storage.write(key: _fullNameKey, value: fullName),
      _storage.write(key: _branchIdKey, value: branchId.toString()),
    ]);
  }

  Future<Map<String, String?>> getUserInfo() async {
    final results = await Future.wait([
      _storage.read(key: _employeeIdKey),
      _storage.read(key: _fullNameKey),
      _storage.read(key: _branchIdKey),
    ]);
    return {
      'employee_id': results[0],
      'full_name': results[1],
      'branch_id': results[2],
    };
  }

  Future<void> clearAll() => _storage.deleteAll();
}
```

### `lib/core/network/api_exceptions.dart`
```dart
class ApiException implements Exception {
  final String message;
  final int? statusCode;

  const ApiException(this.message, {this.statusCode});

  @override
  String toString() => message;
}

class UnauthorizedException extends ApiException {
  const UnauthorizedException([String message = 'Session expired. Please log in again.'])
      : super(message, statusCode: 401);
}

class NetworkException extends ApiException {
  const NetworkException([String message = 'No internet connection. Please try again.'])
      : super(message);
}
```

### `lib/core/network/auth_interceptor.dart`
```dart
import 'package:dio/dio.dart';
import '../services/secure_storage.dart';
import '../constants/api_endpoints.dart';
import 'api_exceptions.dart';

class AuthInterceptor extends Interceptor {
  final Dio _dio;
  final SecureStorageService _storage;
  bool _isRefreshing = false;

  AuthInterceptor(this._dio, this._storage);

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    final token = await _storage.getAccessToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  Future<void> onError(
    DioException err,
    ErrorInterceptorHandler handler,
  ) async {
    if (err.response?.statusCode == 401 && !_isRefreshing) {
      _isRefreshing = true;
      try {
        final refreshToken = await _storage.getRefreshToken();
        if (refreshToken == null) {
          _isRefreshing = false;
          handler.next(err);
          return;
        }

        final response = await _dio.post(
          '${ApiEndpoints.baseUrl}${ApiEndpoints.refresh}',
          data: {'refresh_token': refreshToken},
          options: Options(headers: {'Authorization': ''}),
        );

        final data = response.data['data'];
        await _storage.saveTokens(
          accessToken: data['access_token'],
          refreshToken: data['refresh_token'],
        );

        _isRefreshing = false;

        // Retry original request
        final options = err.requestOptions;
        options.headers['Authorization'] = 'Bearer ${data['access_token']}';
        final retryResponse = await _dio.fetch(options);
        handler.resolve(retryResponse);
      } catch (_) {
        _isRefreshing = false;
        await _storage.clearAll();
        handler.next(err);
      }
    } else {
      handler.next(err);
    }
  }
}
```

### `lib/core/network/api_client.dart`
```dart
import 'package:dio/dio.dart';
import '../constants/api_endpoints.dart';
import '../services/secure_storage.dart';
import 'auth_interceptor.dart';
import 'api_exceptions.dart';

class ApiClient {
  static ApiClient? _instance;
  late final Dio _dio;
  final SecureStorageService _storage = SecureStorageService();

  ApiClient._() {
    _dio = Dio(BaseOptions(
      baseUrl: ApiEndpoints.baseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 15),
      headers: {'Content-Type': 'application/json'},
    ));
    _dio.interceptors.add(AuthInterceptor(_dio, _storage));
  }

  static ApiClient get instance {
    _instance ??= ApiClient._();
    return _instance!;
  }

  Future<Map<String, dynamic>> get(
    String path, {
    Map<String, dynamic>? queryParams,
  }) async {
    try {
      final response = await _dio.get(path, queryParameters: queryParams);
      return _handleResponse(response);
    } on DioException catch (e) {
      throw _handleDioError(e);
    }
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? data,
  }) async {
    try {
      final response = await _dio.post(path, data: data);
      return _handleResponse(response);
    } on DioException catch (e) {
      throw _handleDioError(e);
    }
  }

  Future<Map<String, dynamic>> put(
    String path, {
    Map<String, dynamic>? data,
  }) async {
    try {
      final response = await _dio.put(path, data: data);
      return _handleResponse(response);
    } on DioException catch (e) {
      throw _handleDioError(e);
    }
  }

  Map<String, dynamic> _handleResponse(Response response) {
    final data = response.data as Map<String, dynamic>;
    if (data['success'] == true) {
      return data;
    }
    throw ApiException(
      data['message'] ?? 'An error occurred',
      statusCode: response.statusCode,
    );
  }

  ApiException _handleDioError(DioException e) {
    if (e.response != null) {
      final data = e.response?.data;
      final message = (data is Map) ? (data['message'] ?? 'Request failed') : 'Request failed';
      if (e.response?.statusCode == 401) {
        return UnauthorizedException(message);
      }
      return ApiException(message, statusCode: e.response?.statusCode);
    }
    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.type == DioExceptionType.connectionError) {
      return const NetworkException();
    }
    return ApiException(e.message ?? 'Unknown error');
  }
}
```

**Commit:** `git add lib/core/services/secure_storage.dart lib/core/network/ && git commit -m "feat: add secure storage and Dio network layer with JWT interceptor"`

---

## Task 4: Core Services

**Files:**
- Create: `lib/core/services/auth_service.dart`
- Create: `lib/core/services/biometric_service.dart`
- Create: `lib/core/services/location_service.dart`
- Create: `lib/core/services/device_service.dart`

### `lib/core/services/auth_service.dart`
```dart
import '../network/api_client.dart';
import '../constants/api_endpoints.dart';
import '../services/secure_storage.dart';

class AuthService {
  final ApiClient _api = ApiClient.instance;
  final SecureStorageService _storage = SecureStorageService();

  Future<Map<String, dynamic>> login({
    required String username,
    required String password,
    required String deviceId,
  }) async {
    final response = await _api.post(
      ApiEndpoints.login,
      data: {
        'username': username,
        'password': password,
        'device_id': deviceId,
      },
    );
    final data = response['data'] as Map<String, dynamic>;

    await _storage.saveTokens(
      accessToken: data['access_token'],
      refreshToken: data['refresh_token'],
    );
    await _storage.saveUserInfo(
      employeeId: data['employee_id'],
      fullName: data['full_name'],
      branchId: data['branch_id'],
    );

    return data;
  }

  Future<void> registerDevice({
    required String deviceId,
    String? fcmToken,
  }) async {
    await _api.post(
      ApiEndpoints.registerDevice,
      data: {
        'device_id': deviceId,
        if (fcmToken != null) 'fcm_token': fcmToken,
      },
    );
  }

  Future<bool> isLoggedIn() async {
    final token = await _storage.getAccessToken();
    return token != null;
  }

  Future<void> logout() async {
    await _storage.clearAll();
  }
}
```

### `lib/core/services/biometric_service.dart`
```dart
import 'package:local_auth/local_auth.dart';

class BiometricService {
  final LocalAuthentication _auth = LocalAuthentication();

  Future<bool> isAvailable() async {
    try {
      return await _auth.canCheckBiometrics || await _auth.isDeviceSupported();
    } catch (_) {
      return false;
    }
  }

  Future<bool> authenticate({String reason = 'Verify your identity to continue'}) async {
    try {
      final available = await isAvailable();
      if (!available) return true; // Fallback: allow if no biometrics

      return await _auth.authenticate(
        localizedReason: reason,
        options: const AuthenticationOptions(
          biometricOnly: false,
          stickyAuth: true,
        ),
      );
    } catch (_) {
      return false;
    }
  }
}
```

### `lib/core/services/location_service.dart`
```dart
import 'package:geolocator/geolocator.dart';
import 'package:safe_device/safe_device.dart';

class LocationResult {
  final double latitude;
  final double longitude;
  const LocationResult(this.latitude, this.longitude);
}

class LocationException implements Exception {
  final String message;
  const LocationException(this.message);
  @override
  String toString() => message;
}

class LocationService {
  Future<LocationResult> getCurrentLocation() async {
    // Check for fake GPS / rooted device
    final isMockLocation = await SafeDevice.isMockLocation;
    if (isMockLocation) {
      throw const LocationException(
        'Mock location detected. Disable fake GPS apps and try again.',
      );
    }

    // Check permission
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        throw const LocationException('Location permission denied.');
      }
    }
    if (permission == LocationPermission.deniedForever) {
      throw const LocationException(
        'Location permission permanently denied. Enable it in Settings.',
      );
    }

    // Check if GPS is on
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      throw const LocationException('GPS is disabled. Please enable location services.');
    }

    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 15),
        ),
      );
      return LocationResult(position.latitude, position.longitude);
    } catch (e) {
      throw LocationException('Could not get location: ${e.toString()}');
    }
  }
}
```

### `lib/core/services/device_service.dart`
```dart
import 'dart:io';
import 'package:device_info_plus/device_info_plus.dart';

class DeviceService {
  static String? _cachedId;

  Future<String> getDeviceId() async {
    if (_cachedId != null) return _cachedId!;

    final info = DeviceInfoPlugin();
    try {
      if (Platform.isAndroid) {
        final android = await info.androidInfo;
        _cachedId = android.id;
      } else if (Platform.isIOS) {
        final ios = await info.iosInfo;
        _cachedId = ios.identifierForVendor ?? 'ios-unknown';
      } else {
        _cachedId = 'unknown-device';
      }
    } catch (_) {
      _cachedId = 'unknown-device';
    }
    return _cachedId!;
  }
}
```

**Commit:** `git add lib/core/services/ && git commit -m "feat: add auth, biometric, location, and device services"`

---

## Task 5: Auth Feature

**Files:**
- Create: `lib/features/auth/providers/auth_provider.dart`
- Create: `lib/features/auth/screens/splash_screen.dart`
- Create: `lib/features/auth/screens/login_screen.dart`

### `lib/features/auth/providers/auth_provider.dart`
```dart
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
        username: username,
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
```

### `lib/features/auth/screens/splash_screen.dart`
```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/auth_provider.dart';
import '../../../core/constants/app_colors.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    await Future.delayed(const Duration(milliseconds: 1200));
    if (!mounted) return;
    await ref.read(authProvider.notifier).checkAuth();
  }

  @override
  Widget build(BuildContext context) {
    ref.listen(authProvider, (_, state) {
      if (state.status == AuthStatus.authenticated) {
        context.go('/home');
      } else if (state.status == AuthStatus.unauthenticated) {
        context.go('/login');
      }
    });

    return Scaffold(
      backgroundColor: AppColors.primaryDark,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 100,
              height: 100,
              decoration: BoxDecoration(
                color: AppColors.white,
                borderRadius: BorderRadius.circular(24),
              ),
              padding: const EdgeInsets.all(16),
              child: const Icon(
                Icons.business,
                color: AppColors.primaryDark,
                size: 60,
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'Kalina Engineering',
              style: TextStyle(
                color: AppColors.white,
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Attendance Management',
              style: TextStyle(
                color: Color(0xCCFFFFFF),
                fontSize: 14,
              ),
            ),
            const SizedBox(height: 48),
            const CircularProgressIndicator(color: AppColors.white),
          ],
        ),
      ),
    );
  }
}
```

### `lib/features/auth/screens/login_screen.dart`
```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/auth_provider.dart';
import '../../../core/constants/app_colors.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;
    final success = await ref.read(authProvider.notifier).login(
      _usernameController.text.trim(),
      _passwordController.text,
    );
    if (success && mounted) {
      context.go('/home');
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(authProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 40),
              Center(
                child: Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: AppColors.primaryDark,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Icon(Icons.business, color: Colors.white, size: 48),
                ),
              ),
              const SizedBox(height: 24),
              const Center(
                child: Text(
                  'Kalina Engineering',
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textDark,
                  ),
                ),
              ),
              const SizedBox(height: 4),
              const Center(
                child: Text(
                  'Sign in to your account',
                  style: TextStyle(color: AppColors.textSecondary),
                ),
              ),
              const SizedBox(height: 40),
              Form(
                key: _formKey,
                child: Column(
                  children: [
                    TextFormField(
                      controller: _usernameController,
                      decoration: const InputDecoration(
                        labelText: 'Username',
                        prefixIcon: Icon(Icons.person_outline),
                      ),
                      validator: (v) =>
                          (v == null || v.trim().isEmpty) ? 'Enter your username' : null,
                      textInputAction: TextInputAction.next,
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _passwordController,
                      obscureText: _obscurePassword,
                      decoration: InputDecoration(
                        labelText: 'Password',
                        prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          icon: Icon(_obscurePassword
                              ? Icons.visibility_outlined
                              : Icons.visibility_off_outlined),
                          onPressed: () =>
                              setState(() => _obscurePassword = !_obscurePassword),
                        ),
                      ),
                      validator: (v) =>
                          (v == null || v.isEmpty) ? 'Enter your password' : null,
                      onFieldSubmitted: (_) => _login(),
                    ),
                    const SizedBox(height: 8),
                    if (state.error != null)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        margin: const EdgeInsets.only(top: 8),
                        decoration: BoxDecoration(
                          color: AppColors.error.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: AppColors.error.withOpacity(0.3)),
                        ),
                        child: Text(
                          state.error!,
                          style: const TextStyle(color: AppColors.error, fontSize: 13),
                        ),
                      ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: state.isLoading ? null : _login,
                      child: state.isLoading
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Text('Sign In'),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

**Commit:** `git add lib/features/auth/ && git commit -m "feat: add auth provider, splash screen, and login screen"`

---

## Task 6: Attendance Feature

**Files:**
- Create: `lib/features/attendance/providers/attendance_provider.dart`
- Create: `lib/features/attendance/screens/home_screen.dart`
- Create: `lib/features/attendance/screens/history_screen.dart`

### `lib/features/attendance/providers/attendance_provider.dart`
```dart
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
      // Biometric verification
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

      // Get GPS
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
```

### `lib/features/attendance/screens/home_screen.dart`
```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/attendance_provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../../features/auth/providers/auth_provider.dart';
import '../../../features/notifications/providers/notification_provider.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref.read(attendanceProvider.notifier).loadToday();
      ref.read(notificationProvider.notifier).loadNotifications();
    });
  }

  @override
  Widget build(BuildContext context) {
    final attendanceState = ref.watch(attendanceProvider);
    final authState = ref.watch(authProvider);
    final today = attendanceState.todayRecord;
    final isClockedIn = today?['clocked_in'] == true;
    final isClockedOut = today?['clocked_out'] == true;

    // Show feedback
    ref.listen(attendanceProvider, (_, state) {
      if (state.error != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(state.error!),
            backgroundColor: AppColors.error,
            behavior: SnackBarBehavior.floating,
          ),
        );
        ref.read(attendanceProvider.notifier).clearMessages();
      }
      if (state.successMessage != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(state.successMessage!),
            backgroundColor: AppColors.success,
            behavior: SnackBarBehavior.floating,
          ),
        );
        ref.read(attendanceProvider.notifier).clearMessages();
      }
    });

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Good Morning', style: TextStyle(fontSize: 12, color: Color(0xCCFFFFFF))),
            Text(
              authState.fullName ?? 'Employee',
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
            ),
          ],
        ),
        actions: [
          Consumer(builder: (context, ref, _) {
            final notifState = ref.watch(notificationProvider);
            final unread = notifState.notifications
                .where((n) => n['is_read'] == 0)
                .length;
            return Stack(
              children: [
                IconButton(
                  icon: const Icon(Icons.notifications_outlined),
                  onPressed: () => context.go('/notifications'),
                ),
                if (unread > 0)
                  Positioned(
                    right: 8,
                    top: 8,
                    child: Container(
                      width: 16,
                      height: 16,
                      decoration: const BoxDecoration(
                        color: AppColors.error,
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          unread > 9 ? '9+' : unread.toString(),
                          style: const TextStyle(color: Colors.white, fontSize: 9),
                        ),
                      ),
                    ),
                  ),
              ],
            );
          }),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(attendanceProvider.notifier).loadToday(),
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Status Card
              _StatusCard(today: today),
              const SizedBox(height: 20),

              // Clock In/Out Button
              if (attendanceState.isLoading)
                const Center(child: CircularProgressIndicator())
              else if (!isClockedIn)
                _AttendanceButton(
                  label: 'Clock In',
                  icon: Icons.login,
                  color: AppColors.success,
                  onTap: () => ref.read(attendanceProvider.notifier).clockIn(),
                )
              else if (!isClockedOut)
                _AttendanceButton(
                  label: 'Clock Out',
                  icon: Icons.logout,
                  color: AppColors.error,
                  onTap: () => ref.read(attendanceProvider.notifier).clockOut(),
                )
              else
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: AppColors.success.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: AppColors.success.withOpacity(0.3)),
                  ),
                  child: const Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.check_circle, color: AppColors.success),
                      SizedBox(width: 8),
                      Text(
                        'Attendance marked for today',
                        style: TextStyle(
                          color: AppColors.success,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),

              const SizedBox(height: 20),

              // Quick Actions
              Row(
                children: [
                  Expanded(
                    child: _QuickActionCard(
                      icon: Icons.calendar_month,
                      label: 'History',
                      color: AppColors.primaryLight,
                      onTap: () => context.go('/history'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _QuickActionCard(
                      icon: Icons.beach_access,
                      label: 'Apply Leave',
                      color: AppColors.warning,
                      onTap: () => context.go('/apply-leave'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _QuickActionCard(
                      icon: Icons.receipt_long,
                      label: 'Salary',
                      color: AppColors.success,
                      onTap: () => context.go('/salary'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusCard extends StatelessWidget {
  final Map<String, dynamic>? today;
  const _StatusCard({this.today});

  @override
  Widget build(BuildContext context) {
    final isClockedIn = today?['clocked_in'] == true;
    final isClockedOut = today?['clocked_out'] == true;
    final status = today?['status'] as String?;

    Color statusColor = AppColors.textSecondary;
    String statusLabel = 'Not clocked in';
    IconData statusIcon = Icons.radio_button_unchecked;

    if (isClockedIn && !isClockedOut) {
      statusColor = AppColors.success;
      statusLabel = status == 'late' ? 'Late' : 'Present';
      statusIcon = Icons.radio_button_checked;
    } else if (isClockedOut) {
      statusColor = AppColors.primaryDark;
      statusLabel = 'Completed';
      statusIcon = Icons.check_circle;
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppColors.primaryDark, AppColors.primaryLight],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            _formattedDate(),
            style: const TextStyle(color: Color(0xCCFFFFFF), fontSize: 13),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Icon(statusIcon, color: Colors.white, size: 20),
              const SizedBox(width: 8),
              Text(
                statusLabel,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          if (isClockedIn) ...[
            const SizedBox(height: 16),
            Row(
              children: [
                _TimeInfo(
                  label: 'Clock In',
                  time: today?['clock_in_time'] ?? '--:--',
                ),
                const SizedBox(width: 32),
                if (isClockedOut)
                  _TimeInfo(
                    label: 'Clock Out',
                    time: today?['clock_out_time'] ?? '--:--',
                  ),
                if (isClockedOut) ...[
                  const SizedBox(width: 32),
                  _TimeInfo(
                    label: 'Hours',
                    time: '${today?['work_hours'] ?? '0'}h',
                  ),
                ],
              ],
            ),
          ],
        ],
      ),
    );
  }

  String _formattedDate() {
    final now = DateTime.now();
    final days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    final months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return '${days[now.weekday - 1]}, ${now.day} ${months[now.month - 1]} ${now.year}';
  }
}

class _TimeInfo extends StatelessWidget {
  final String label;
  final String time;
  const _TimeInfo({required this.label, required this.time});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(color: Color(0xCCFFFFFF), fontSize: 11)),
        Text(time, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600)),
      ],
    );
  }
}

class _AttendanceButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  const _AttendanceButton({
    required this.label,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: 20),
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: color.withOpacity(0.3),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: Colors.white, size: 24),
            const SizedBox(width: 12),
            Text(
              label,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _QuickActionCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;
  const _QuickActionCard({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 16),
        decoration: BoxDecoration(
          color: AppColors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppColors.cardBorder),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 28),
            const SizedBox(height: 6),
            Text(
              label,
              style: const TextStyle(fontSize: 11, color: AppColors.textDark),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
```

### `lib/features/attendance/screens/history_screen.dart`
```dart
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
                // Summary chips
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
                // Calendar
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
                // Records list
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
                                    '${DateTime.parse(r['date']).day}',
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
```

**Commit:** `git add lib/features/attendance/ && git commit -m "feat: add attendance provider, home screen, and history calendar"`

---

## Task 7: Leaves Feature

**Files:**
- Create: `lib/features/leaves/providers/leave_provider.dart`
- Create: `lib/features/leaves/screens/leave_screen.dart`
- Create: `lib/features/leaves/screens/apply_leave_screen.dart`

### `lib/features/leaves/providers/leave_provider.dart`
```dart
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
```

### `lib/features/leaves/screens/leave_screen.dart`
```dart
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
}
```

### `lib/features/leaves/screens/apply_leave_screen.dart`
```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../providers/leave_provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/date_utils.dart';

class ApplyLeaveScreen extends ConsumerStatefulWidget {
  const ApplyLeaveScreen({super.key});

  @override
  ConsumerState<ApplyLeaveScreen> createState() => _ApplyLeaveScreenState();
}

class _ApplyLeaveScreenState extends ConsumerState<ApplyLeaveScreen> {
  final _formKey = GlobalKey<FormState>();
  String _leaveType = 'CL';
  DateTime _startDate = DateTime.now();
  DateTime _endDate = DateTime.now();
  bool _isHalfDay = false;
  final _reasonController = TextEditingController();

  final _leaveTypes = const [
    ('CL', 'Casual Leave'),
    ('SL', 'Sick Leave'),
    ('EL', 'Earned Leave'),
    ('CO', 'Compensatory Off'),
    ('LWP', 'Leave Without Pay'),
  ];

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _pickDate({required bool isStart}) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: isStart ? _startDate : _endDate,
      firstDate: DateTime.now().subtract(const Duration(days: 7)),
      lastDate: DateTime.now().add(const Duration(days: 90)),
      builder: (context, child) => Theme(
        data: Theme.of(context).copyWith(
          colorScheme: const ColorScheme.light(primary: AppColors.primaryDark),
        ),
        child: child!,
      ),
    );
    if (picked != null) {
      setState(() {
        if (isStart) {
          _startDate = picked;
          if (_endDate.isBefore(_startDate)) _endDate = _startDate;
        } else {
          _endDate = picked;
        }
      });
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final success = await ref.read(leaveProvider.notifier).applyLeave(
          leaveType: _leaveType,
          startDate: AppDateUtils.toApiDate(_startDate),
          endDate: AppDateUtils.toApiDate(_endDate),
          isHalfDay: _isHalfDay,
          reason: _reasonController.text.trim(),
        );
    if (success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Leave application submitted'),
          backgroundColor: AppColors.success,
        ),
      );
      context.pop();
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
      appBar: AppBar(title: const Text('Apply for Leave')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Leave Type',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                value: _leaveType,
                decoration: const InputDecoration(),
                items: _leaveTypes
                    .map((t) => DropdownMenuItem(value: t.$1, child: Text(t.$2)))
                    .toList(),
                onChanged: (v) => setState(() => _leaveType = v!),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('From',
                            style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                        const SizedBox(height: 8),
                        InkWell(
                          onTap: () => _pickDate(isStart: true),
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 12, vertical: 14),
                            decoration: BoxDecoration(
                              color: AppColors.white,
                              border: Border.all(color: AppColors.cardBorder),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.calendar_today,
                                    size: 16, color: AppColors.primaryDark),
                                const SizedBox(width: 8),
                                Text(AppDateUtils.formatDate(
                                    _startDate.toIso8601String())),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('To',
                            style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                        const SizedBox(height: 8),
                        InkWell(
                          onTap: () => _pickDate(isStart: false),
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 12, vertical: 14),
                            decoration: BoxDecoration(
                              color: AppColors.white,
                              border: Border.all(color: AppColors.cardBorder),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.calendar_today,
                                    size: 16, color: AppColors.primaryDark),
                                const SizedBox(width: 8),
                                Text(AppDateUtils.formatDate(
                                    _endDate.toIso8601String())),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              CheckboxListTile(
                value: _isHalfDay,
                onChanged: (v) => setState(() => _isHalfDay = v!),
                title: const Text('Half Day'),
                contentPadding: EdgeInsets.zero,
                activeColor: AppColors.primaryDark,
              ),
              const SizedBox(height: 8),
              const Text('Reason (optional)',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
              const SizedBox(height: 8),
              TextFormField(
                controller: _reasonController,
                maxLines: 3,
                decoration: const InputDecoration(
                  hintText: 'Enter reason for leave...',
                ),
              ),
              const SizedBox(height: 24),
              ElevatedButton(
                onPressed: state.isLoading ? null : _submit,
                child: state.isLoading
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(
                            strokeWidth: 2, color: Colors.white),
                      )
                    : const Text('Submit Application'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
```

**Commit:** `git add lib/features/leaves/ && git commit -m "feat: add leave provider, leave balance/history screen, and apply leave screen"`

---

## Task 8: Salary Feature

**Files:**
- Create: `lib/features/salary/providers/salary_provider.dart`
- Create: `lib/features/salary/screens/salary_screen.dart`
- Create: `lib/features/salary/screens/slip_detail_screen.dart`

### `lib/features/salary/providers/salary_provider.dart`
```dart
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
```

### `lib/features/salary/screens/salary_screen.dart`
```dart
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
    const names = ['', 'January','February','March','April','May','June',
        'July','August','September','October','November','December'];
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
```

### `lib/features/salary/screens/slip_detail_screen.dart`
```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../providers/salary_provider.dart';
import '../../../core/constants/app_colors.dart';

class SlipDetailScreen extends ConsumerStatefulWidget {
  final int slipId;
  const SlipDetailScreen({super.key, required this.slipId});

  @override
  ConsumerState<SlipDetailScreen> createState() => _SlipDetailScreenState();
}

class _SlipDetailScreenState extends ConsumerState<SlipDetailScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(
        () => ref.read(salaryProvider.notifier).loadSlipDetail(widget.slipId));
  }

  String _fmt(dynamic amount) {
    final num v = (amount is num) ? amount : num.tryParse(amount.toString()) ?? 0;
    return '₹${NumberFormat('#,##,###.##').format(v)}';
  }

  String _monthName(dynamic month) {
    const names = ['','January','February','March','April','May','June',
        'July','August','September','October','November','December'];
    final m = (month is num) ? month.toInt() : int.tryParse(month.toString()) ?? 0;
    return m >= 1 && m <= 12 ? names[m] : '';
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(salaryProvider);
    final slip = state.selectedSlip;

    return Scaffold(
      appBar: AppBar(
        title: slip != null
            ? Text('${_monthName(slip['month'])} ${slip['year']}')
            : const Text('Salary Slip'),
      ),
      body: state.isLoading
          ? const Center(child: CircularProgressIndicator())
          : slip == null
              ? const Center(child: Text('Slip not found'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      // Header card
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [AppColors.primaryDark, AppColors.primaryLight],
                          ),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Column(
                          children: [
                            const Icon(Icons.business, color: Colors.white, size: 40),
                            const SizedBox(height: 8),
                            const Text('Kalina Engineering Pvt. Ltd.',
                                style: TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 16)),
                            const SizedBox(height: 16),
                            Text(slip['full_name'] ?? '',
                                style: const TextStyle(
                                    color: Colors.white, fontSize: 18,
                                    fontWeight: FontWeight.bold)),
                            Text(
                              '${slip['employee_code'] ?? ''} • ${slip['designation'] ?? ''}',
                              style: const TextStyle(color: Color(0xCCFFFFFF)),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Attendance summary
                      _SectionCard(
                        title: 'Attendance',
                        rows: [
                          ('Working Days', '${slip['total_days']} days'),
                          ('Days Present', '${slip['present_days']} days'),
                          ('Leave Days', '${slip['leave_days']} days'),
                          ('LWP Days', '${slip['lwp_days']} days'),
                        ],
                      ),
                      const SizedBox(height: 12),

                      // Earnings
                      _SectionCard(
                        title: 'Earnings',
                        rows: [
                          ('Gross Salary', _fmt(slip['gross_salary'])),
                        ],
                        highlightLast: false,
                      ),
                      const SizedBox(height: 12),

                      // Deductions
                      _SectionCard(
                        title: 'Deductions',
                        rows: [
                          ('LWP Deduction', _fmt(slip['deductions'])),
                        ],
                        highlightLast: false,
                      ),
                      const SizedBox(height: 12),

                      // Net salary
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: AppColors.success.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.success.withOpacity(0.3)),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('Net Salary',
                                style: TextStyle(
                                    fontWeight: FontWeight.bold, fontSize: 16)),
                            Text(
                              _fmt(slip['net_salary']),
                              style: const TextStyle(
                                  color: AppColors.success,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 22),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final String title;
  final List<(String, String)> rows;
  final bool highlightLast;

  const _SectionCard({
    required this.title,
    required this.rows,
    this.highlightLast = false,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title,
                style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    color: AppColors.textSecondary,
                    fontSize: 12)),
            const SizedBox(height: 12),
            ...rows.asMap().entries.map((entry) {
              final isLast = entry.key == rows.length - 1;
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 4),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(entry.value.$1,
                        style: TextStyle(
                            fontWeight: isLast && highlightLast
                                ? FontWeight.bold
                                : FontWeight.normal)),
                    Text(entry.value.$2,
                        style: TextStyle(
                            fontWeight: isLast && highlightLast
                                ? FontWeight.bold
                                : FontWeight.normal)),
                  ],
                ),
              );
            }),
          ],
        ),
      ),
    );
  }
}
```

**Commit:** `git add lib/features/salary/ && git commit -m "feat: add salary provider, slips list, and slip detail screen"`

---

## Task 9: Profile + Notifications + Holidays

**Files:**
- Create: `lib/features/profile/providers/profile_provider.dart`
- Create: `lib/features/profile/screens/profile_screen.dart`
- Create: `lib/features/notifications/providers/notification_provider.dart`
- Create: `lib/features/notifications/screens/notifications_screen.dart`
- Create: `lib/features/holidays/providers/holiday_provider.dart`
- Create: `lib/features/holidays/screens/holidays_screen.dart`

### `lib/features/profile/providers/profile_provider.dart`
```dart
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/constants/api_endpoints.dart';

class ProfileNotifier extends StateNotifier<AsyncValue<Map<String, dynamic>>> {
  final ApiClient _api;

  ProfileNotifier(this._api) : super(const AsyncValue.loading());

  Future<void> load() async {
    state = const AsyncValue.loading();
    try {
      final response = await _api.get(ApiEndpoints.profile);
      state = AsyncValue.data(response['data'] as Map<String, dynamic>);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }
}

final profileProvider =
    StateNotifierProvider<ProfileNotifier, AsyncValue<Map<String, dynamic>>>((ref) {
  return ProfileNotifier(ApiClient.instance);
});
```

### `lib/features/profile/screens/profile_screen.dart`
```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/profile_provider.dart';
import '../../auth/providers/auth_provider.dart';
import '../../../core/constants/app_colors.dart';

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(profileProvider.notifier).load());
  }

  @override
  Widget build(BuildContext context) {
    final profileAsync = ref.watch(profileProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('My Profile'),
        actions: [
          TextButton.icon(
            icon: const Icon(Icons.logout, color: Colors.white, size: 18),
            label: const Text('Logout', style: TextStyle(color: Colors.white)),
            onPressed: () async {
              final confirmed = await showDialog<bool>(
                context: context,
                builder: (context) => AlertDialog(
                  title: const Text('Log out?'),
                  content: const Text('Are you sure you want to log out?'),
                  actions: [
                    TextButton(
                      onPressed: () => Navigator.pop(context, false),
                      child: const Text('Cancel'),
                    ),
                    ElevatedButton(
                      onPressed: () => Navigator.pop(context, true),
                      child: const Text('Log out'),
                    ),
                  ],
                ),
              );
              if (confirmed == true && mounted) {
                await ref.read(authProvider.notifier).logout();
              }
            },
          ),
        ],
      ),
      body: profileAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(e.toString(), style: const TextStyle(color: AppColors.error)),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => ref.read(profileProvider.notifier).load(),
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
        data: (profile) => SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              // Avatar
              Container(
                width: 80,
                height: 80,
                decoration: BoxDecoration(
                  color: AppColors.primaryDark,
                  borderRadius: BorderRadius.circular(40),
                ),
                child: const Icon(Icons.person, color: Colors.white, size: 48),
              ),
              const SizedBox(height: 12),
              Text(
                profile['full_name'] ?? '',
                style: const TextStyle(
                    fontSize: 20, fontWeight: FontWeight.bold),
              ),
              Text(
                '${profile['employee_code'] ?? ''} • ${profile['branch_name'] ?? ''}',
                style: const TextStyle(color: AppColors.textSecondary),
              ),
              const SizedBox(height: 24),

              _InfoCard(title: 'Employment Details', fields: [
                ('Designation', profile['designation'] ?? '-'),
                ('Department', profile['department'] ?? '-'),
                ('Employment Type', profile['employment_type'] ?? '-'),
                ('Date of Joining', profile['date_of_joining'] ?? '-'),
              ]),
              const SizedBox(height: 12),

              _InfoCard(title: 'Contact Information', fields: [
                ('Email', profile['email'] ?? '-'),
                ('Phone', profile['phone'] ?? '-'),
              ]),
              const SizedBox(height: 12),

              _InfoCard(title: 'Bank Details', fields: [
                ('Bank Account', profile['bank_account'] ?? '-'),
                ('IFSC Code', profile['ifsc_code'] ?? '-'),
              ]),
              const SizedBox(height: 12),

              _InfoCard(title: 'KYC', fields: [
                ('PAN Number', profile['pan_number'] ?? '-'),
                ('Aadhar Number', profile['aadhar_number'] != null
                    ? 'XXXX XXXX ${profile['aadhar_number'].toString().replaceAll(' ', '').substring(profile['aadhar_number'].toString().length > 4 ? profile['aadhar_number'].toString().length - 4 : 0)}'
                    : '-'),
              ]),
            ],
          ),
        ),
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  final String title;
  final List<(String, String)> fields;

  const _InfoCard({required this.title, required this.fields});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title,
                style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    color: AppColors.textSecondary,
                    fontSize: 12)),
            const Divider(height: 16),
            ...fields.map((f) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      SizedBox(
                        width: 120,
                        child: Text(f.$1,
                            style: const TextStyle(
                                color: AppColors.textSecondary, fontSize: 13)),
                      ),
                      Expanded(
                        child: Text(f.$2,
                            style: const TextStyle(fontWeight: FontWeight.w500)),
                      ),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }
}
```

### `lib/features/notifications/providers/notification_provider.dart`
```dart
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../../core/constants/api_endpoints.dart';

class NotificationState {
  final bool isLoading;
  final List<Map<String, dynamic>> notifications;

  const NotificationState({
    this.isLoading = false,
    this.notifications = const [],
  });

  NotificationState copyWith({
    bool? isLoading,
    List<Map<String, dynamic>>? notifications,
  }) {
    return NotificationState(
      isLoading: isLoading ?? this.isLoading,
      notifications: notifications ?? this.notifications,
    );
  }
}

class NotificationNotifier extends StateNotifier<NotificationState> {
  final ApiClient _api;

  NotificationNotifier(this._api) : super(const NotificationState());

  Future<void> loadNotifications() async {
    state = state.copyWith(isLoading: true);
    try {
      final response = await _api.get(ApiEndpoints.notificationsList);
      state = state.copyWith(
        isLoading: false,
        notifications: List<Map<String, dynamic>>.from(response['data'] ?? []),
      );
    } catch (_) {
      state = state.copyWith(isLoading: false);
    }
  }

  Future<void> markRead(int notificationId) async {
    try {
      await _api.post(
        ApiEndpoints.notificationsMarkRead,
        data: {'notification_id': notificationId},
      );
      state = state.copyWith(
        notifications: state.notifications.map((n) {
          if (n['id'] == notificationId) {
            return {...n, 'is_read': 1};
          }
          return n;
        }).toList(),
      );
    } catch (_) {}
  }
}

final notificationProvider =
    StateNotifierProvider<NotificationNotifier, NotificationState>((ref) {
  return NotificationNotifier(ApiClient.instance);
});
```

### `lib/features/notifications/screens/notifications_screen.dart`
```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/notification_provider.dart';
import '../../../core/constants/app_colors.dart';

class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(notificationProvider.notifier).loadNotifications());
  }

  IconData _typeIcon(String? type) {
    switch (type) {
      case 'leave': return Icons.beach_access;
      case 'salary': return Icons.receipt_long;
      default: return Icons.notifications;
    }
  }

  Color _typeColor(String? type) {
    switch (type) {
      case 'leave': return AppColors.warning;
      case 'salary': return AppColors.success;
      default: return AppColors.primaryDark;
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(notificationProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Notifications')),
      body: state.isLoading
          ? const Center(child: CircularProgressIndicator())
          : state.notifications.isEmpty
              ? const Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.notifications_none, size: 64, color: AppColors.cardBorder),
                      SizedBox(height: 16),
                      Text('No notifications',
                          style: TextStyle(color: AppColors.textSecondary)),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: () =>
                      ref.read(notificationProvider.notifier).loadNotifications(),
                  child: ListView.builder(
                    itemCount: state.notifications.length,
                    itemBuilder: (context, i) {
                      final n = state.notifications[i];
                      final isRead = n['is_read'] == 1;
                      return InkWell(
                        onTap: () {
                          if (!isRead) {
                            ref
                                .read(notificationProvider.notifier)
                                .markRead(n['id']);
                          }
                        },
                        child: Container(
                          color: isRead ? null : AppColors.primaryDark.withOpacity(0.04),
                          padding: const EdgeInsets.symmetric(
                              horizontal: 16, vertical: 12),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Container(
                                width: 44,
                                height: 44,
                                decoration: BoxDecoration(
                                  color: _typeColor(n['type']).withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Icon(
                                  _typeIcon(n['type']),
                                  color: _typeColor(n['type']),
                                  size: 22,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      n['title'] ?? '',
                                      style: TextStyle(
                                        fontWeight: isRead
                                            ? FontWeight.normal
                                            : FontWeight.w600,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      n['body'] ?? '',
                                      style: const TextStyle(
                                          fontSize: 13,
                                          color: AppColors.textSecondary),
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ],
                                ),
                              ),
                              if (!isRead)
                                Container(
                                  width: 8,
                                  height: 8,
                                  margin: const EdgeInsets.only(top: 4),
                                  decoration: const BoxDecoration(
                                    color: AppColors.primaryDark,
                                    shape: BoxShape.circle,
                                  ),
                                ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ),
    );
  }
}
```

### `lib/features/holidays/providers/holiday_provider.dart`
```dart
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
```

### `lib/features/holidays/screens/holidays_screen.dart`
```dart
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
                      subtitle: Text(
                          AppDateUtils.formatDate(h['date']),
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
```

**Commit:** `git add lib/features/profile/ lib/features/notifications/ lib/features/holidays/ && git commit -m "feat: add profile, notifications, and holidays screens"`

---

## Task 10: main.dart + Routes + Shared Widgets

**Files:**
- Create: `lib/shared/widgets/app_drawer.dart`
- Create: `lib/shared/widgets/loading_overlay.dart`
- Create: `lib/shared/widgets/kalina_error_widget.dart`
- Create: `lib/app/routes.dart`
- Modify: `lib/main.dart`

### `lib/shared/widgets/loading_overlay.dart`
```dart
import 'package:flutter/material.dart';
import '../../core/constants/app_colors.dart';

class LoadingOverlay extends StatelessWidget {
  final bool isLoading;
  final Widget child;

  const LoadingOverlay({
    super.key,
    required this.isLoading,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        child,
        if (isLoading)
          Container(
            color: Colors.black26,
            child: const Center(
              child: Card(
                child: Padding(
                  padding: EdgeInsets.all(24),
                  child: CircularProgressIndicator(color: AppColors.primaryDark),
                ),
              ),
            ),
          ),
      ],
    );
  }
}
```

### `lib/shared/widgets/kalina_error_widget.dart`
```dart
import 'package:flutter/material.dart';
import '../../core/constants/app_colors.dart';

class KalinaErrorWidget extends StatelessWidget {
  final String message;
  final VoidCallback? onRetry;

  const KalinaErrorWidget({
    super.key,
    required this.message,
    this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 64, color: AppColors.error),
            const SizedBox(height: 16),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.textSecondary),
            ),
            if (onRetry != null) ...[
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh),
                label: const Text('Retry'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
```

### `lib/shared/widgets/app_drawer.dart`
```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/constants/app_colors.dart';
import '../../features/auth/providers/auth_provider.dart';

class AppDrawer extends ConsumerWidget {
  const AppDrawer({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);

    return Drawer(
      child: Column(
        children: [
          DrawerHeader(
            decoration: const BoxDecoration(color: AppColors.primaryDark),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                const CircleAvatar(
                  radius: 28,
                  backgroundColor: AppColors.primaryLight,
                  child: Icon(Icons.person, color: Colors.white, size: 32),
                ),
                const SizedBox(height: 12),
                Text(
                  authState.fullName ?? 'Employee',
                  style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 16),
                ),
                const Text('Kalina Engineering',
                    style: TextStyle(color: Color(0xCCFFFFFF), fontSize: 12)),
              ],
            ),
          ),
          _DrawerItem(
            icon: Icons.home_outlined,
            label: 'Home',
            onTap: () { Navigator.pop(context); context.go('/home'); },
          ),
          _DrawerItem(
            icon: Icons.calendar_month,
            label: 'Attendance History',
            onTap: () { Navigator.pop(context); context.go('/history'); },
          ),
          _DrawerItem(
            icon: Icons.beach_access,
            label: 'Leaves',
            onTap: () { Navigator.pop(context); context.go('/leaves'); },
          ),
          _DrawerItem(
            icon: Icons.receipt_long,
            label: 'Salary',
            onTap: () { Navigator.pop(context); context.go('/salary'); },
          ),
          _DrawerItem(
            icon: Icons.celebration,
            label: 'Holidays',
            onTap: () { Navigator.pop(context); context.go('/holidays'); },
          ),
          _DrawerItem(
            icon: Icons.person_outline,
            label: 'My Profile',
            onTap: () { Navigator.pop(context); context.go('/profile'); },
          ),
          const Spacer(),
          const Divider(),
          _DrawerItem(
            icon: Icons.logout,
            label: 'Logout',
            color: AppColors.error,
            onTap: () async {
              Navigator.pop(context);
              await ref.read(authProvider.notifier).logout();
            },
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }
}

class _DrawerItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color? color;

  const _DrawerItem({
    required this.icon,
    required this.label,
    required this.onTap,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(icon, color: color ?? AppColors.textDark),
      title: Text(label,
          style: TextStyle(color: color ?? AppColors.textDark)),
      onTap: onTap,
    );
  }
}
```

### `lib/app/routes.dart`
```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../features/auth/providers/auth_provider.dart';
import '../features/auth/screens/splash_screen.dart';
import '../features/auth/screens/login_screen.dart';
import '../features/attendance/screens/home_screen.dart';
import '../features/attendance/screens/history_screen.dart';
import '../features/leaves/screens/leave_screen.dart';
import '../features/leaves/screens/apply_leave_screen.dart';
import '../features/salary/screens/salary_screen.dart';
import '../features/salary/screens/slip_detail_screen.dart';
import '../features/profile/screens/profile_screen.dart';
import '../features/notifications/screens/notifications_screen.dart';
import '../features/holidays/screens/holidays_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authProvider);

  return GoRouter(
    initialLocation: '/',
    redirect: (context, state) {
      final isAuth = authState.status == AuthStatus.authenticated;
      final isLoggingIn = state.matchedLocation == '/login';
      final isSplash = state.matchedLocation == '/';

      if (isSplash) return null;
      if (!isAuth && !isLoggingIn) return '/login';
      if (isAuth && isLoggingIn) return '/home';
      return null;
    },
    routes: [
      GoRoute(path: '/', builder: (_, __) => const SplashScreen()),
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(path: '/home', builder: (_, __) => const _MainScaffold(child: HomeScreen())),
      GoRoute(path: '/history', builder: (_, __) => const HistoryScreen()),
      GoRoute(path: '/leaves', builder: (_, __) => const _MainScaffold(child: LeaveScreen())),
      GoRoute(path: '/apply-leave', builder: (_, __) => const ApplyLeaveScreen()),
      GoRoute(path: '/salary', builder: (_, __) => const _MainScaffold(child: SalaryScreen())),
      GoRoute(
        path: '/salary/detail',
        builder: (_, state) {
          final slipId = state.extra as int;
          return SlipDetailScreen(slipId: slipId);
        },
      ),
      GoRoute(path: '/profile', builder: (_, __) => const _MainScaffold(child: ProfileScreen())),
      GoRoute(path: '/notifications', builder: (_, __) => const NotificationsScreen()),
      GoRoute(path: '/holidays', builder: (_, __) => const _MainScaffold(child: HolidaysScreen())),
    ],
  );
});

class _MainScaffold extends StatefulWidget {
  final Widget child;
  const _MainScaffold({required this.child});

  @override
  State<_MainScaffold> createState() => _MainScaffoldState();
}

class _MainScaffoldState extends State<_MainScaffold> {
  int _selectedIndex = 0;

  static const _routes = ['/home', '/leaves', '/salary', '/holidays', '/profile'];

  @override
  Widget build(BuildContext context) {
    final location = GoRouterState.of(context).matchedLocation;
    final idx = _routes.indexOf(location);
    if (idx >= 0 && idx != _selectedIndex) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) setState(() => _selectedIndex = idx);
      });
    }

    return Scaffold(
      drawer: const AppDrawer(),
      body: widget.child,
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _selectedIndex < 0 ? 0 : _selectedIndex,
        onTap: (i) {
          setState(() => _selectedIndex = i);
          context.go(_routes[i]);
        },
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.home_outlined), activeIcon: Icon(Icons.home), label: 'Home'),
          BottomNavigationBarItem(icon: Icon(Icons.beach_access_outlined), activeIcon: Icon(Icons.beach_access), label: 'Leaves'),
          BottomNavigationBarItem(icon: Icon(Icons.receipt_long_outlined), activeIcon: Icon(Icons.receipt_long), label: 'Salary'),
          BottomNavigationBarItem(icon: Icon(Icons.celebration_outlined), activeIcon: Icon(Icons.celebration), label: 'Holidays'),
          BottomNavigationBarItem(icon: Icon(Icons.person_outline), activeIcon: Icon(Icons.person), label: 'Profile'),
        ],
      ),
    );
  }
}

// Need to import AppDrawer
import '../shared/widgets/app_drawer.dart';
```

### `lib/main.dart`
```dart
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'app/routes.dart';
import 'app/theme.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Hive.initFlutter();
  runApp(const ProviderScope(child: KalinaApp()));
}

class KalinaApp extends ConsumerWidget {
  const KalinaApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);

    return MaterialApp.router(
      title: 'Kalina Engineering',
      theme: AppTheme.light,
      routerConfig: router,
      debugShowCheckedModeBanner: false,
    );
  }
}
```

**Commit:** `git add lib/ && git commit -m "feat: complete Flutter app — main, routes, shared widgets, all features wired"`

---

## Self-Review Checklist

1. All routes connected — splash → login → home with bottom nav
2. JWT auto-attached via AuthInterceptor, 401 triggers silent refresh
3. Biometric required for clock in/out
4. Safe device / fake GPS check before clock in
5. IST time display via AppDateUtils
6. Riverpod StateNotifier for every feature
7. Error + success SnackBar feedback on all actions
8. Kalina color theme applied consistently
9. All 30 API endpoints covered by the service layer
