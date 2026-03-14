import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../api/api_client.dart';
import '../models/product.dart';
import '../theme/app_theme.dart';
import 'barcode_scanner_screen.dart';
import 'product_form_screen.dart';

class ProductsScreen extends StatefulWidget {
  const ProductsScreen({
    super.key,
    required this.api,
    this.editMode = false,
  });

  final ApiClient api;
  final bool editMode;

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  bool _loading = true;
  List<Product> _products = [];
  String? _error;
  final _searchCtrl = TextEditingController();
  bool _searching = false;
  String _csrfToken = '';
  String _stockFilter = 'all';

  List<Product> get _filtered {
    if (_stockFilter == 'all') return _products;
    return _products.where((p) {
      if (_stockFilter == 'out')       return p.quantity <= 0;
      if (_stockFilter == 'low')       return p.quantity > 0 && p.quantity <= 5;
      if (_stockFilter == 'available') return p.quantity > 5;
      return true;
    }).toList();
  }

  @override
  void initState() {
    super.initState();
    _load();
    if (widget.editMode) _loadCsrf();
  }

  @override
  void dispose() { _searchCtrl.dispose(); super.dispose(); }

  Future<void> _loadCsrf() async {
    try {
      final r = await widget.api.getMe();
      if (mounted) setState(() => _csrfToken = (r['csrf_token'] ?? '').toString());
    } catch (_) {}
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
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
      setState(() {
        _error = code == 401
            ? 'انتهت الجلسة. سجّل الدخول من جديد.'
            : 'فشل تحميل المنتجات. تحقق من الاتصال.';
      });
    } catch (_) {
      if (mounted) setState(() => _error = 'فشل تحميل المنتجات. تحقق من الاتصال.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _deleteProduct(int id) async {
    if (_csrfToken.isEmpty) return;
    try {
      final r = await widget.api.deleteProduct(id: id, csrfToken: _csrfToken);
      if (r['success'] == true) {
        _showSnack('تم الحذف');
        _load();
      } else {
        _showSnack((r['error'] ?? 'فشل الحذف').toString(), isError: true);
      }
    } catch (_) {
      _showSnack('تعذر الاتصال', isError: true);
    }
  }

  void _showForm([Product? p]) {
    Navigator.of(context).push(MaterialPageRoute<void>(
      builder: (_) => ProductFormScreen(
        api: widget.api,
        csrfToken: _csrfToken,
        product: p,
        onSaved: () { Navigator.pop(context); _load(); },
      ),
    ));
  }

  Future<void> _openScanner() async {
    final code = await Navigator.of(context).push<String>(
      MaterialPageRoute<String>(
        builder: (_) => const BarcodeScannerScreen(title: 'مسح باركود المنتج')),
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
        _showProductSheet(
          Product.fromJson(Map<String, dynamic>.from(res['product'] as Map)));
      } else {
        _showSnack((res['error'] ?? 'لم يُعثر على المنتج').toString(), isError: true);
      }
    } catch (_) {
      if (mounted) _showSnack('تعذر الاتصال بالخادم', isError: true);
    } finally {
      if (mounted) setState(() => _searching = false);
    }
  }

  void _showSnack(String msg, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: isError ? AppColors.error : AppColors.success,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      margin: const EdgeInsets.all(16),
    ));
  }

  void _showProductSheet(Product p) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => _ProductDetailSheet(product: p),
    );
  }

  // ── Build ─────────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        body: SafeArea(
          child: Column(children: [
            _buildHeader(),
            _buildSearchBar(),
            _buildFilterChips(),
            if (_error != null) _buildErrorBanner(),
            Expanded(
              child: _loading
                  ? _buildSkeletons()
                  : RefreshIndicator(
                      color: AppColors.primary,
                      onRefresh: _load,
                      child: _filtered.isEmpty
                          ? _buildEmpty()
                          : _buildList(),
                    ),
            ),
          ]),
        ),
        floatingActionButton: widget.editMode
            ? FloatingActionButton(
                onPressed: () => _showForm(),
                tooltip: 'إضافة منتج',
                child: const Icon(Icons.add_rounded))
            : null,
      ),
    );
  }

  // ── Header ────────────────────────────────────────────────────────────────────
  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
      child: Row(children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('المنتجات',
                style: GoogleFonts.cairo(
                  fontSize: 22, fontWeight: FontWeight.w800,
                  color: AppColors.textPrimary, letterSpacing: -0.4)),
              Text('${_filtered.length} منتج',
                style: GoogleFonts.cairo(
                  fontSize: 13, color: AppColors.textSecondary,
                  fontWeight: FontWeight.w500)),
            ],
          ),
        ),
        AppIconButton(
          icon: Icons.refresh_rounded,
          onTap: _load,
          color: AppColors.primary,
          bgColor: AppColors.primarySurface,
          size: 44,
        ),
      ]),
    );
  }

  // ── Search Bar ────────────────────────────────────────────────────────────────
  Widget _buildSearchBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 14, 20, 0),
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.card,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.border),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 8, offset: const Offset(0, 2)),
          ],
        ),
        child: Row(children: [
          // Camera scan
          GestureDetector(
            onTap: _openScanner,
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Icon(Icons.qr_code_scanner_rounded,
                color: AppColors.primary, size: 24))),
          // Divider
          Container(width: 1, height: 24, color: AppColors.border),
          // Text field
          Expanded(
            child: TextField(
              controller: _searchCtrl,
              decoration: InputDecoration(
                hintText: 'بحث بالباركود أو الرمز SKU...',
                border: InputBorder.none,
                enabledBorder: InputBorder.none,
                focusedBorder: InputBorder.none,
                filled: false,
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 12, vertical: 15),
                suffixIcon: _searching
                    ? const Padding(
                        padding: EdgeInsets.all(13),
                        child: SizedBox(width: 18, height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2, color: AppColors.primary)))
                    : IconButton(
                        icon: const Icon(Icons.search_rounded,
                          color: AppColors.primary, size: 22),
                        onPressed: _searchByBarcode),
              ),
              onSubmitted: (_) => _searchByBarcode(),
            ),
          ),
        ]),
      ),
    );
  }

  // ── Filter Chips ──────────────────────────────────────────────────────────────
  Widget _buildFilterChips() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 6),
      child: Row(children: [
        _Chip(label: 'الكل',   active: _stockFilter == 'all',
          color: AppColors.primary, onTap: () => setState(() => _stockFilter = 'all')),
        const SizedBox(width: 8),
        _Chip(label: 'متوفر',  active: _stockFilter == 'available',
          color: AppColors.success, onTap: () => setState(() => _stockFilter = 'available')),
        const SizedBox(width: 8),
        _Chip(label: 'منخفض', active: _stockFilter == 'low',
          color: AppColors.warning, onTap: () => setState(() => _stockFilter = 'low')),
        const SizedBox(width: 8),
        _Chip(label: 'نفد',    active: _stockFilter == 'out',
          color: AppColors.error,   onTap: () => setState(() => _stockFilter = 'out')),
      ]),
    );
  }

  // ── Error Banner ──────────────────────────────────────────────────────────────
  Widget _buildErrorBanner() {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 4, 20, 8),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.errorBg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.error.withValues(alpha: 0.25))),
      child: Row(children: [
        const Icon(Icons.error_outline_rounded, color: AppColors.error, size: 18),
        const SizedBox(width: 10),
        Expanded(child: Text(_error!,
          style: GoogleFonts.cairo(color: AppColors.error, fontSize: 13))),
        TextButton(
          onPressed: _load,
          child: Text('إعادة',
            style: GoogleFonts.cairo(
              color: AppColors.error, fontWeight: FontWeight.w600))),
      ]),
    );
  }

  // ── List ──────────────────────────────────────────────────────────────────────
  Widget _buildList() {
    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(20, 4, 20, 100),
      itemCount: _filtered.length,
      itemBuilder: (_, i) {
        final p = _filtered[i];
        return _ProductCard(
          product: p,
          isOutOfStock: p.quantity <= 0,
          isLow: p.quantity > 0 && p.quantity <= 5,
          onTap: () => _showProductSheet(p),
          onEdit: widget.editMode ? () => _showForm(p) : null,
          onDelete: widget.editMode
              ? () => _confirmDelete(context, p) : null,
        );
      },
    );
  }

  void _confirmDelete(BuildContext ctx, Product p) {
    showDialog<void>(
      context: ctx,
      builder: (_) => AlertDialog(
        title: Text('حذف المنتج',
          style: GoogleFonts.cairo(fontWeight: FontWeight.w700)),
        content: Text('هل أنت متأكد من حذف "${p.name}"؟',
          style: GoogleFonts.cairo()),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('إلغاء')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.error),
            onPressed: () { Navigator.pop(ctx); _deleteProduct(p.id); },
            child: const Text('حذف')),
        ],
      ),
    );
  }

  Widget _buildEmpty() {
    final isFiltered = _stockFilter != 'all';
    return EmptyState(
      icon: isFiltered
          ? Icons.filter_list_off_rounded : Icons.inventory_2_rounded,
      title: isFiltered ? 'لا توجد نتائج' : 'لا توجد منتجات',
      subtitle: isFiltered
          ? 'جرب فلتر آخر'
          : 'أضف منتجات من لوحة التحكم على الموقع',
      action: isFiltered
          ? TextButton(
              onPressed: () => setState(() => _stockFilter = 'all'),
              child: const Text('إزالة الفلتر'))
          : null,
    );
  }

  Widget _buildSkeletons() {
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemCount: 8,
      itemBuilder: (_, __) =>
          const SkeletonLoader(height: 76, borderRadius: 20),
    );
  }
}

// ── Product Card ──────────────────────────────────────────────────────────────
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
    Color badgeBg    = AppColors.successBg;
    String badgeText = 'متوفر';

    if (isOutOfStock) {
      badgeColor = AppColors.error;
      badgeBg    = AppColors.errorBg;
      badgeText  = 'نفد';
    } else if (isLow) {
      badgeColor = AppColors.warning;
      badgeBg    = AppColors.warningBg;
      badgeText  = 'منخفض';
    }

    final letter = product.name.isNotEmpty
        ? product.name[0].toUpperCase() : '?';

    return AppCards.modern(
      onTap: onTap,
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      child: Row(children: [
        // Avatar
        Container(
          width: 50, height: 50,
          decoration: BoxDecoration(
            gradient: LinearGradient(colors: [
              AppColors.primary.withValues(alpha: 0.12),
              AppColors.primaryLight.withValues(alpha: 0.06),
            ]),
            borderRadius: BorderRadius.circular(14)),
          child: Center(
            child: Text(letter,
              style: GoogleFonts.cairo(
                fontSize: 20, fontWeight: FontWeight.w800,
                color: AppColors.primary))),
        ),
        const SizedBox(width: 12),
        // Info
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(product.name,
                style: GoogleFonts.cairo(
                  fontWeight: FontWeight.w700, fontSize: 14,
                  color: AppColors.textPrimary),
                maxLines: 1, overflow: TextOverflow.ellipsis),
              const SizedBox(height: 3),
              Row(children: [
                Text('الكمية: ${product.quantity}',
                  style: GoogleFonts.cairo(
                    fontSize: 12, color: AppColors.textSecondary)),
                if (product.sku != null) ...[
                  Text(' · ',
                    style: GoogleFonts.cairo(color: AppColors.textTertiary)),
                  Flexible(child: Text(product.sku!,
                    style: GoogleFonts.cairo(
                      fontSize: 11, color: AppColors.textTertiary),
                    maxLines: 1, overflow: TextOverflow.ellipsis)),
                ],
              ]),
            ],
          ),
        ),
        const SizedBox(width: 10),
        // Right side
        Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(product.price.toStringAsFixed(0),
              style: GoogleFonts.cairo(
                fontSize: 15, fontWeight: FontWeight.w800,
                color: AppColors.primary, letterSpacing: -0.3)),
            const SizedBox(height: 5),
            AppBadge(
              label: badgeText,
              color: badgeColor,
              bgColor: badgeBg,
            ),
            if (onEdit != null || onDelete != null) ...[
              const SizedBox(height: 6),
              Row(mainAxisSize: MainAxisSize.min, children: [
                if (onEdit != null)
                  _ActionBtn(
                    icon: Icons.edit_rounded,
                    color: AppColors.primary,
                    bg: AppColors.primarySurface,
                    onTap: onEdit!),
                if (onEdit != null && onDelete != null)
                  const SizedBox(width: 6),
                if (onDelete != null)
                  _ActionBtn(
                    icon: Icons.delete_outline_rounded,
                    color: AppColors.error,
                    bg: AppColors.errorBg,
                    onTap: onDelete!),
              ]),
            ],
          ],
        ),
      ]),
    );
  }
}

class _ActionBtn extends StatelessWidget {
  const _ActionBtn({
    required this.icon,
    required this.color,
    required this.bg,
    required this.onTap,
  });
  final IconData icon;
  final Color color;
  final Color bg;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(5),
        decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(8)),
        child: Icon(icon, color: color, size: 14)),
    );
  }
}

// ── Product Detail Sheet ──────────────────────────────────────────────────────
class _ProductDetailSheet extends StatelessWidget {
  const _ProductDetailSheet({required this.product});

  final Product product;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.card, borderRadius: BorderRadius.circular(28)),
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Handle
            Container(width: 40, height: 4,
              decoration: BoxDecoration(
                color: AppColors.border,
                borderRadius: BorderRadius.circular(2))),
            const SizedBox(height: 24),
            // Icon
            Container(
              width: 68, height: 68,
              decoration: BoxDecoration(
                gradient: LinearGradient(colors: [
                  AppColors.primary.withValues(alpha: 0.12),
                  AppColors.primaryLight.withValues(alpha: 0.06),
                ]),
                borderRadius: BorderRadius.circular(20)),
              child: const Icon(Icons.inventory_2_rounded,
                color: AppColors.primary, size: 34)),
            const SizedBox(height: 14),
            // Name
            Text(product.name,
              textAlign: TextAlign.center,
              style: GoogleFonts.cairo(
                fontSize: 19, fontWeight: FontWeight.w800,
                color: AppColors.textPrimary, letterSpacing: -0.3)),
            const SizedBox(height: 22),
            // Details
            Container(
              padding: const EdgeInsets.all(4),
              decoration: BoxDecoration(
                color: AppColors.bg,
                borderRadius: BorderRadius.circular(16)),
              child: Column(children: [
                _DetailRow('السعر',
                  '${product.price.toStringAsFixed(0)} د.ع',
                  Icons.attach_money_rounded, AppColors.primary),
                _DetailRow('الكمية', '${product.quantity}',
                  Icons.inventory_rounded, AppColors.success),
                if (product.sku != null)
                  _DetailRow('الرمز SKU', product.sku!,
                    Icons.qr_code_rounded, AppColors.textSecondary),
              ]),
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              height: 52,
              child: ElevatedButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('إغلاق'))),
          ],
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow(this.label, this.value, this.icon, this.color);
  final String label, value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      child: Row(children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.10),
            borderRadius: BorderRadius.circular(10)),
          child: Icon(icon, color: color, size: 17)),
        const SizedBox(width: 12),
        Text(label, style: GoogleFonts.cairo(
          fontSize: 13, color: AppColors.textSecondary,
          fontWeight: FontWeight.w500)),
        const Spacer(),
        Text(value, style: GoogleFonts.cairo(
          fontSize: 14, fontWeight: FontWeight.w700,
          color: AppColors.textPrimary)),
      ]),
    );
  }
}

// ── Filter Chip ───────────────────────────────────────────────────────────────
class _Chip extends StatelessWidget {
  const _Chip({
    required this.label,
    required this.active,
    required this.color,
    required this.onTap,
  });
  final String label;
  final bool active;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
        decoration: BoxDecoration(
          color: active ? color : AppColors.card,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: active ? color : AppColors.border, width: 1.5),
          boxShadow: active
              ? [BoxShadow(
                  color: color.withValues(alpha: 0.25),
                  blurRadius: 8, offset: const Offset(0, 2))]
              : null,
        ),
        child: Text(label,
          style: GoogleFonts.cairo(
            fontSize: 12, fontWeight: FontWeight.w600,
            color: active ? Colors.white : AppColors.textSecondary)),
      ),
    );
  }
}
