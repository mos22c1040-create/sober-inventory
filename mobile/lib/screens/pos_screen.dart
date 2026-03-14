import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import '../api/api_client.dart';
import '../models/product.dart';
import '../theme/app_theme.dart';
import '../utils/api_parse.dart';
import '../services/printer_service.dart';
import 'barcode_scanner_screen.dart';
import 'printer_settings_screen.dart';

class PosScreen extends StatefulWidget {
  const PosScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<PosScreen> createState() => _PosScreenState();
}

class _PosScreenState extends State<PosScreen> with SingleTickerProviderStateMixin {
  final List<Map<String, dynamic>> _cart = [];
  List<Product> _products = [];
  bool _loadingProducts = true;
  bool _completing = false;
  bool _printing = false;
  String? _error;
  String _csrfToken = '';
  final TextEditingController _searchCtrl = TextEditingController();
  late TabController _tabCtrl;
  final PrinterService _printerService = PrinterService();

  @override
  void initState() {
    super.initState();
    _tabCtrl = TabController(length: 2, vsync: this);
    _loadProducts();
    _loadCsrf();
    _printerService.init();
  }

  @override
  void dispose() {
    _tabCtrl.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  // === BUSINESS LOGIC (UNCHANGED) ===

  Future<void> _loadCsrf() async {
    try {
      final res = await widget.api.getMe();
      if (mounted) setState(() => _csrfToken = (res['csrf_token'] ?? '').toString());
    } catch (_) {}
  }

  Future<void> _loadProducts({String search = ''}) async {
    setState(() => _loadingProducts = true);
    try {
      final res = await widget.api.getPosProducts(search: search);
      final list = (res['products'] as List<dynamic>?) ?? [];
      setState(() {
        _products = list.map((e) => Product.fromJson(Map<String, dynamic>.from(e as Map))).toList();
      });
    } catch (_) {
      if (mounted) setState(() => _error = 'فشل تحميل المنتجات');
    } finally {
      if (mounted) setState(() => _loadingProducts = false);
    }
  }

  void _addToCart(Product p) {
    setState(() {
      final i = _cart.indexWhere((e) => e['product_id'] == p.id);
      if (i >= 0) {
        _cart[i]['quantity'] = toInt(_cart[i]['quantity']) + 1;
      } else {
        _cart.add({
          'product_id': p.id,
          'quantity': 1,
          'unit_price': p.price,
          'name': p.name,
        });
      }
    });
  }

  Future<void> _scanAndAddToCart() async {
    final code = await Navigator.of(context).push<String>(
      MaterialPageRoute<String>(builder: (_) => const BarcodeScannerScreen(title: 'مسح باركود')),
    );
    if (code == null || code.isEmpty || !mounted) return;
    try {
      final res = await widget.api.findByBarcode(code);
      if (!mounted) return;
      if (res['success'] == true && res['product'] != null) {
        final product = Product.fromJson(Map<String, dynamic>.from(res['product'] as Map));
        _addToCart(product);
        _showSnack('أُضيف: ${product.name}');
      } else {
        _showSnack((res['error'] ?? 'لم يُعثر على المنتج').toString(), isError: true);
      }
    } catch (_) {
      if (mounted) _showSnack('تعذر الاتصال', isError: true);
    }
  }

  void _updateQty(int i, int delta) {
    setState(() {
      final q = toInt(_cart[i]['quantity']) + delta;
      if (q <= 0) {
        _cart.removeAt(i);
      } else {
        _cart[i]['quantity'] = q;
      }
    });
  }

  double get _cartTotal {
    double t = 0;
    for (final e in _cart) {
      t += toDouble(e['unit_price']) * toInt(e['quantity']);
    }
    return t;
  }

  int get _cartCount {
    return _cart.fold(0, (s, e) => s + toInt(e['quantity']));
  }

  Future<void> _completeSale() async {
    if (_cart.isEmpty) return;
    if (_csrfToken.isEmpty) {
      await _loadCsrf();
      if (_csrfToken.isEmpty) {
        _showSnack('جاري تجهيز الجلسة... حاول مرة أخرى', isError: true);
        return;
      }
    }
    setState(() => _completing = true);
    try {
      final items = _cart.map<Map<String, dynamic>>((e) => {
        'product_id': e['product_id'],
        'quantity': e['quantity'],
        'unit_price': e['unit_price'],
      }).toList();
      final res = await widget.api.postPosComplete(items: items, csrfToken: _csrfToken);
      if (res['success'] == true && mounted) {
        final invoice = res['invoice_number'] ?? '';
        final saleId = res['sale_id'] ?? '';
        
        setState(() { _cart.clear(); _csrfToken = ''; });
        _loadCsrf();
        _tabCtrl.animateTo(0);
        _showSnack('✓ تم البيع — فاتورة رقم #$invoice');
        
        if (_printerService.isConnected) {
          await _printReceipt(res, invoice, saleId);
        }
      } else {
        setState(() => _error = (res['error'] ?? 'فشل إتمام البيع').toString());
      }
    } catch (_) {
      setState(() => _error = 'تعذر الاتصال بالخادم');
    } finally {
      if (mounted) setState(() => _completing = false);
    }
  }

  Future<void> _printReceipt(Map<String, dynamic> saleData, String invoice, String saleId) async {
    setState(() => _printing = true);
    try {
      final receiptData = {
        'store_name': 'Sober Inventory',
        'invoice_id': invoice,
        'date': DateTime.now().toString().split('.').first,
        'items': _cart.map((item) => {
          'name': item['name'] ?? '',
          'quantity': item['quantity'],
          'price': item['unit_price'],
          'total': (item['quantity'] * item['unit_price']),
        }).toList(),
        'subtotal': _cartTotal,
        'tax': 0.0,
        'discount': 0.0,
        'total': _cartTotal,
      };
      await _printerService.printReceipt(receiptData);
      _showSnack('تم طباعة الفاتورة');
    } catch (e) {
      _showSnack('فشل الطباعة: $e', isError: true);
    } finally {
      if (mounted) setState(() => _printing = false);
    }
  }

  void _openPrinterSettings() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(builder: (_) => const PrinterSettingsScreen()),
    );
  }

  void _showSnack(String msg, {bool isError = false}) {
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

  // === UI BUILD ===

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
              _buildTabs(),
              if (_error != null) _buildErrorBar(),
              Expanded(child: TabBarView(controller: _tabCtrl, children: [_buildProductsTab(), _buildCartTab()])),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('نقطة البيع', style: GoogleFonts.cairo(fontSize: 24, fontWeight: FontWeight.w800, color: AppColors.textPrimary, letterSpacing: -0.5)),
                Text('${_cartCount} عنصر في السلة', style: GoogleFonts.cairo(fontSize: 13, color: AppColors.textSecondary, fontWeight: FontWeight.w500)),
              ],
            ),
          ),
          // Scan Button
          _buildIconButton(
            icon: Icons.qr_code_scanner_rounded,
            onTap: _scanAndAddToCart,
            color: AppColors.secondary,
            bgColor: AppColors.secondarySurface,
          ),
          if (_cart.isNotEmpty) ...[
            const SizedBox(width: 12),
            _buildClearCartButton(),
          ],
        ],
      ),
    );
  }

  Widget _buildIconButton({required IconData icon, required VoidCallback onTap, required Color color, required Color bgColor}) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(14)),
        child: Icon(icon, color: color, size: 24),
      ),
    );
  }

  Widget _buildClearCartButton() {
    return GestureDetector(
      onTap: () => setState(() => _cart.clear()),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(color: AppColors.errorBg, borderRadius: BorderRadius.circular(14)),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.delete_outline_rounded, color: AppColors.error, size: 18),
            const SizedBox(width: 6),
            Text('تفريغ', style: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.error)),
          ],
        ),
      ),
    );
  }

  Widget _buildSearchBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 12),
      child: TextField(
        controller: _searchCtrl,
        onChanged: (value) => _loadProducts(search: value),
        decoration: InputDecoration(
          hintText: 'بحث عن منتج...',
          prefixIcon: const Icon(Icons.search_rounded, color: AppColors.textSecondary),
          suffixIcon: _searchCtrl.text.isNotEmpty
              ? IconButton(icon: const Icon(Icons.clear_rounded), onPressed: () { _searchCtrl.clear(); _loadProducts(); })
              : null,
          filled: true,
          fillColor: AppColors.card,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppColors.border, width: 1)),
          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppColors.primary, width: 2)),
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        ),
      ),
    );
  }

  Widget _buildTabs() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
      child: Container(
        padding: const EdgeInsets.all(4),
        decoration: BoxDecoration(color: AppColors.card, borderRadius: BorderRadius.circular(16), boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10)]),
        child: TabBar(
          controller: _tabCtrl,
          indicator: BoxDecoration(gradient: AppColors.primaryGradient, borderRadius: BorderRadius.circular(12)),
          indicatorSize: TabBarIndicatorSize.tab,
          dividerHeight: 0,
          labelColor: Colors.white,
          unselectedLabelColor: AppColors.textSecondary,
          labelStyle: GoogleFonts.cairo(fontWeight: FontWeight.w700, fontSize: 14),
          unselectedLabelStyle: GoogleFonts.cairo(fontWeight: FontWeight.w500, fontSize: 14),
          tabs: [
            Tab(child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [const Icon(Icons.grid_view_rounded, size: 18), const SizedBox(width: 8), const Text('المنتجات')])),
            Tab(
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.shopping_cart_rounded, size: 18),
                  const SizedBox(width: 8),
                  const Text('السلة'),
                  if (_cart.isNotEmpty) ...[
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.25), borderRadius: BorderRadius.circular(8)),
                      child: Text('${_cart.length}', style: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.w700)),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildErrorBar() {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 300),
      margin: const EdgeInsets.fromLTRB(20, 0, 20, 12),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(color: AppColors.errorBg, borderRadius: BorderRadius.circular(14), border: Border.all(color: AppColors.error.withValues(alpha: 0.3))),
      child: Row(
        children: [
          const Icon(Icons.error_outline_rounded, color: AppColors.error, size: 20),
          const SizedBox(width: 10),
          Expanded(child: Text(_error ?? '', style: GoogleFonts.cairo(color: AppColors.error, fontSize: 13))),
          IconButton(icon: const Icon(Icons.close, size: 18), onPressed: () => setState(() => _error = null), padding: EdgeInsets.zero, constraints: const BoxConstraints()),
        ],
      ),
    );
  }

  Widget _buildProductsTab() {
    if (_loadingProducts) {
      return _buildLoadingState();
    }
    if (_products.isEmpty) {
      return EmptyState(
        icon: Icons.inventory_2_outlined,
        title: 'لا توجد منتجات',
        subtitle: 'أضف منتجات من صفحة الإدارة',
      );
    }
    return GridView.builder(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 20),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, childAspectRatio: 0.85, crossAxisSpacing: 12, mainAxisSpacing: 12),
      itemCount: _products.length,
      itemBuilder: (context, index) => _buildProductCard(_products[index]),
    );
  }

  Widget _buildLoadingState() {
    return GridView.builder(
      padding: const EdgeInsets.all(20),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 2, childAspectRatio: 0.85, crossAxisSpacing: 12, mainAxisSpacing: 12),
      itemCount: 6,
      itemBuilder: (context, index) => const SkeletonLoader(borderRadius: 16),
    );
  }

  Widget _buildProductCard(Product p) {
    final isOutOfStock = p.quantity <= 0;
    return GestureDetector(
      onTap: isOutOfStock ? null : () => _addToCart(p),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        decoration: BoxDecoration(
          color: AppColors.card,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: isOutOfStock ? AppColors.border : Colors.transparent, width: 1),
          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
        ),
        child: Stack(
          children: [
            Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Product Icon/Image placeholder
                  Expanded(
                    child: Container(
                      decoration: BoxDecoration(color: AppColors.primarySurface, borderRadius: BorderRadius.circular(12)),
                      child: Center(child: Icon(Icons.inventory_2_rounded, size: 36, color: AppColors.primary.withValues(alpha: 0.6))),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(p.name, style: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.textPrimary), maxLines: 2, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 4),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('${p.price.toStringAsFixed(0)} د.ع', style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.primary)),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(color: isOutOfStock ? AppColors.errorBg : AppColors.successBg, borderRadius: BorderRadius.circular(8)),
                        child: Text('${p.quantity}', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.w600, color: isOutOfStock ? AppColors.error : AppColors.success)),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            if (isOutOfStock)
              Positioned.fill(
                child: Container(
                  decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.7), borderRadius: BorderRadius.circular(16)),
                  child: const Center(child: Icon(Icons.block_rounded, color: AppColors.textTertiary, size: 32)),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildCartTab() {
    if (_cart.isEmpty) {
      return EmptyState(
        icon: Icons.shopping_cart_outlined,
        title: 'السلة فارغة',
        subtitle: 'أضف منتجات من علامة المنتجات',
        action: ElevatedButton.icon(
          onPressed: () => _tabCtrl.animateTo(0),
          icon: const Icon(Icons.add_rounded),
          label: const Text('تصفح المنتجات'),
        ),
      );
    }
    return Column(
      children: [
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 12),
            itemCount: _cart.length,
            itemBuilder: (context, index) => _buildCartItem(index),
          ),
        ),
        _buildCheckoutBar(),
      ],
    );
  }

  Widget _buildCartItem(int index) {
    final item = _cart[index];
    final name = item['name'] as String? ?? 'منتج';
    final qty = toInt(item['quantity']);
    final price = toDouble(item['unit_price']);
    return AnimatedContainer(
      duration: const Duration(milliseconds: 300),
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: AppColors.card, borderRadius: BorderRadius.circular(16), border: Border.all(color: AppColors.border)),
      child: Row(
        children: [
          Container(
            width: 48, height: 48,
            decoration: BoxDecoration(color: AppColors.primarySurface, borderRadius: BorderRadius.circular(12)),
            child: const Icon(Icons.inventory_2_rounded, color: AppColors.primary, size: 24),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name, style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.w600, color: AppColors.textPrimary), maxLines: 1, overflow: TextOverflow.ellipsis),
                Text('${price.toStringAsFixed(0)} د.ع للوحدة', style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary)),
              ],
            ),
          ),
          // Quantity Controls
          Container(
            decoration: BoxDecoration(color: AppColors.bgSecondary, borderRadius: BorderRadius.circular(12)),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                _buildQtyButton(Icons.remove, () => _updateQty(index, -1), qty <= 1 ? AppColors.error : AppColors.textSecondary),
                Container(
                  constraints: const BoxConstraints(minWidth: 40),
                  alignment: Alignment.center,
                  child: Text('$qty', style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                ),
                _buildQtyButton(Icons.add, () => _updateQty(index, 1), AppColors.primary),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQtyButton(IconData icon, VoidCallback onTap, Color color) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(10),
        child: Icon(icon, color: color, size: 20),
      ),
    );
  }

  Widget _buildCheckoutBar() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.card,
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 20, offset: const Offset(0, -4))],
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        child: Column(
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('الإجمالي', style: GoogleFonts.cairo(fontSize: 14, color: AppColors.textSecondary)),
                    const SizedBox(height: 4),
                    TweenAnimationBuilder<double>(
                      tween: Tween(begin: 0, end: _cartTotal),
                      duration: const Duration(milliseconds: 300),
                      builder: (context, value, child) {
                        return Text('${value.toStringAsFixed(0)} د.ع', style: GoogleFonts.cairo(fontSize: 28, fontWeight: FontWeight.w800, color: AppColors.primary));
                      },
                    ),
                  ],
                ),
                SizedBox(
                  width: 120,
                  height: 56,
                  child: ElevatedButton(
                    onPressed: _completing ? null : _completeSale,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      elevation: 0,
                    ),
                    child: _completing
                        ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                        : Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.check_circle_rounded, size: 22),
                              const SizedBox(width: 8),
                              Text('إتمام البيع', style: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.w700)),
                            ],
                          ),
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  width: 56,
                  height: 56,
                  decoration: BoxDecoration(
                    color: _printerService.isConnected ? AppColors.successBg : AppColors.card,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: _printerService.isConnected ? AppColors.success : AppColors.border,
                    ),
                  ),
                  child: IconButton(
                    onPressed: _openPrinterSettings,
                    icon: _printing
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                        : Icon(
                            Icons.print_rounded,
                            color: _printerService.isConnected ? AppColors.success : AppColors.textSecondary,
                          ),
                    tooltip: 'إعدادات الطابعة',
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}