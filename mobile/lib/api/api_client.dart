import 'package:dio/dio.dart';
import 'package:dio_cookie_manager/dio_cookie_manager.dart';
import 'package:cookie_jar/cookie_jar.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:path_provider/path_provider.dart';

/// عميل API مع حفظ الكوكيز (جلسة PHP بعد /api/login) — ضروري للـ APK وطلبات GET مثل /api/products
class ApiClient {
  final Dio _dio;

  ApiClient._(this._dio);

  static Future<ApiClient> create(String baseUrl) async {
    final dio = Dio(
      BaseOptions(
        baseUrl: baseUrl,
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 15),
        headers: const {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );

    if (!kIsWeb) {
      final dir = await getApplicationSupportDirectory();
      final jar = PersistCookieJar(
        storage: FileStorage('${dir.path}/.cookies/'),
      );
      dio.interceptors.add(CookieManager(jar));
    }

    return ApiClient._(dio);
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final response = await _dio.post<Map<String, dynamic>>(
      '/api/login',
      data: <String, dynamic>{
        'email': email,
        'password': password,
      },
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(response.data ?? <String, dynamic>{});
  }

  Future<Map<String, dynamic>> fetchProducts({
    int page = 1,
    int perPage = 20,
  }) async {
    final response = await _dio.get<Map<String, dynamic>>(
      '/api/products',
      queryParameters: <String, dynamic>{
        'page': page,
        'per_page': perPage,
      },
    );
    return Map<String, dynamic>.from(response.data ?? <String, dynamic>{});
  }

  Future<Map<String, dynamic>> findByBarcode(String sku) async {
    final response = await _dio.get<Map<String, dynamic>>(
      '/api/products/barcode',
      queryParameters: <String, dynamic>{'sku': sku},
    );
    return Map<String, dynamic>.from(response.data ?? <String, dynamic>{});
  }
}
