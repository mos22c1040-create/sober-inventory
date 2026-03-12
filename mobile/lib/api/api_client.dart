import 'package:dio/dio.dart';

class ApiClient {
  final Dio _dio;

  ApiClient(String baseUrl)
      : _dio = Dio(
          BaseOptions(
            baseUrl: baseUrl,
            connectTimeout: const Duration(seconds: 10),
            receiveTimeout: const Duration(seconds: 10),
            headers: const {
              'Accept': 'application/json',
            },
          ),
        );

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final response = await _dio.post(
      '/api/login',
      data: <String, dynamic>{
        'email': email,
        'password': password,
      },
    );
    return Map<String, dynamic>.from(response.data as Map);
  }

  Future<Map<String, dynamic>> fetchProducts({
    int page = 1,
    int perPage = 20,
  }) async {
    final response = await _dio.get(
      '/api/products',
      queryParameters: <String, dynamic>{
        'page': page,
        'per_page': perPage,
      },
    );
    return Map<String, dynamic>.from(response.data as Map);
  }

  Future<Map<String, dynamic>> findByBarcode(String sku) async {
    final response = await _dio.get(
      '/api/products/barcode',
      queryParameters: <String, dynamic>{'sku': sku},
    );
    return Map<String, dynamic>.from(response.data as Map);
  }
}

