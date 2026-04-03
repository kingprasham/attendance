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
