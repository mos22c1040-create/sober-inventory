import 'package:dio/dio.dart';
import 'package:flutter/material.dart';

import '../api/api_client.dart';
import '../models/product.dart';
import '../theme/app_theme.dart';
import 'barcode_scanner_screen.dart';
import 'product_form_screen.dart';

class ProductsScreen extends StatefulWidget {
  const ProductsScreen({super.key, required this.api, this.editMode = false});

  final ApiClient api;
  final bool editMode;

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  bool _loading = true;
  List<Product> _products = [];
  String? _error;
  final TextEditingController _searchCtrl = TextEditingController();
  bool _searching = false;
  String _csrfToken = '';
  String _stockFilter = 'all';

  List<Product> get _displayProducts {
    if (_stockFilter == 'all') return _products;
    return _products.where((p) {
      if (_stockFilter == 'out') return p.quantity <= 0;
      if (_stockFilter == 'low') return p.quantity > 0 && p.quantity <= 5;
      if (_stockFilter == 'available') return p.quantity > 5;
      return true;
    }).toList();
  }

  @override
  void initState() {
    super.initState();
    _loadProducts();
    if (widget.editMode) _loadCsrf();
  }

  Future<void> _loadCsrf() async {
    try {
      final r = await widget.api.getMe();
      if (mounted) setState(() => _csrfToken = (r['csrf_token'] ?? '').toString());
    } catch (_) {}
  }

  Future<void> _deleteProduct(int id) async {
    if (_csrfToken.isEmpty) return;
    try {
      final r = await widget.api.deleteProduct(id: id, csrfToken: _csrfToken);
      if (r['success'] == true) {
        _showSnackBar('تم الحذف');
        _loadProducts();
      } else {
        _showSnackBar((r['error'] ?? 'فشل الحذف').toString(), isError: true);
      }
    } catch (_) {
      _showSnackBar('تعذر الاتصال', isError: true);
    }
  }

  void _showProductForm([Product? product]) {
    Navigator.of(context).push(MaterialPageRoute<void>(
      builder: (_) => ProductFormScreen(
        api: widget.api,
        csrfToken: _csrfToken,
        product: product,
        onSaved: () { Navigator.pop(context); _loadProducts(); },
      ),
    ));
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadProducts() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await widget.api.fetchProducts(page: 1, perPage: 50);
      final List list = (res['data'] ?? []) as List;
      if (mounted) {
        setState(() {
          _products = list
              .map((e) => Product.fromJson(Map<String, dynamic>.from(e as Map)))
              .toList();
        });
      }
    } on DioException catch (e) {
      if (!mounted) return;
      final code = e.response?.statusCode;
      if (code == 401) {
        setState(() => _error = 'انتهت الجلسة. سجّل الدخول من جديد.');
      } else if (e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError) {
        setState(() => _error = 'تعذر الاتصال بالخادم. تحقق من الإنترنت.');
      } else {
        setState(() => _error = 'فشل تحميل المنتجات.');
      }
    } catch (_) {
      if (mounted) setState(() => _error = 'فشل تحميل المنتجات. تحقق من الاتصال.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _setStockFilter(String filter) {
    setState(() => _stockFilter = filter);
  }

  Future<void> _openBarcodeScanner() async {
    final code = await Navigator.of(context).push<String>(
      MaterialPageRoute<String>(
        builder: (_) => const BarcodeScannerScreen(title: 'مسح باركود المنتج'),
      ),
    );
    if (code != null && code.isNotEmpty && mounted) {
      _searchCtrl.text = code;
      _searchByBarcode();
    }
  }

  Future<void> _searchByBarcode() async {
    final sku = _searchCtrl.text.trim();
    if (sku.isEmpty) return;
    setState(() => _searching = true);
    try {
      final res = await widget.api.findByBarcode(sku);
      if (!mounted) return;
      if (res['success'] == true && res['product'] != null) {
        final product =
            Product.fromJson(Map<String, dynamic>.from(res['product'] as Map));
        _showProductSheet(product);
      } else {
        _showSnackBar(
            (res['error'] ?? 'لم يُعثر على المنتج').toString(), isError: true);
      }
    } catch (_) {
      if (mounted) _showSnackBar('تعذر الاتصال بالخادم', isError: true);
    } finally {
      if (mounted) setState(() => _searching = false);
    }
  }

  void _showSnackBar(String msg, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: isError ? AppColors.error : AppColors.success,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  void _showProductSheet(Product p) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _ProductDetailSheet(product: p),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        body: SafeArea(
          child: Column(
            children: [
              _buildHeader(),
              _buildSearchBar(),
              if (_error != null) _buildErrorBanner(),
              Expanded(
                child: _loading
                    ? _buildSkeletons()
                    : RefreshIndicator(
                        color: AppColors.primary,
                        onRefresh: _loadProducts,
                        child: _displayProducts.isEmpty
                            ? _buildEmpty()
                            : _buildList(),
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'المنتجات',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                    color: AppColors.textPrimary,
                    letterSpacing: -0.5,
                  ),
                ),
                Text(
                  '${_displayProducts.length} منتج',
                  style: const TextStyle(
                    fontSize: 13,
                    color: AppColors.textSecondary,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
          Row(
            children: [
              if (widget.editMode)
                Container(
                  margin: const EdgeInsets.only(left: 8),
                  decoration: BoxDecoration(
                    gradient: AppColors.primaryGradient,
                    borderRadius: BorderRadius.circular(14),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.primary.withValues(alpha: 0.4),
                        blurRadius: 12,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Material(
                    color: Colors.transparent,
                    child: InkWell(
                      borderRadius: BorderRadius.circular(14),
                      onTap: () => _showProductForm(),
                      child: const Padding(
                        padding: EdgeInsets.all(10),
                        child: Icon(Icons.add_rounded, color: Colors.white, size: 24),
                      ),
                    ),
                  ),
                ),
              Container(
                decoration: BoxDecoration(
                  color: AppColors.card,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.05),
                      blurRadius: 8,
                    ),
                  ],
                ),
                child: IconButton(
                  icon: const Icon(Icons.refresh_rounded, color: AppColors.primary),
                  onPressed: _loadProducts,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSearchBar() {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 12),
          child: Container(
            decoration: BoxDecoration(
              color: AppColors.card,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.05),
                  blurRadius: 10,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.qr_code_scanner_rounded, color: AppColors.primary, size: 26),
                  onPressed: _openBarcodeScanner,
                  tooltip: 'مسح الباركود',
                ),
                Expanded(
                  child: TextField(
                    controller: _searchCtrl,
                    decoration: InputDecoration(
                      hintText: 'بحث بالباركود أو الرمز SKU...',
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 15),
                      filled: false,
                      suffixIcon: _searching
                          ? const Padding(
                              padding: EdgeInsets.all(12),
                              child: SizedBox(
                                width: 20,
                                height: 20,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: AppColors.primary,
                                ),
                              ),
                            )
                          : IconButton(
                              icon: const Icon(Icons.search_rounded, color: AppColors.primary),
                              onPressed: _searchByBarcode,
                            ),
                    ),
                    onSubmitted: (_) => _searchByBarcode(),
                  ),
                ),
              ],
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
          child: Row(
            children: [
              _FilterChip(
                label: 'الكل',
                isSelected: _stockFilter == 'all',
                onTap: () => _setStockFilter('all'),
              ),
              const SizedBox(width: 8),
              _FilterChip(
                label: 'متوفر',
                isSelected: _stockFilter == 'available',
                color: AppColors.success,
                onTap: () => _setStockFilter('available'),
              ),
              const SizedBox(width: 8),
              _FilterChip(
                label: 'منخفض',
                isSelected: _stockFilter == 'low',
                color: AppColors.warning,
                onTap: () => _setStockFilter('low'),
              ),
              const SizedBox(width: 8),
              _FilterChip(
                label: 'نفد',
                isSelected: _stockFilter == 'out',
                color: AppColors.error,
                onTap: () => _setStockFilter('out'),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildErrorBanner() {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 0, 20, 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.errorBg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.error.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Icon(Icons.error_outline_rounded, color: AppColors.error, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(_error!,
                style: const TextStyle(color: AppColors.error, fontSize: 13)),
          ),
          TextButton(
            onPressed: _loadProducts,
            child: const Text('إعادة'),
          ),
        ],
      ),
    );
  }

  Widget _buildList() {
    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
      itemCount: _displayProducts.length,
      itemBuilder: (context, i) {
        final p = _displayProducts[i];
        final isOutOfStock = p.quantity <= 0;
        final isLow = p.quantity > 0 && p.quantity <= 5;
        return _ProductCard(
          product: p,
          isOutOfStock: isOutOfStock,
          isLow: isLow,
          onTap: () => _showProductSheet(p),
          onEdit: widget.editMode ? () => _showProductForm(p) : null,
          onDelete: widget.editMode ? () => _deleteProduct(p.id) : null,
        );
      },
    );
  }

  Widget _buildEmpty() {
    final isFiltered = _stockFilter != 'all';
    return EmptyState(
      icon: isFiltered ? Icons.filter_list_off_rounded : Icons.inventory_2_rounded,
      title: isFiltered ? 'لا توجد نتائج' : 'لا توجد منتجات',
      subtitle: isFiltered 
          ? 'جرب فلتر آخر'
          : 'أضف منتجات من لوحة التحكم على الموقع',
      action: isFiltered
          ? TextButton(
              onPressed: () => _setStockFilter('all'),
              child: const Text('إزالة الفلتر'),
            )
          : null,
    );
  }

  Widget _buildSkeletons() {
    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
      itemCount: 8,
      itemBuilder: (_, i) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: AppCards.modern(
          margin: EdgeInsets.zero,
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              const SkeletonLoader(width: 52, height: 52, borderRadius: 14),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SkeletonLoader(width: 120, height: 16),
                    const SizedBox(height: 8),
                    SkeletonLoader(width: 80, height: 12),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  SkeletonLoader(width: 50, height: 18),
                  const SizedBox(height: 8),
                  SkeletonLoader(width: 40, height: 20, borderRadius: 10),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProductCard extends StatelessWidget {
  const _ProductCard({
    required this.product,
    required this.isOutOfStock,
    required this.isLow,
    required this.onTap,
    this.onEdit,
    this.onDelete,
  });

  final Product product;
  final bool isOutOfStock;
  final bool isLow;
  final VoidCallback onTap;
  final VoidCallback? onEdit;
  final VoidCallback? onDelete;

  @override
  Widget build(BuildContext context) {
    Color badgeColor = AppColors.success;
    Color badgeBg = AppColors.successBg;
    String badgeText = 'متوفر';

    if (isOutOfStock) {
      badgeColor = AppColors.error;
      badgeBg = AppColors.errorBg;
      badgeText = 'نفد';
    } else if (isLow) {
      badgeColor = AppColors.warning;
      badgeBg = AppColors.warningBg;
      badgeText = 'منخفض';
    }

    final firstLetter = product.name.isNotEmpty ? product.name[0].toUpperCase() : '?';

    return AppCards.modern(
      onTap: onTap,
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  AppColors.primary.withValues(alpha: 0.15),
                  AppColors.primaryLight.withValues(alpha: 0.08),
                ],
              ),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Center(
              child: Text(
                firstLetter,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: AppColors.primary,
                ),
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  product.name,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                    color: AppColors.textPrimary,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Text(
                      'الكمية: ${product.quantity}',
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.textSecondary,
                      ),
                    ),
                    if (product.sku != null) ...[
                      const Text(' · ',
                          style: TextStyle(color: AppColors.textHint)),
                      Text(
                        product.sku!,
                        style: const TextStyle(
                          fontSize: 12,
                          color: AppColors.textHint,
                        ),
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                product.price.toStringAsFixed(0),
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  color: AppColors.primary,
                  letterSpacing: -0.3,
                ),
              ),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: badgeBg,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  badgeText,
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: badgeColor,
                  ),
                ),
              ),
              if (onEdit != null || onDelete != null) ...[
                const SizedBox(height: 6),
                Row(
                  children: [
                    if (onEdit != null)
                      GestureDetector(
                        onTap: onEdit,
                        child: Container(
                          padding: const EdgeInsets.all(4),
                          decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(6)),
                          child: const Icon(Icons.edit_rounded, color: AppColors.primary, size: 14),
                        ),
                      ),
                    if (onEdit != null && onDelete != null) const SizedBox(width: 6),
                    if (onDelete != null)
                      GestureDetector(
                        onTap: onDelete,
                        child: Container(
                          padding: const EdgeInsets.all(4),
                          decoration: BoxDecoration(color: AppColors.errorBg, borderRadius: BorderRadius.circular(6)),
                          child: const Icon(Icons.delete_rounded, color: AppColors.error, size: 14),
                        ),
                      ),
                  ],
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _ProductDetailSheet extends StatelessWidget {
  const _ProductDetailSheet({required this.product});

  final Product product;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(28),
      ),
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: AppColors.outline,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 24),
            Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [AppColors.primary.withValues(alpha: 0.15), AppColors.primaryLight.withValues(alpha: 0.08)],
                ),
                borderRadius: BorderRadius.circular(20),
              ),
              child: const Icon(Icons.inventory_2_rounded, color: AppColors.primary, size: 36),
            ),
            const SizedBox(height: 16),
            Text(
              product.name,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
                color: AppColors.textPrimary,
                letterSpacing: -0.3,
              ),
            ),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                color: AppColors.bg,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                children: [
                  _detailRow('السعر', '${product.price.toStringAsFixed(0)} د.ع', Icons.attach_money_rounded, AppColors.primary),
                  _detailRow('الكمية', '${product.quantity}', Icons.inventory_rounded, AppColors.success),
                  if (product.sku != null)
                    _detailRow('الرمز SKU', product.sku!, Icons.qr_code_rounded, AppColors.textSecondary),
                ],
              ),
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              height: 52,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('إغلاق'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _detailRow(String label, String value, IconData icon, Color color) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: color, size: 18),
          ),
          const SizedBox(width: 14),
          Text(
            label,
            style: const TextStyle(fontSize: 14, color: AppColors.textSecondary, fontWeight: FontWeight.w500),
          ),
          const Spacer(),
          Text(
            value,
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
          ),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final Color? color;
  final VoidCallback onTap;

  const _FilterChip({
    required this.label,
    required this.isSelected,
    this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final chipColor = color ?? AppColors.primary;
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? chipColor : AppColors.card,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? chipColor : AppColors.border,
            width: 1.5,
          ),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: chipColor.withValues(alpha: 0.3),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ]
              : null,
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: isSelected ? Colors.white : AppColors.textSecondary,
          ),
        ),
      ),
    );
  }
}
