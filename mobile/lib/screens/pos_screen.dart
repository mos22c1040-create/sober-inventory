import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show HapticFeedback;
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

class _PosScreenState extends State<PosScreen>
    with SingleTickerProviderStateMixin {
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

  // ── Business Logic (unchanged) ──────────────────────────────────────────────
  Future<void> _loadCsrf() async {
    try {
      final res = await widget.api.getMe();
      if (mounted) setState(() => _csrfToken = (res['csrf_token'] ?? '').toString());
    } catch (_) {}
  }

  Future<void> _loadProducts({String search = ''}) async {
    setState(() { _loadingProducts = true; _error = null; });
    try {
      final res = await widget.api.getPosProducts(search: search);
      final list = (res['products'] as List<dynamic>?) ?? [];
      if (mounted) {
        setState(() {
          _products = list
              .map((e) => Product.fromJson(Map<String, dynamic>.from(e as Map)))
              .toList();
        });
      }
    } on DioException catch (e) {
      if (!mounted) return;
      // 401: سيتم التوجيه لتسجيل الدخول عبر الـ interceptor — لا نعرض رسالة عامة
      if (e.response?.statusCode == 401) {
        setState(() => _error = null);
        return;
      }
      final msg = e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.unknown
          ? 'تحقق من الاتصال بالإنترنت أو سجّل الدخول من جديد'
          : 'فشل تحميل المنتجات';
      setState(() => _error = msg);
    } catch (_) {
      if (mounted) setState(() => _error = 'فشل تحميل المنتجات. تحقق من الاتصال.');
    } finally {
      if (mounted) setState(() => _loadingProducts = false);
    }
  }

  void _addToCart(Product p) {
    HapticFeedback.lightImpact();
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
      MaterialPageRoute<String>(
        builder: (_) => const BarcodeScannerScreen(title: 'مسح باركود')),
    );
    if (code == null || code.isEmpty || !mounted) return;
    try {
      final res = await widget.api.findByBarcode(code);
      if (!mounted) return;
      if (res['success'] == true && res['product'] != null) {
        final p = Product.fromJson(
          Map<String, dynamic>.from(res['product'] as Map));
        _addToCart(p);
        _showSnack('أُضيف: ${p.name}');
      } else {
        _showSnack(
          (res['error'] ?? 'لم يُعثر على المنتج').toString(), isError: true);
      }
    } catch (_) {
      if (mounted) _showSnack('تعذر الاتصال', isError: true);
    }
  }

  void _updateQty(int i, int delta) {
    HapticFeedback.selectionClick();
    setState(() {
      final q = toInt(_cart[i]['quantity']) + delta;
      if (q <= 0) { _cart.removeAt(i); } 
      else { _cart[i]['quantity'] = q; }
    });
  }

  double get _cartTotal {
    return _cart.fold(0, (s, e) => s + toDouble(e['unit_price']) * toInt(e['quantity']));
  }

  int get _cartCount =>
      _cart.fold(0, (s, e) => s + toInt(e['quantity']));

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
        'product_id': toInt(e['product_id']),
        'quantity': toInt(e['quantity']),
        'unit_price': (e['unit_price'] as num?)?.toDouble() ?? 0.0,
      }).toList();
      final res = await widget.api.postPosComplete(
        items: items, csrfToken: _csrfToken);
      if (res['success'] == true && mounted) {
        final invoice = res['invoice_number'] ?? '';
        final saleId  = res['sale_id'] ?? '';
        setState(() { _cart.clear(); _csrfToken = ''; });
        _loadCsrf();
        _tabCtrl.animateTo(0);
        _showSnack('✓ تم البيع — فاتورة رقم #$invoice');
        if (_printerService.isConnected) {
          await _printReceipt(res, invoice, saleId);
        }
      } else {
        setState(() =>
          _error = (res['error'] ?? res['data']?['error'] ?? 'فشل إتمام البيع').toString());
      }
    } on DioException catch (e) {
      final body = e.response?.data;
      final msg = body is Map
          ? (body['error'] ?? body['data']?['error'] ?? 'تعذر الاتصال بالخادم').toString()
          : (e.response?.statusCode == 400 ? 'طلب غير صالح (تحقق من الكميات أو المخزون)' : 'تعذر الاتصال بالخادم');
      if (mounted) setState(() => _error = msg);
    } catch (_) {
      if (mounted) setState(() => _error = 'تعذر الاتصال بالخادم');
    } finally {
      if (mounted) setState(() => _completing = false);
    }
  }

  Future<void> _printReceipt(
      Map<String, dynamic> saleData, String invoice, String saleId) async {
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
          'total': item['quantity'] * item['unit_price'],
        }).toList(),
        'subtotal': _cartTotal,
        'tax': 0.0, 'discount': 0.0, 'total': _cartTotal,
      };
      await _printerService.printReceipt(receiptData);
      _showSnack('تم طباعة الفاتورة');
    } catch (e) {
      _showSnack('فشل الطباعة: $e', isError: true);
    } finally {
      if (mounted) setState(() => _printing = false);
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

  // ── UI ──────────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        body: SafeArea(
          child: Column(children: [
            _buildHeader(),
            _buildSearchAndTabs(),
            if (_error != null) _buildErrorBar(),
            Expanded(
              child: TabBarView(
                controller: _tabCtrl,
                children: [_buildProductsTab(), _buildCartTab()],
              ),
            ),
          ]),
        ),
      ),
    );
  }

  // ── Header ──────────────────────────────────────────────────────────────────
  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 4),
      child: Row(children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('نقطة البيع',
                style: GoogleFonts.cairo(
                  fontSize: 22, fontWeight: FontWeight.w800,
                  color: AppColors.textPrimary, letterSpacing: -0.4)),
              Text('$_cartCount عنصر في السلة',
                style: GoogleFonts.cairo(
                  fontSize: 13, color: AppColors.textSecondary,
                  fontWeight: FontWeight.w500)),
            ],
          ),
        ),

        // Printer button
        AppIconButton(
          icon: Icons.print_rounded,
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute<void>(
              builder: (_) => const PrinterSettingsScreen())),
          color: _printerService.isConnected
            ? AppColors.success : AppColors.textSecondary,
          bgColor: _printerService.isConnected
            ? AppColors.successBg : AppColors.bgSecondary,
          size: 44,
        ),

        const SizedBox(width: 8),

        // Scan button
        AppIconButton(
          icon: Icons.qr_code_scanner_rounded,
          onTap: _scanAndAddToCart,
          color: AppColors.primary,
          bgColor: AppColors.primarySurface,
          size: 44,
        ),

        if (_cart.isNotEmpty) ...[
          const SizedBox(width: 8),
          AppIconButton(
            icon: Icons.delete_sweep_rounded,
            onTap: () {
              HapticFeedback.mediumImpact();
              setState(() => _cart.clear());
            },
            color: AppColors.error,
            bgColor: AppColors.errorBg,
            size: 44,
          ),
        ],
      ]),
    );
  }

  // ── Search + Tabs ────────────────────────────────────────────────────────────
  Widget _buildSearchAndTabs() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 10, 20, 0),
      child: Column(children: [
        // Search bar
        TextField(
          controller: _searchCtrl,
          onChanged: (v) => _loadProducts(search: v),
          decoration: InputDecoration(
            hintText: 'بحث عن منتج...',
            prefixIcon: const Icon(Icons.search_rounded,
              color: AppColors.textSecondary, size: 22),
            suffixIcon: _searchCtrl.text.isNotEmpty
                ? IconButton(
                    icon: const Icon(Icons.clear_rounded, size: 20),
                    onPressed: () {
                      _searchCtrl.clear();
                      _loadProducts();
                    })
                : null,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16, vertical: 14),
          ),
        ),

        const SizedBox(height: 12),

        // Pill Tabs
        Container(
          padding: const EdgeInsets.all(4),
          decoration: BoxDecoration(
            color: AppColors.card,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.border),
          ),
          child: TabBar(
            controller: _tabCtrl,
            indicator: BoxDecoration(
              gradient: AppColors.primaryGradient,
              borderRadius: BorderRadius.circular(12)),
            indicatorSize: TabBarIndicatorSize.tab,
            dividerHeight: 0,
            labelColor: Colors.white,
            unselectedLabelColor: AppColors.textSecondary,
            labelStyle: GoogleFonts.cairo(
              fontWeight: FontWeight.w700, fontSize: 14),
            unselectedLabelStyle: GoogleFonts.cairo(
              fontWeight: FontWeight.w500, fontSize: 14),
            tabs: [
              Tab(
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: const [
                    Icon(Icons.grid_view_rounded, size: 18),
                    SizedBox(width: 7),
                    Text('المنتجات'),
                  ],
                ),
              ),
              Tab(
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.shopping_cart_rounded, size: 18),
                    const SizedBox(width: 7),
                    const Text('السلة'),
                    if (_cart.isNotEmpty) ...[
                      const SizedBox(width: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 7, vertical: 2),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.22),
                          borderRadius: BorderRadius.circular(8)),
                        child: Text('${_cart.length}',
                          style: GoogleFonts.cairo(
                            fontSize: 11, fontWeight: FontWeight.w700))),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 12),
      ]),
    );
  }

  // ── Error Bar ────────────────────────────────────────────────────────────────
  Widget _buildErrorBar() {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 0, 20, 10),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.errorBg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.error.withValues(alpha: 0.25))),
      child: Row(children: [
        const Icon(Icons.error_outline_rounded,
          color: AppColors.error, size: 18),
        const SizedBox(width: 10),
        Expanded(
          child: Text(_error ?? '',
            style: GoogleFonts.cairo(color: AppColors.error, fontSize: 13))),
        TextButton(
          onPressed: () {
            setState(() => _error = null);
            _loadProducts();
          },
          child: Text('إعادة',
            style: GoogleFonts.cairo(
              fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.error))),
        GestureDetector(
          onTap: () => setState(() => _error = null),
          child: const Icon(Icons.close_rounded,
            size: 18, color: AppColors.error)),
      ]),
    );
  }

  // ── Products Tab ─────────────────────────────────────────────────────────────
  Widget _buildProductsTab() {
    if (_loadingProducts) {
      return GridView.builder(
        padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2, childAspectRatio: 0.82,
          crossAxisSpacing: 12, mainAxisSpacing: 12),
        itemCount: 6,
        itemBuilder: (_, __) =>
            const SkeletonLoader(borderRadius: 20),
      );
    }
    if (_products.isEmpty) {
      return EmptyState(
        icon: Icons.inventory_2_outlined,
        title: 'لا توجد منتجات',
        subtitle: 'أضف منتجات من صفحة الإدارة',
      );
    }
    return GridView.builder(
      padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2, childAspectRatio: 0.82,
        crossAxisSpacing: 12, mainAxisSpacing: 12),
      itemCount: _products.length,
      itemBuilder: (_, i) => _buildProductCard(_products[i]),
    );
  }

  Widget _buildProductCard(Product p) {
    final isOut  = p.quantity <= 0;
    final inCart = _cart.where((e) => e['product_id'] == p.id).fold<int>(
      0, (s, e) => s + toInt(e['quantity']));

    return GestureDetector(
      onTap: isOut ? null : () => _addToCart(p),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        decoration: BoxDecoration(
          color: AppColors.card,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: inCart > 0
                ? AppColors.primary.withValues(alpha: 0.40)
                : AppColors.border),
          boxShadow: [
            BoxShadow(
              color: inCart > 0
                  ? AppColors.primary.withValues(alpha: 0.08)
                  : Colors.black.withValues(alpha: 0.04),
              blurRadius: 12, offset: const Offset(0, 3)),
          ],
        ),
        child: Stack(children: [
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Product icon
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: isOut
                          ? AppColors.bgSecondary
                          : AppColors.primarySurface,
                      borderRadius: BorderRadius.circular(14)),
                    child: Center(
                      child: Icon(Icons.inventory_2_rounded,
                        size: 34,
                        color: isOut
                          ? AppColors.textTertiary
                          : AppColors.primary.withValues(alpha: 0.65))),
                  ),
                ),
                const SizedBox(height: 10),
                Text(p.name,
                  style: GoogleFonts.cairo(
                    fontSize: 13, fontWeight: FontWeight.w600,
                    color: isOut
                      ? AppColors.textTertiary : AppColors.textPrimary),
                  maxLines: 2, overflow: TextOverflow.ellipsis),
                const SizedBox(height: 4),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('${p.price.toStringAsFixed(0)} د.ع',
                      style: GoogleFonts.cairo(
                        fontSize: 14, fontWeight: FontWeight.w700,
                        color: isOut
                          ? AppColors.textTertiary : AppColors.primary)),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 7, vertical: 3),
                      decoration: BoxDecoration(
                        color: isOut
                          ? AppColors.errorBg : AppColors.successBg,
                        borderRadius: BorderRadius.circular(8)),
                      child: Text('${p.quantity}',
                        style: GoogleFonts.cairo(
                          fontSize: 10, fontWeight: FontWeight.w700,
                          color: isOut
                            ? AppColors.error : AppColors.success))),
                  ],
                ),
              ],
            ),
          ),

          // In-cart badge
          if (inCart > 0)
            Positioned(
              top: 10, left: 10,
              child: Container(
                width: 22, height: 22,
                decoration: BoxDecoration(
                  gradient: AppColors.primaryGradient,
                  shape: BoxShape.circle),
                child: Center(
                  child: Text('$inCart',
                    style: GoogleFonts.cairo(
                      fontSize: 11, fontWeight: FontWeight.w700,
                      color: Colors.white))))),

          // Out-of-stock overlay
          if (isOut)
            Positioned.fill(
              child: Container(
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.65),
                  borderRadius: BorderRadius.circular(20)),
                child: const Center(
                  child: Icon(Icons.block_rounded,
                    color: AppColors.textTertiary, size: 28)),
              )),
        ]),
      ),
    );
  }

  // ── Cart Tab ─────────────────────────────────────────────────────────────────
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
    return Column(children: [
      Expanded(
        child: ListView.builder(
          padding: const EdgeInsets.fromLTRB(20, 4, 20, 12),
          itemCount: _cart.length,
          itemBuilder: (_, i) => _buildCartItem(i),
        ),
      ),
      _buildCheckoutBar(),
    ]);
  }

  Widget _buildCartItem(int index) {
    final item  = _cart[index];
    final name  = item['name'] as String? ?? 'منتج';
    final qty   = toInt(item['quantity']);
    final price = toDouble(item['unit_price']);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border)),
      child: Row(children: [
        Container(
          width: 44, height: 44,
          decoration: BoxDecoration(
            color: AppColors.primarySurface,
            borderRadius: BorderRadius.circular(12)),
          child: const Icon(Icons.inventory_2_rounded,
            color: AppColors.primary, size: 22)),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(name,
                style: GoogleFonts.cairo(
                  fontSize: 14, fontWeight: FontWeight.w600,
                  color: AppColors.textPrimary),
                maxLines: 1, overflow: TextOverflow.ellipsis),
              Text('${price.toStringAsFixed(0)} د.ع للوحدة',
                style: GoogleFonts.cairo(
                  fontSize: 12, color: AppColors.textSecondary)),
            ],
          ),
        ),
        const SizedBox(width: 10),
        // Qty controls
        Container(
          decoration: BoxDecoration(
            color: AppColors.bgSecondary,
            borderRadius: BorderRadius.circular(12)),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            _qtyBtn(
              icon: qty <= 1
                ? Icons.delete_outline_rounded : Icons.remove_rounded,
              color: qty <= 1 ? AppColors.error : AppColors.textSecondary,
              onTap: () => _updateQty(index, -1),
            ),
            Container(
              constraints: const BoxConstraints(minWidth: 36),
              alignment: Alignment.center,
              child: Text('$qty',
                style: GoogleFonts.cairo(
                  fontSize: 15, fontWeight: FontWeight.w700,
                  color: AppColors.textPrimary))),
            _qtyBtn(
              icon: Icons.add_rounded,
              color: AppColors.primary,
              onTap: () => _updateQty(index, 1),
            ),
          ]),
        ),
      ]),
    );
  }

  Widget _qtyBtn({
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.all(10),
        child: Icon(icon, color: color, size: 19)),
    );
  }

  // ── Checkout Bar ─────────────────────────────────────────────────────────────
  Widget _buildCheckoutBar() {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
      decoration: BoxDecoration(
        color: AppColors.card,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.07),
            blurRadius: 20, offset: const Offset(0, -4)),
        ],
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Row(children: [
          // Total
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('الإجمالي',
                  style: GoogleFonts.cairo(
                    fontSize: 13, color: AppColors.textSecondary)),
                const SizedBox(height: 2),
                TweenAnimationBuilder<double>(
                  tween: Tween(begin: 0, end: _cartTotal),
                  duration: const Duration(milliseconds: 300),
                  builder: (_, v, __) => Text(
                    '${v.toStringAsFixed(0)} د.ع',
                    style: GoogleFonts.cairo(
                      fontSize: 26, fontWeight: FontWeight.w800,
                      color: AppColors.primary, letterSpacing: -0.5))),
              ],
            ),
          ),
          const SizedBox(width: 12),
          // Complete button
          SizedBox(
            width: 140, height: 52,
            child: AppPrimaryButton(
              label: 'إتمام البيع',
              icon: Icons.check_circle_outline_rounded,
              onPressed: _completing ? null : _completeSale,
              loading: _completing,
            ),
          ),
        ]),
      ),
    );
  }
}
