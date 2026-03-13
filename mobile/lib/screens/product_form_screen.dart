import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../models/product.dart';

class ProductFormScreen extends StatefulWidget {
  const ProductFormScreen({
    super.key,
    required this.api,
    required this.csrfToken,
    required this.onSaved,
    this.product,
  });

  final ApiClient api;
  final String csrfToken;
  final Product? product;
  final VoidCallback onSaved;

  @override
  State<ProductFormScreen> createState() => _ProductFormScreenState();
}

class _ProductFormScreenState extends State<ProductFormScreen> {
  final _nameCtrl = TextEditingController();
  final _priceCtrl = TextEditingController();
  final _costCtrl = TextEditingController();
  final _qtyCtrl = TextEditingController();
  final _skuCtrl = TextEditingController();
  final _thresholdCtrl = TextEditingController();
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final p = widget.product;
    if (p != null) {
      _nameCtrl.text = p.name;
      _priceCtrl.text = p.price.toString();
      _qtyCtrl.text = p.quantity.toString();
      _skuCtrl.text = p.sku ?? '';
    }
    _thresholdCtrl.text = '5';
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _priceCtrl.dispose();
    _costCtrl.dispose();
    _qtyCtrl.dispose();
    _skuCtrl.dispose();
    _thresholdCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final name = _nameCtrl.text.trim();
    final price = double.tryParse(_priceCtrl.text.trim()) ?? 0;
    final qty = int.tryParse(_qtyCtrl.text.trim()) ?? 0;

    if (name.isEmpty || price <= 0) return;
    setState(() => _saving = true);

    try {
      final cost = double.tryParse(_costCtrl.text.trim());
      final threshold = int.tryParse(_thresholdCtrl.text.trim()) ?? 5;
      final sku = _skuCtrl.text.trim().isEmpty ? null : _skuCtrl.text.trim();

      final Map<String, dynamic> r;
      if (widget.product != null) {
        r = await widget.api.updateProduct(
          id: widget.product!.id,
          name: name,
          price: price,
          quantity: qty,
          sku: sku,
          cost: cost,
          lowStockThreshold: threshold,
          csrfToken: widget.csrfToken,
        );
      } else {
        r = await widget.api.createProduct(
          name: name,
          price: price,
          quantity: qty,
          sku: sku,
          cost: cost,
          lowStockThreshold: threshold,
          csrfToken: widget.csrfToken,
        );
      }

      if (r['success'] == true || r['id'] != null) {
        widget.onSaved();
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text((r['error'] ?? 'حدث خطأ').toString()),
            backgroundColor: AppColors.error,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            margin: const EdgeInsets.all(16),
          ));
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
          content: Text('تعذر الاتصال بالخادم'),
          backgroundColor: AppColors.error,
          behavior: SnackBarBehavior.floating,
        ));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.product != null;
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: Text(isEdit ? 'تعديل المنتج' : 'إضافة منتج جديد'),
          backgroundColor: AppColors.bg,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_rounded),
            onPressed: () => Navigator.pop(context),
          ),
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Header banner
              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [AppColors.primary, AppColors.primaryDark],
                  ),
                  borderRadius: BorderRadius.circular(18),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.primary.withValues(alpha: 0.3),
                      blurRadius: 16,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    const Icon(Icons.inventory_2_rounded, color: Colors.white, size: 32),
                    const SizedBox(width: 14),
                    Text(
                      isEdit ? 'تعديل بيانات المنتج' : 'إضافة منتج جديد',
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 16),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              // Form card
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppColors.card,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12)],
                ),
                child: Column(
                  children: [
                    _field(_nameCtrl, 'اسم المنتج *', Icons.label_rounded),
                    const SizedBox(height: 14),
                    Row(
                      children: [
                        Expanded(child: _field(_priceCtrl, 'السعر *', Icons.sell_rounded, isNum: true)),
                        const SizedBox(width: 12),
                        Expanded(child: _field(_costCtrl, 'التكلفة', Icons.price_change_rounded, isNum: true)),
                      ],
                    ),
                    const SizedBox(height: 14),
                    Row(
                      children: [
                        Expanded(child: _field(_qtyCtrl, 'الكمية *', Icons.numbers_rounded, isInt: true)),
                        const SizedBox(width: 12),
                        Expanded(child: _field(_thresholdCtrl, 'حد التنبيه', Icons.warning_rounded, isInt: true)),
                      ],
                    ),
                    const SizedBox(height: 14),
                    _field(_skuCtrl, 'الباركود / SKU (اختياري)', Icons.qr_code_rounded),
                  ],
                ),
              ),
              const SizedBox(height: 24),

              SizedBox(
                height: 54,
                child: ElevatedButton.icon(
                  onPressed: _saving ? null : _save,
                  icon: _saving
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : Icon(isEdit ? Icons.save_rounded : Icons.add_circle_rounded),
                  label: Text(_saving ? 'جاري الحفظ...' : (isEdit ? 'تحديث المنتج' : 'إضافة المنتج')),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _field(
    TextEditingController ctrl,
    String label,
    IconData icon, {
    bool isNum = false,
    bool isInt = false,
  }) {
    return TextField(
      controller: ctrl,
      keyboardType: isInt
          ? TextInputType.number
          : isNum
              ? const TextInputType.numberWithOptions(decimal: true)
              : TextInputType.text,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, size: 20),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
    );
  }
}
