import 'package:dio/dio.dart';
import '../models/product_cache.dart';
import '../services/sync_service.dart';

class OfflineApiClient {
  late final Dio _dio;
  late final SyncService _syncService;
  String _baseUrl = '';
  void Function()? _onUnauthorized;

  OfflineApiClient._(this._dio, this._syncService);

  static Future<OfflineApiClient> create(String baseUrl) async {
    final dio = Dio(BaseOptions(
      baseUrl: baseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 15),
      headers: const {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ));

    final syncService = SyncService();
    await syncService.init();

    final client = OfflineApiClient._(dio, syncService);
    client._baseUrl = baseUrl;

    // Set up sync callback
    syncService.setSyncCallback(() {
      client.syncPendingSales();
    });

    return client;
  }

  void setOnUnauthorized(void Function() callback) {
    _onUnauthorized = callback;
  }

  // Network Status
  bool get isOnline => _syncService.isOnline;
  Stream<bool> get connectivityStream => _syncService.connectivityStream;
  int get pendingSyncCount => _syncService.pendingCount;

  // Product Methods - Offline First
  Future<Map<String, dynamic>> fetchProducts({int page = 1, int perPage = 20}) async {
    try {
      if (_syncService.isOnline) {
        final response = await _dio.get<Map<String, dynamic>>(
          '/api/products',
          queryParameters: {'page': page, 'per_page': perPage},
        );
        
        final data = response.data?['data'] ?? [];
        await _syncService.cacheProducts(List<Map<String, dynamic>>.from(data));
        
        return response.data?['data'] != null 
            ? Map<String, dynamic>.from(response.data!['data'])
            : {};
      }
    } catch (e) {
      // Fall through to cache
    }

    // Offline - return cached products
    final cachedProducts = _syncService.getCachedProducts();
    return {
      'data': cachedProducts.map((p) => p.toJson()).toList(),
      'total': cachedProducts.length,
      'page': 1,
      'pages': 1,
    };
  }

  List<ProductCache> getCachedProducts() {
    return _syncService.getCachedProducts();
  }

  List<ProductCache> searchCachedProducts(String query) {
    return _syncService.searchCachedProducts(query);
  }

  ProductCache? getCachedProduct(int productId) {
    return _syncService.getCachedProduct(productId);
  }

  // Sale Methods - Offline Aware
  Future<Map<String, dynamic>> completeSale({
    required List<Map<String, dynamic>> items,
    required String csrfToken,
    String customerName = 'عميل',
    String paymentMethod = 'cash',
  }) async {
    final total = items.fold<double>(0, (sum, item) => 
        sum + ((item['quantity'] as int) * (item['unit_price'] as double)));

    final saleData = {
      'items': items,
      'customer_name': customerName,
      'payment_method': paymentMethod,
      'discount': 0.0,
      'notes': '',
      'total': total,
    };

    if (_syncService.isOnline) {
      try {
        final response = await _dio.post<Map<String, dynamic>>(
          '/api/pos/complete',
          data: {
            'items': items,
            'csrf_token': csrfToken,
            'customer_name': customerName,
            'payment_method': paymentMethod,
          },
          options: Options(contentType: Headers.jsonContentType),
        );
        
        return Map<String, dynamic>.from(response.data ?? {});
      } catch (e) {
        // Network error - save to pending queue
        await _syncService.addPendingSale(saleData);
        return {
          'success': true,
          'offline': true,
          'local_id': DateTime.now().millisecondsSinceEpoch,
          'message': 'تم حفظ البيع محلياً. سيتم مزامنته عند الاتصال.',
        };
      }
    } else {
      // Offline - save to pending queue
      await _syncService.addPendingSale(saleData);
      return {
        'success': true,
        'offline': true,
        'local_id': DateTime.now().millisecondsSinceEpoch,
        'message': 'تم حفظ البيع محلياً. سيتم مزامنته عند الاتصال.',
      };
    }
  }

  // Sync Methods
  Future<void> syncPendingSales() async {
    final pending = _syncService.getPendingSales();
    if (pending.isEmpty) return;

    for (final sale in pending) {
      try {
        final csrfToken = await getCsrfToken();
        
        await _dio.post<Map<String, dynamic>>(
          '/api/pos/complete',
          data: {
            'items': sale.items,
            'csrf_token': csrfToken,
            'customer_name': sale.customerName,
            'payment_method': sale.paymentMethod,
          },
          options: Options(contentType: Headers.jsonContentType),
        );

        await _syncService.markSaleSynced(sale.localId);
      } catch (e) {
        await _syncService.setSaleError(sale.localId, e.toString());
      }
    }
  }

  Future<void> forceSync() async {
    await syncPendingSales();
  }

  List<dynamic> getPendingSales() {
    return _syncService.getPendingSales();
  }

  // User/Me
  Future<Map<String, dynamic>> getMe() async {
    final response = await _dio.get<Map<String, dynamic>>('/api/me');
    return Map<String, dynamic>.from(response.data?['user'] ?? {});
  }

  // Get CSRF Token
  Future<String> getCsrfToken() async {
    try {
      final me = await getMe();
      return me['csrf_token'] ?? '';
    } catch (e) {
      return '';
    }
  }

  // Login
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

  Future<void> logout() async {
    await _dio.post<dynamic>('/api/logout');
  }
}