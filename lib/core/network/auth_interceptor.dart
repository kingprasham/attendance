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
