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
    final raw = response.data;
    if (raw is! Map<String, dynamic>) {
      final body = raw?.toString() ?? '';
      throw ApiException(
        'Server returned unexpected response: ${body.length > 200 ? body.substring(0, 200) : body}',
        statusCode: response.statusCode,
      );
    }
    if (raw['success'] == true) {
      return raw;
    }
    throw ApiException(
      raw['message'] ?? 'An error occurred',
      statusCode: response.statusCode,
    );
  }

  ApiException _handleDioError(DioException e) {
    if (e.response != null) {
      final data = e.response?.data;
      final String message;
      if (data is Map) {
        message = data['message'] ?? 'Request failed';
      } else {
        final body = data?.toString() ?? '';
        message = 'Server error (${e.response?.statusCode}): ${body.length > 200 ? body.substring(0, 200) : body}';
      }
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
