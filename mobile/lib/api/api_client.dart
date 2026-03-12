import 'package:dio/dio.dart';

class ApiClient {
  final Dio _dio;

  ApiClient(String baseUrl)
      : _dio = Dio(
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
