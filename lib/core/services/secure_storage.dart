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
