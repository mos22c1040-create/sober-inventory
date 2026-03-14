import 'package:dio/dio.dart';
import 'package:dio_cookie_manager/dio_cookie_manager.dart';
import 'package:cookie_jar/cookie_jar.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:path_provider/path_provider.dart';

import 'dio_stub.dart' if (dart.library.html) 'dio_web.dart' as dio_web;

/// عميل API مع حفظ الكوكيز (جلسة PHP بعد /api/login) — ضروري للـ APK وطلبات GET مثل /api/products
class ApiClient {
  final Dio _dio;
  void Function()? _onUnauthorized;

  ApiClient._(this._dio) : _onUnauthorized = null;

  /// عند استلام 401 من أي مسار (ما عدا /api/login) يتم استدعاء هذا الـ callback — عادة إعادة توجيه لشاشة تسجيل الدخول
  void setOnUnauthorized(void Function() callback) {
    _onUnauthorized = callback;
  }

  // ── Envelope helpers ────────────────────────────────────────────────────
  // Controller::jsonResponse wraps every response in:
  //   {"success": true, "status": 200, "data": <your_data>, "error": null}
  // _d() unwraps one level: returns the inner "data" as a Map.
  // _lst() returns the inner list (handles both direct list and {"data":[...]} nesting).

  static Map<String, dynamic> _d(dynamic raw) {
    if (raw is Map) {
      final d = raw['data'];
      if (d is Map) return Map<String, dynamic>.from(d);
    }
    return <String, dynamic>{};
  }

  static List<Map<String, dynamic>> _lst(dynamic raw, [String key = 'data']) {
    if (raw is Map) {
      final d = raw['data'];
      if (d is Map) {
        final list = d[key];
        if (list is List) {
          return list.map((e) => Map<String, dynamic>.from(e as Map)).toList();
        }
      }
      if (d is List) {
        return d.map((e) => Map<String, dynamic>.from(e as Map)).toList();
      }
    }
    return <Map<String, dynamic>>[];
  }

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

    final client = ApiClient._(dio);

    dio.interceptors.add(
      InterceptorsWrapper(
        onError: (error, handler) {
          final status = error.response?.statusCode;
          final path = error.requestOptions.path;
          if (status == 401 && path != null && !path.contains('login')) {
            client._onUnauthorized?.call();
          }
          handler.next(error);
        },
      ),
    );

    return client;
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
    return _d(response.data);
  }

  Future<Map<String, dynamic>> findByBarcode(String sku) async {
    final response = await _dio.get<Map<String, dynamic>>(
      '/api/products/barcode',
      queryParameters: <String, dynamic>{'sku': sku},
    );
    return _d(response.data);
  }

  /// منتجات منخفضة المخزون (للإشعارات والتقارير)
  Future<List<Map<String, dynamic>>> getLowStockProducts({int limit = 30}) async {
    final response = await _dio.get<Map<String, dynamic>>(
      '/api/products/low-stock',
      queryParameters: <String, dynamic>{'limit': limit},
    );
    return _lst(response.data);
  }

  Future<Map<String, dynamic>> getMe() async {
    final response = await _dio.get<Map<String, dynamic>>('/api/me');
    return _d(response.data);
  }

  Future<Map<String, dynamic>> getDashboard() async {
    final response = await _dio.get<Map<String, dynamic>>('/api/dashboard');
    return _d(response.data);
  }

  Future<Map<String, dynamic>> getPosProducts({String search = ''}) async {
    final response = await _dio.get<Map<String, dynamic>>(
      '/api/pos/products',
      queryParameters: search.isEmpty ? null : <String, dynamic>{'q': search},
    );
    return _d(response.data);
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

  /// POST /api/profile/password — تغيير كلمة مرور المستخدم الحالي
  Future<Map<String, dynamic>> updateProfilePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/profile/password',
      data: {
        'current_password': currentPassword,
        'new_password': newPassword,
        'confirm_password': confirmPassword,
        'csrf_token': csrfToken,
      },
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  /// GET /reports/export/sales — تصدير المبيعات CSV (bytes، يتطلب صلاحية مدير)
  Future<List<int>> getReportExportSalesBytes({
    required String from,
    required String to,
  }) async {
    final r = await _dio.get<List<int>>(
      '/reports/export/sales',
      queryParameters: {'from': from, 'to': to},
      options: Options(responseType: ResponseType.bytes),
    );
    return r.data ?? [];
  }

  /// GET /reports/export/products — تصدير المنتجات الأكثر مبيعاً CSV (bytes)
  Future<List<int>> getReportExportProductsBytes({
    required String from,
    required String to,
  }) async {
    final r = await _dio.get<List<int>>(
      '/reports/export/products',
      queryParameters: {'from': from, 'to': to},
      options: Options(responseType: ResponseType.bytes),
    );
    return r.data ?? [];
  }

  // ── Sales ────────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getSales({int page = 1, int perPage = 25}) async {
    final r = await _dio.get<Map<String, dynamic>>(
      '/api/sales',
      queryParameters: {'page': page, 'per_page': perPage},
    );
    return _d(r.data);
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

  Future<Map<String, dynamic>> getSaleDetails(int saleId) async {
    final r = await _dio.get<Map<String, dynamic>>(
      '/api/sales/details',
      queryParameters: {'id': saleId},
    );
    return _d(r.data);
  }

  // ── Returns ────────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> submitReturn(Map<String, dynamic> payload) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/returns',
      data: payload,
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  // ── Categories ───────────────────────────────────────────────────────────

  Future<List<Map<String, dynamic>>> getCategories() async {
    final r = await _dio.get<Map<String, dynamic>>('/api/categories');
    return _lst(r.data);
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

  // ── Types (الأنواع) ───────────────────────────────────────────────────────

  Future<List<Map<String, dynamic>>> getTypes() async {
    final r = await _dio.get<Map<String, dynamic>>('/api/types');
    return _lst(r.data);
  }

  Future<Map<String, dynamic>> createType({
    required String name,
    String? description,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/types',
      data: {'name': name, 'description': description, 'csrf_token': csrfToken},
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<Map<String, dynamic>> updateType({
    required int id,
    required String name,
    String? description,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/types/update',
      data: {'id': id, 'name': name, 'description': description, 'csrf_token': csrfToken},
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<Map<String, dynamic>> deleteType({
    required int id,
    required String csrfToken,
  }) async {
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/types/delete',
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
    return _d(r.data);
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
    return _d(r.data);
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

  Future<Map<String, dynamic>> submitPurchase({
    required List<Map<String, dynamic>> items,
    String supplier = '',
  }) async {
    final csrfToken = await getCsrfToken();
    final r = await _dio.post<Map<String, dynamic>>(
      '/api/purchases',
      data: {
        'items': items,
        'supplier': supplier,
        'csrf_token': csrfToken,
      },
      options: Options(contentType: Headers.jsonContentType),
    );
    return Map<String, dynamic>.from(r.data ?? {});
  }

  Future<String> getCsrfToken() async {
    try {
      final me = await getMe();
      return me['csrf_token'] ?? '';
    } catch (e) {
      return '';
    }
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
    return _d(r.data);
  }

  Future<Map<String, dynamic>> fetchPnL({String? startDate, String? endDate}) async {
    final r = await _dio.get<Map<String, dynamic>>(
      '/api/reports/pnl',
      queryParameters: {
        if (startDate != null) 'start_date': startDate,
        if (endDate != null) 'end_date': endDate,
      },
    );
    return _d(r.data);
  }

  // ── Users ────────────────────────────────────────────────────────────────

  Future<List<Map<String, dynamic>>> getUsers() async {
    final r = await _dio.get<Map<String, dynamic>>('/api/users/list');
    return _lst(r.data);
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
    return _lst(r.data);
  }

  // ── Settings ─────────────────────────────────────────────────────────────

  Future<Map<String, dynamic>> getSettings() async {
    final r = await _dio.get<Map<String, dynamic>>('/api/settings/data');
    return _d(r.data);
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
    int? typeId,
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
        if (typeId != null) 'type_id': typeId,
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
    int? typeId,
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
        if (typeId != null) 'type_id': typeId,
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
