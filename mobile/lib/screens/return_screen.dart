import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';

class ReturnScreen extends StatefulWidget {
  const ReturnScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<ReturnScreen> createState() => _ReturnScreenState();
}

class _ReturnScreenState extends State<ReturnScreen> {
  final _saleIdCtrl = TextEditingController();
  final _reasonCtrl = TextEditingController();
  final _fmt        = NumberFormat('#,##0');

  bool _loading    = false;
  bool _submitting = false;
  String? _error;
  Map<String, dynamic>? _saleData;
  List<_SaleItemReturn> _items = [];

  @override
  void dispose() {
    _saleIdCtrl.dispose();
    _reasonCtrl.dispose();
    super.dispose();
  }

  // ── Logic ────────────────────────────────────────────────────────────────────
  Future<void> _search() async {
    final text = _saleIdCtrl.text.trim();
    if (text.isEmpty) { _snack('أدخل رقم الفاتورة', isError: true); return; }
    final id = int.tryParse(text);
    if (id == null || id <= 0) {
      _snack('رقم الفاتورة غير صالح', isError: true); return;
    }

    setState(() { _loading = true; _error = null; _saleData = null; _items = []; });

    try {
      final res  = await widget.api.getSaleDetails(id);
      final sale = res['sale'] as Map<String, dynamic>?;
      final list = res['items'] as List<dynamic>?;

      if (sale == null) { _snack('الفاتورة غير موجودة', isError: true); return; }
      if (sale['status'] == 'cancelled') {
        _snack('لا يمكن إرجاع فاتورة ملغاة', isError: true); return;
      }

      setState(() {
        _saleData = sale;
        _items = (list ?? []).map((e) => _SaleItemReturn(
          productId:        (e as Map)['product_id'] as int,
          productName:      e['product_name'] as String? ?? 'منتج',
          originalQuantity: e['quantity'] as int,
          unitPrice:        (e['unit_price'] as num).toDouble(),
        )).toList();
      });
    } catch (_) {
      setState(() => _error = 'فشل تحميل الفاتورة');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  double get _totalRefund =>
      _items.fold(0.0, (s, e) => s + e.returnQty * e.unitPrice);

  Future<void> _submit() async {
    final selected = _items.where((e) => e.returnQty > 0).toList();
    if (selected.isEmpty) {
      _snack('حدد منتجات للإرجاع', isError: true); return;
    }

    final csrf = await _getCsrf();
    setState(() { _submitting = true; _error = null; });

    try {
      await widget.api.submitReturn({
        'sale_id':    int.parse(_saleIdCtrl.text.trim()),
        'reason':     _reasonCtrl.text.trim(),
        'csrf_token': csrf,
        'items': selected.map((e) => {
          'product_id': e.productId,
          'quantity':   e.returnQty,
          'unit_price': e.unitPrice,
        }).toList(),
      });
      if (mounted) {
        _snack('تم معالجة الإرجاع بنجاح');
        _reset();
      }
    } catch (_) {
      setState(() => _error = 'فشل معالجة الإرجاع. تحقق من البيانات.');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<String> _getCsrf() async {
    try {
      final me = await widget.api.getMe();
      return me['csrf_token'] ?? '';
    } catch (_) { return ''; }
  }

  void _snack(String msg, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: isError ? AppColors.error : AppColors.success,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      margin: const EdgeInsets.all(16),
    ));
  }

  void _reset() {
    setState(() {
      _saleIdCtrl.clear();
      _reasonCtrl.clear();
      _saleData = null;
      _items = [];
    });
  }

  // ── Build ────────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: Text('إرجاع المنتجات',
            style: GoogleFonts.cairo(
              fontWeight: FontWeight.w700, fontSize: 17)),
          backgroundColor: AppColors.bg,
          elevation: 0,
          scrolledUnderElevation: 0.5,
        ),
        body: _loading
            ? const Center(
                child: CircularProgressIndicator(color: AppColors.primary))
            : _saleData == null
                ? _buildSearch()
                : _buildForm(),
      ),
    );
  }

  // ── Search Section ────────────────────────────────────────────────────────────
  Widget _buildSearch() {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(22),
              decoration: BoxDecoration(
                color: AppColors.primarySurface,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(
                  color: AppColors.primary.withValues(alpha: 0.15))),
              child: const Icon(Icons.receipt_long_rounded,
                size: 48, color: AppColors.primary)),
            const SizedBox(height: 22),
            Text('البحث عن فاتورة',
              style: GoogleFonts.cairo(
                fontSize: 20, fontWeight: FontWeight.w700,
                color: AppColors.textPrimary)),
            const SizedBox(height: 6),
            Text('أدخل رقم الفاتورة للبدء بالإرجاع',
              style: GoogleFonts.cairo(
                fontSize: 13, color: AppColors.textSecondary)),
            const SizedBox(height: 28),
            TextField(
              controller: _saleIdCtrl,
              keyboardType: TextInputType.number,
              textAlign: TextAlign.center,
              style: GoogleFonts.cairo(
                fontSize: 26, fontWeight: FontWeight.w800,
                letterSpacing: 2),
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              onSubmitted: (_) => _search(),
              decoration: const InputDecoration(
                hintText: '١٢٣٤',
                contentPadding: EdgeInsets.symmetric(
                  horizontal: 20, vertical: 18)),
            ),
            if (_error != null) ...[
              const SizedBox(height: 14),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 14, vertical: 10),
                decoration: BoxDecoration(
                  color: AppColors.errorBg,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: AppColors.error.withValues(alpha: 0.25))),
                child: Row(children: [
                  const Icon(Icons.error_outline_rounded,
                    color: AppColors.error, size: 18),
                  const SizedBox(width: 8),
                  Expanded(child: Text(_error!,
                    style: GoogleFonts.cairo(
                      color: AppColors.error, fontSize: 13))),
                ])),
            ],
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: AppPrimaryButton(
                label: 'بحث',
                icon: Icons.search_rounded,
                onPressed: _search,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ── Return Form ───────────────────────────────────────────────────────────────
  Widget _buildForm() {
    return Column(children: [
      // Invoice header
      Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        decoration: BoxDecoration(
          color: AppColors.primarySurface,
          border: Border(
            bottom: BorderSide(color: AppColors.border))),
        child: Row(children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.10),
              borderRadius: BorderRadius.circular(10)),
            child: const Icon(Icons.receipt_rounded,
              color: AppColors.primary, size: 20)),
          const SizedBox(width: 12),
          Expanded(child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'فاتورة #${_saleData!['invoice_number'] ?? ''}',
                style: GoogleFonts.cairo(
                  fontWeight: FontWeight.w700, fontSize: 14,
                  color: AppColors.textPrimary)),
              Text(
                'العميل: ${_saleData!['customer_name'] ?? '—'}',
                style: GoogleFonts.cairo(
                  fontSize: 12, color: AppColors.textSecondary)),
            ],
          )),
          GestureDetector(
            onTap: _reset,
            child: Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: AppColors.bgSecondary,
                borderRadius: BorderRadius.circular(8)),
              child: const Icon(Icons.close_rounded,
                size: 18, color: AppColors.textSecondary))),
        ]),
      ),

      // Items
      Expanded(
        child: ListView.builder(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 12),
          itemCount: _items.length,
          itemBuilder: (_, i) => _buildItemCard(_items[i], i),
        ),
      ),

      // Bottom
      _buildBottom(),
    ]);
  }

  Widget _buildItemCard(_SaleItemReturn item, int index) {
    return AppCards.modern(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Expanded(child: Text(item.productName,
              style: GoogleFonts.cairo(
                fontWeight: FontWeight.w700, fontSize: 14,
                color: AppColors.textPrimary),
              maxLines: 1, overflow: TextOverflow.ellipsis)),
            Text('${_fmt.format(item.unitPrice)} د.ع',
              style: GoogleFonts.cairo(
                fontSize: 12, color: AppColors.textSecondary)),
          ]),
          const SizedBox(height: 10),
          Row(children: [
            AppBadge(
              label: 'مباع: ${item.originalQuantity}',
              color: AppColors.textSecondary),
            const Spacer(),
            // Qty stepper
            Container(
              decoration: BoxDecoration(
                color: AppColors.bgSecondary,
                borderRadius: BorderRadius.circular(12)),
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                _stepBtn(
                  icon: Icons.remove_rounded,
                  enabled: item.returnQty > 0,
                  onTap: () => setState(
                    () => _items[index].returnQty--),
                ),
                Container(
                  constraints: const BoxConstraints(minWidth: 36),
                  alignment: Alignment.center,
                  child: Text('${item.returnQty}',
                    style: GoogleFonts.cairo(
                      fontSize: 15, fontWeight: FontWeight.w700,
                      color: item.returnQty > 0
                        ? AppColors.primary : AppColors.textTertiary))),
                _stepBtn(
                  icon: Icons.add_rounded,
                  enabled: item.returnQty < item.originalQuantity,
                  onTap: () => setState(
                    () => _items[index].returnQty++),
                ),
              ]),
            ),
          ]),
          if (item.returnQty > 0) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(
                horizontal: 10, vertical: 5),
              decoration: BoxDecoration(
                color: AppColors.errorBg,
                borderRadius: BorderRadius.circular(8)),
              child: Text(
                'مسترد: ${_fmt.format(item.returnQty * item.unitPrice)} د.ع',
                style: GoogleFonts.cairo(
                  color: AppColors.error, fontSize: 12,
                  fontWeight: FontWeight.w600))),
          ],
        ],
      ),
    );
  }

  Widget _stepBtn({
    required IconData icon,
    required bool enabled,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: enabled ? onTap : null,
      child: Padding(
        padding: const EdgeInsets.all(9),
        child: Icon(icon,
          size: 19,
          color: enabled ? AppColors.primary : AppColors.textTertiary)),
    );
  }

  // ── Bottom ────────────────────────────────────────────────────────────────────
  Widget _buildBottom() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.card,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.07),
            blurRadius: 20, offset: const Offset(0, -4))],
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24))),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: _reasonCtrl,
              decoration: const InputDecoration(
                hintText: 'سبب الإرجاع (اختياري)',
                prefixIcon: Icon(Icons.notes_rounded)),
            ),
            const SizedBox(height: 14),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('المبلغ المسترد',
                  style: GoogleFonts.cairo(
                    fontSize: 14, color: AppColors.textSecondary)),
                Text(
                  '${_fmt.format(_totalRefund)} د.ع',
                  style: GoogleFonts.cairo(
                    fontSize: 22, fontWeight: FontWeight.w800,
                    letterSpacing: -0.4,
                    color: _totalRefund > 0
                        ? AppColors.error : AppColors.textTertiary)),
              ],
            ),
            if (_error != null) ...[
              const SizedBox(height: 6),
              Text(_error!,
                style: GoogleFonts.cairo(
                  color: AppColors.error, fontSize: 12)),
            ],
            const SizedBox(height: 14),
            SizedBox(
              width: double.infinity,
              child: AppPrimaryButton(
                label: _submitting ? 'جاري المعالجة...' : 'معالجة الإرجاع',
                icon: Icons.undo_rounded,
                onPressed: _totalRefund > 0 && !_submitting ? _submit : null,
                loading: _submitting,
                color: AppColors.error,
                gradient: _totalRefund > 0
                    ? const LinearGradient(
                        colors: [Color(0xFFEF4444), Color(0xFFF87171)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight)
                    : null,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Data model ────────────────────────────────────────────────────────────────
class _SaleItemReturn {
  final int productId;
  final String productName;
  final int originalQuantity;
  final double unitPrice;
  int returnQty;

  _SaleItemReturn({
    required this.productId,
    required this.productName,
    required this.originalQuantity,
    required this.unitPrice,
    this.returnQty = 0,
  });
}
