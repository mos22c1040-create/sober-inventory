import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show FilteringTextInputFormatter;
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';

class PurchaseScreen extends StatefulWidget {
  const PurchaseScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<PurchaseScreen> createState() => _PurchaseScreenState();
}

class _PurchaseScreenState extends State<PurchaseScreen> {
  final _searchCtrl   = TextEditingController();
  final _supplierCtrl = TextEditingController();
  final _fmt          = NumberFormat('#,##0');

  List<Map<String, dynamic>> _products  = [];
  List<_PurchaseItem> _items            = [];
  bool _loading    = false;
  bool _submitting = false;
  String? _error;
  Map<String, dynamic>? _selected;

  @override
  void initState() { super.initState(); _loadProducts(); }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _supplierCtrl.dispose();
    super.dispose();
  }

  // ── Logic ────────────────────────────────────────────────────────────────────
  Future<void> _loadProducts() async {
    setState(() => _loading = true);
    try {
      final r = await widget.api.fetchProducts(page: 1, perPage: 1000);
      setState(() => _products =
          List<Map<String, dynamic>>.from(r['data'] ?? []));
    } catch (_) {}
    finally { setState(() => _loading = false); }
  }

  void _addToList() {
    if (_selected == null) { _snack('الرجاء اختيار منتج', isError: true); return; }
    final id = _selected!['id'] as int;
    if (_items.any((e) => e.productId == id)) {
      _snack('المنتج موجود بالفعل في القائمة', isError: true); return;
    }
    setState(() {
      _items.add(_PurchaseItem(
        productId: id,
        productName: _selected!['name'] as String? ?? 'منتج',
        quantity: 1,
        unitCost: (_selected!['cost'] as num?)?.toDouble() ?? 0.0,
      ));
      _searchCtrl.clear();
      _selected = null;
    });
  }

  double get _total =>
      _items.fold(0.0, (s, e) => s + e.quantity * e.unitCost);

  Future<void> _submit() async {
    if (_items.isEmpty) { _snack('أضف منتجات للقائمة', isError: true); return; }
    setState(() { _submitting = true; _error = null; });
    try {
      await widget.api.submitPurchase(
        items: _items.map((e) => {
          'product_id': e.productId,
          'quantity': e.quantity,
          'unit_cost': e.unitCost,
        }).toList(),
        supplier: _supplierCtrl.text.trim(),
      );
      if (mounted) {
        _snack('تم تحديث المخزون بنجاح');
        setState(() { _items.clear(); _supplierCtrl.clear(); });
      }
    } catch (e) {
      setState(() => _error = 'فشل حفظ طلب الشراء. تحقق من البيانات.');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
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

  // ── Build ────────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: Text('شراء بضاعة',
            style: GoogleFonts.cairo(
              fontWeight: FontWeight.w700, fontSize: 17)),
          backgroundColor: AppColors.bg,
          elevation: 0,
          scrolledUnderElevation: 0.5,
        ),
        body: _loading
            ? const Center(
                child: CircularProgressIndicator(color: AppColors.primary))
            : Column(children: [
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildSelector(),
                        const SizedBox(height: 24),
                        _buildList(),
                      ],
                    ),
                  ),
                ),
                _buildBottom(),
              ]),
      ),
    );
  }

  // ── Product Selector ──────────────────────────────────────────────────────────
  Widget _buildSelector() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('اختيار المنتج',
          style: GoogleFonts.cairo(
            fontSize: 15, fontWeight: FontWeight.w700,
            color: AppColors.textPrimary)),
        const SizedBox(height: 12),

        Autocomplete<Map<String, dynamic>>(
          optionsBuilder: (tv) {
            if (tv.text.isEmpty) return const [];
            final q = tv.text.toLowerCase();
            return _products.where((p) {
              final name = (p['name'] as String? ?? '').toLowerCase();
              final sku  = (p['sku']  as String? ?? '').toLowerCase();
              return name.contains(q) || sku.contains(q);
            });
          },
          displayStringForOption: (p) => p['name'] as String? ?? 'منتج',
          fieldViewBuilder: (_, ctrl, fn, _onSub) {
            return TextField(
              controller: ctrl,
              focusNode: fn,
              decoration: InputDecoration(
                hintText: 'ابحث عن منتج بالاسم أو الرمز',
                prefixIcon: const Icon(Icons.search_rounded,
                  color: AppColors.textSecondary),
              ),
            );
          },
          optionsViewBuilder: (_, onSel, options) => Align(
            alignment: Alignment.topCenter,
            child: Material(
              color: AppColors.card,
              elevation: 0,
              borderRadius: BorderRadius.circular(16),
              child: Container(
                constraints: const BoxConstraints(maxHeight: 200),
                decoration: BoxDecoration(
                  color: AppColors.card,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.border)),
                child: ListView.separated(
                  padding: const EdgeInsets.all(8),
                  shrinkWrap: true,
                  separatorBuilder: (_, __) => const Divider(height: 1),
                  itemCount: options.length,
                  itemBuilder: (_, i) {
                    final p = options.elementAt(i);
                    return ListTile(
                      title: Text(p['name'] ?? '',
                        style: GoogleFonts.cairo(
                          fontWeight: FontWeight.w600, fontSize: 14,
                          color: AppColors.textPrimary)),
                      subtitle: Text(
                        'تكلفة: ${_fmt.format((p['cost'] as num?)?.toDouble() ?? 0)}  |  مخزون: ${p['quantity'] ?? 0}',
                        style: GoogleFonts.cairo(
                          fontSize: 11, color: AppColors.textSecondary)),
                      onTap: () {
                        onSel(p);
                        setState(() => _selected = p);
                      },
                    );
                  },
                ),
              ),
            ),
          ),
          onSelected: (p) => setState(() => _selected = p),
        ),

        const SizedBox(height: 12),

        SizedBox(
          width: double.infinity,
          child: AppPrimaryButton(
            label: 'إضافة للقائمة',
            icon: Icons.add_shopping_cart_rounded,
            onPressed: _selected == null ? null : _addToList,
          ),
        ),
      ],
    );
  }

  // ── Items List ────────────────────────────────────────────────────────────────
  Widget _buildList() {
    if (_items.isEmpty) {
      return EmptyState(
        icon: Icons.shopping_bag_outlined,
        title: 'القائمة فارغة',
        subtitle: 'أضف منتجات من الأعلى',
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text('قائمة الشراء',
              style: GoogleFonts.cairo(
                fontSize: 15, fontWeight: FontWeight.w700,
                color: AppColors.textPrimary)),
            AppBadge(
              label: '${_items.length} منتج',
              color: AppColors.primary),
          ],
        ),
        const SizedBox(height: 12),
        ..._items.asMap().entries.map((entry) =>
            _buildItemCard(entry.value, entry.key)),
      ],
    );
  }

  Widget _buildItemCard(_PurchaseItem item, int index) {
    return AppCards.modern(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Expanded(
              child: Text(item.productName,
                style: GoogleFonts.cairo(
                  fontWeight: FontWeight.w700, fontSize: 14,
                  color: AppColors.textPrimary),
                maxLines: 1, overflow: TextOverflow.ellipsis)),
            GestureDetector(
              onTap: () => setState(() => _items.removeAt(index)),
              child: Container(
                padding: const EdgeInsets.all(5),
                decoration: BoxDecoration(
                  color: AppColors.errorBg,
                  borderRadius: BorderRadius.circular(8)),
                child: const Icon(Icons.delete_outline_rounded,
                  color: AppColors.error, size: 16))),
          ]),
          const SizedBox(height: 12),
          Row(children: [
            Expanded(child: _numField(
              label: 'الكمية',
              value: '${item.quantity}',
              digits: true,
              onChanged: (v) {
                final q = int.tryParse(v) ?? 1;
                if (q > 0) setState(() => _items[index].quantity = q);
              },
            )),
            const SizedBox(width: 12),
            Expanded(child: _numField(
              label: 'تكلفة الوحدة',
              value: item.unitCost.toStringAsFixed(0),
              digits: false,
              onChanged: (v) {
                final c = double.tryParse(v) ?? 0.0;
                setState(() => _items[index].unitCost = c);
              },
            )),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text('المجموع',
                  style: GoogleFonts.cairo(
                    fontSize: 11, color: AppColors.textSecondary)),
                const SizedBox(height: 4),
                Text(_fmt.format(item.quantity * item.unitCost),
                  style: GoogleFonts.cairo(
                    fontSize: 14, fontWeight: FontWeight.w700,
                    color: AppColors.textPrimary)),
              ],
            ),
          ]),
        ],
      ),
    );
  }

  Widget _numField({
    required String label,
    required String value,
    required bool digits,
    required ValueChanged<String> onChanged,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
          style: GoogleFonts.cairo(
            fontSize: 11, color: AppColors.textSecondary)),
        const SizedBox(height: 4),
        SizedBox(
          height: 44,
          child: TextField(
            controller: TextEditingController(text: value),
            keyboardType: digits
                ? TextInputType.number
                : const TextInputType.numberWithOptions(decimal: true),
            textAlign: TextAlign.center,
            style: GoogleFonts.cairo(
              fontWeight: FontWeight.w700, fontSize: 14),
            inputFormatters: digits
                ? [FilteringTextInputFormatter.digitsOnly]
                : [FilteringTextInputFormatter.allow(
                    RegExp(r'^\d*\.?\d{0,2}'))],
            onChanged: onChanged,
            decoration: InputDecoration(
              isDense: true,
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 8, vertical: 10),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
                borderSide: const BorderSide(color: AppColors.border)),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
                borderSide: const BorderSide(
                  color: AppColors.primary, width: 2)),
            ),
          ),
        ),
      ],
    );
  }

  // ── Bottom Section ────────────────────────────────────────────────────────────
  Widget _buildBottom() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.card,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.07),
            blurRadius: 20, offset: const Offset(0, -4))],
        borderRadius: const BorderRadius.vertical(
          top: Radius.circular(24))),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Supplier field
            TextField(
              controller: _supplierCtrl,
              decoration: const InputDecoration(
                hintText: 'المورد (اختياري)',
                prefixIcon: Icon(Icons.business_rounded)),
            ),
            const SizedBox(height: 14),
            // Total row
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('إجمالي التكلفة',
                  style: GoogleFonts.cairo(
                    fontSize: 14, color: AppColors.textSecondary)),
                Text('${_fmt.format(_total)} د.ع',
                  style: GoogleFonts.cairo(
                    fontSize: 22, fontWeight: FontWeight.w800,
                    color: AppColors.primary, letterSpacing: -0.4)),
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
                label: _submitting ? 'جاري الحفظ...' : 'إرسال طلب الشراء',
                icon: Icons.shopping_cart_checkout_rounded,
                onPressed: _items.isNotEmpty && !_submitting ? _submit : null,
                loading: _submitting,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Data model ────────────────────────────────────────────────────────────────
class _PurchaseItem {
  final int productId;
  final String productName;
  int quantity;
  double unitCost;

  _PurchaseItem({
    required this.productId,
    required this.productName,
    required this.quantity,
    required this.unitCost,
  });
}
