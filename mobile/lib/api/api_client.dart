import 'package:dio/dio.dart';
import 'package:dio_cookie_manager/dio_cookie_manager.dart';
import 'package:cookie_jar/cookie_jar.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:path_provider/path_provider.dart';

import 'dio_stub.dart' if (dart.library.html) 'dio_web.dart' as dio_web;

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

    if (kIsWeb) {
      dio_web.attachWebCredentials(dio);
    } else {
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

  Future<Map<String, dynamic>> getMe() async {
    final response = await _dio.get<Map<String, dynamic>>('/api/me');
    return Map<String, dynamic>.from(response.data ?? <String, dynamic>{});
  }

  Future<Map<String, dynamic>> getDashboard() async {
    final response = await _dio.get<Map<String, dynamic>>('/api/dashboard');
    return Map<String, dynamic>.from(response.data ?? <String, dynamic>{});
  }

  Future<Map<String, dynamic>> getPosProducts({String search = ''}) async {
    final response = await _dio.get<Map<String, dynamic>>(
      '/api/pos/products',
      queryParameters: search.isEmpty ? null : <String, dynamic>{'q': search},
    );
    return Map<String, dynamic>.from(response.data ?? <String, dynamic>{});
  }

  Future<Map<String, dynamic>> postPosComplete({
    required List<Map<String, dynamic>> items,
    required String csrfToken,
    String customerName = 'عميل',
    String paymentMethod = 'cash',
  }) async {
    final response = await _dio.post<Map<String, dynamic>>(
      '/api/pos/complete',
      data: <String, dynamic>{
        'items': items,
        'csrf_token': csrfToken,
        'customer_name': customerName,
        'payment_method': paymentMethod,
      },
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(response.data ?? <String, dynamic>{});
  }

  Future<void> logout() async {
    await _dio.post<dynamic>('/api/logout');
  }

  // ── Sales ────────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getSales({int page = 1, int perPage = 25}) async {
    final r = await _dio.get<Map<String, dynamic>>(
      '/api/sales',
      queryParameters: {'page': page, 'per_page': perPage},
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<Map<String, dynamic>> cancelSale({
    required int id,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/sales/cancel',
      data: {'id': id, 'csrf_token': csrfToken},
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  // ── Categories ───────────────────────────────────────────────────────────

  Future<List<Map<String, dynamic>>> getCategories() async {
    final r = await _dio.get<Map<String, dynamic>>('/api/categories');
    final list = (r.data?['data'] as List?) ?? [];
    return list.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  Future<Map<String, dynamic>> createCategory({
    required String name,
    String? description,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/categories',
      data: {'name': name, 'description': description, 'csrf_token': csrfToken},
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<Map<String, dynamic>> updateCategory({
    required int id,
    required String name,
    String? description,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/categories/update',
      data: {'id': id, 'name': name, 'description': description, 'csrf_token': csrfToken},
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<Map<String, dynamic>> deleteCategory({
    required int id,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/categories/delete',
      data: {'id': id, 'csrf_token': csrfToken},
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  // ── Expenses ─────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getExpenses({int page = 1}) async {
    final r = await _dio.get<Map<String, dynamic>>(
      '/api/expenses/list',
      queryParameters: {'page': page},
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<Map<String, dynamic>> createExpense({
    required double amount,
    required String category,
    required String description,
    required String expenseDate,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/expenses',
      data: {
        'amount': amount,
        'category': category,
        'description': description,
        'expense_date': expenseDate,
        'csrf_token': csrfToken,
      },
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<Map<String, dynamic>> updateExpense({
    required int id,
    required double amount,
    required String category,
    required String description,
    required String expenseDate,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/expenses/update',
      data: {
        'id': id,
        'amount': amount,
        'category': category,
        'description': description,
        'expense_date': expenseDate,
        'csrf_token': csrfToken,
      },
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<Map<String, dynamic>> deleteExpense({
    required int id,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/expenses/delete',
      data: {'id': id, 'csrf_token': csrfToken},
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  // ── Purchases ────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getPurchases() async {
    final r = await _dio.get<Map<String, dynamic>>('/api/purchases/list');
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<Map<String, dynamic>> createPurchase({
    required List<Map<String, dynamic>> items,
    required String supplier,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/purchases',
      data: {'items': items, 'supplier': supplier, 'csrf_token': csrfToken},
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  // ── Reports ──────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getReports({String? from, String? to}) async {
    final r = await _dio.get<Map<String, dynamic>>(
      '/api/reports/data',
      queryParameters: {
        if (from != null) 'from': from,
        if (to != null) 'to': to,
      },
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  // ── Users ────────────────────────────────────────────────────────────────

  Future<List<Map<String, dynamic>>> getUsers() async {
    final r = await _dio.get<Map<String, dynamic>>('/api/users/list');
    final list = (r.data?['data'] as List?) ?? [];
    return list.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  Future<Map<String, dynamic>> createUser({
    required String username,
    required String email,
    required String password,
    required String role,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/users',
      data: {
        'username': username,
        'email': email,
        'password': password,
        'role': role,
        'status': 'active',
        'csrf_token': csrfToken,
      },
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<Map<String, dynamic>> deleteUser({
    required int id,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/users/delete',
      data: {'id': id, 'csrf_token': csrfToken},
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  // ── Activity Log ─────────────────────────────────────────────────────────

  Future<List<Map<String, dynamic>>> getActivityLog({int limit = 100}) async {
    final r = await _dio.get<Map<String, dynamic>>(
      '/api/activity-log/list',
      queryParameters: {'limit': limit},
    );
    final list = (r.data?['data'] as List?) ?? [];
    return list.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  // ── Settings ─────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getSettings() async {
    final r = await _dio.get<Map<String, dynamic>>('/api/settings/data');
    return Map<String, dynamic>.from(r.data?['data'] as Map? ?? {});
  }

  Future<Map<String, dynamic>> saveSettings({
    required String appName,
    required String currencySymbol,
    required String timezone,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/settings',
      data: {
        'app_name': appName,
        'currency_symbol': currencySymbol,
        'timezone': timezone,
        'csrf_token': csrfToken,
      },
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  // ── Products CRUD ────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> createProduct({
    required String name,
    required double price,
    required int quantity,
    String? sku,
    double? cost,
    int? categoryId,
    int? lowStockThreshold,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/products',
      data: {
        'name': name,
        'price': price,
        'quantity': quantity,
        if (sku != null) 'sku': sku,
        if (cost != null) 'cost': cost,
        if (categoryId != null) 'category_id': categoryId,
        'low_stock_threshold': lowStockThreshold ?? 5,
        'csrf_token': csrfToken,
      },
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<Map<String, dynamic>> updateProduct({
    required int id,
    required String name,
    required double price,
    required int quantity,
    String? sku,
    double? cost,
    int? categoryId,
    int? lowStockThreshold,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/products/update',
      data: {
        'id': id,
        'name': name,
        'price': price,
        'quantity': quantity,
        if (sku != null) 'sku': sku,
        if (cost != null) 'cost': cost,
        if (categoryId != null) 'category_id': categoryId,
        'low_stock_threshold': lowStockThreshold ?? 5,
        'csrf_token': csrfToken,
      },
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<Map<String, dynamic>> deleteProduct({
    required int id,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/products/delete',
      data: {'id': id, 'csrf_token': csrfToken},
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }
}
