import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:uuid/uuid.dart';
import '../models/pending_sale.dart';
import '../models/product_cache.dart';

class SyncService {
  static final SyncService _instance = SyncService._internal();
  factory SyncService() => _instance;
  SyncService._internal();

  final Connectivity _connectivity = Connectivity();
  final Uuid _uuid = const Uuid();

  Box<ProductCache>? _productBox;
  Box<PendingSale>? _pendingSalesBox;

  final _connectivityController = StreamController<bool>.broadcast();
  Stream<bool> get connectivityStream => _connectivityController.stream;

  bool _isOnline = true;
  bool get isOnline => _isOnline;

  int _pendingCount = 0;
  int get pendingCount => _pendingCount;

  // Callback for syncing - will be set by OfflineApiClient
  Function()? _syncCallback;

  Future<void> init() async {
    await Hive.initFlutter();
    Hive.registerAdapter(ProductCacheAdapter());
    Hive.registerAdapter(PendingSaleAdapter());

    _productBox = await Hive.openBox<ProductCache>('products_cache');
    _pendingSalesBox = await Hive.openBox<PendingSale>('pending_sales');

    _updatePendingCount();

    _connectivity.onConnectivityChanged.listen((results) {
      final isConnected = results.isNotEmpty && 
          results.any((r) => r != ConnectivityResult.none);
      _isOnline = isConnected;
      _connectivityController.add(isConnected);

      if (isConnected && _syncCallback != null) {
        _syncCallback!();
      }
    });

    final results = await _connectivity.checkConnectivity();
    _isOnline = results.isNotEmpty && 
        results.any((r) => r != ConnectivityResult.none);
    _connectivityController.add(_isOnline);
  }

  void setSyncCallback(Function() callback) {
    _syncCallback = callback;
  }

  void _updatePendingCount() {
    if (_pendingSalesBox != null) {
      _pendingCount = _pendingSalesBox!.values
          .where((sale) => !sale.isSynced)
          .length;
    }
  }

  // Product Cache Methods
  Future<void> cacheProducts(List<Map<String, dynamic>> products) async {
    if (_productBox == null) return;

    await _productBox!.clear();
    for (final product in products) {
      final cache = ProductCache.fromJson(product);
      await _productBox!.put(cache.id, cache);
    }
  }

  List<ProductCache> getCachedProducts() {
    if (_productBox == null) return [];
    return _productBox!.values.toList()
      ..sort((a, b) => a.name.compareTo(b.name));
  }

  ProductCache? getCachedProduct(int productId) {
    return _productBox?.get(productId);
  }

  List<ProductCache> searchCachedProducts(String query) {
    if (_productBox == null || query.isEmpty) {
      return getCachedProducts();
    }
    final lowerQuery = query.toLowerCase();
    return _productBox!.values
        .where((p) =>
            p.name.toLowerCase().contains(lowerQuery) ||
            (p.sku?.toLowerCase().contains(lowerQuery) ?? false))
        .toList();
  }

  // Pending Sales Methods
  Future<void> addPendingSale(Map<String, dynamic> saleData) async {
    if (_pendingSalesBox == null) return;

    final pendingSale = PendingSale(
      localId: _uuid.v4(),
      items: List<Map<String, dynamic>>.from(saleData['items']),
      total: (saleData['total'] as num).toDouble(),
      customerName: saleData['customer_name'] as String? ?? 'عميل نقدي',
      paymentMethod: saleData['payment_method'] as String? ?? 'cash',
      discount: (saleData['discount'] as num?)?.toDouble() ?? 0.0,
      notes: saleData['notes'] as String?,
      createdAt: DateTime.now(),
    );

    await _pendingSalesBox!.put(pendingSale.localId, pendingSale);
    _updatePendingCount();
  }

  List<PendingSale> getPendingSales() {
    if (_pendingSalesBox == null) return [];
    return _pendingSalesBox!.values
        .where((s) => !s.isSynced)
        .toList()
      ..sort((a, b) => b.createdAt.compareTo(a.createdAt));
  }

  Future<void> markSaleSynced(String localId) async {
    final sale = _pendingSalesBox?.get(localId);
    if (sale != null) {
      sale.isSynced = true;
      await sale.save();
      _updatePendingCount();
    }
  }

  Future<void> setSaleError(String localId, String error) async {
    final sale = _pendingSalesBox?.get(localId);
    if (sale != null) {
      sale.errorMessage = error;
      await sale.save();
    }
  }

  Future<void> forceSync() async {
    _syncCallback?.call();
  }

  Future<void> clearSyncedSales() async {
    if (_pendingSalesBox == null) return;

    final keysToRemove = _pendingSalesBox!.keys
        .where((key) => _pendingSalesBox!.get(key)?.isSynced == true)
        .toList();

    for (final key in keysToRemove) {
      await _pendingSalesBox!.delete(key);
    }
    _updatePendingCount();
  }

  void dispose() {
    _connectivityController.close();
  }
}