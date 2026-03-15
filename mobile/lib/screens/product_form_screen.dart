import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../api/api_client.dart';
import '../models/product.dart';
import '../theme/app_theme.dart';
import '../utils/api_parse.dart';
import '../widgets/product_image_file.dart';
import 'barcode_scanner_screen.dart';

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
  List<Map<String, dynamic>> _types = [];
  int? _selectedTypeId;

  /// Path to the picked product image (camera or gallery).
  String? _productImagePath;
  final ImagePicker _imagePicker = ImagePicker();

  @override
  void initState() {
    super.initState();
    final p = widget.product;
    if (p != null) {
      _nameCtrl.text = p.name;
      _priceCtrl.text = p.price.toString();
      _qtyCtrl.text = p.quantity.toString();
      _skuCtrl.text = p.sku ?? '';
      _selectedTypeId = p.typeId;
    }
    _thresholdCtrl.text = '5';
    _loadTypes();
  }

  Future<void> _loadTypes() async {
    try {
      final list = await widget.api.getTypes();
      if (mounted) setState(() => _types = list);
    } catch (_) {}
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

  Future<void> _scanBarcode() async {
    final code = await Navigator.of(context).push<String>(
      MaterialPageRoute<String>(
        builder: (_) => const BarcodeScannerScreen(title: 'مسح الباركود'),
      ),
    );
    if (code != null && code.isNotEmpty && mounted) {
      _skuCtrl.text = code;
    }
  }

  void _showImageSourceSheet() {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'اختر مصدر الصورة',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 20),
              ListTile(
                leading: const Icon(Icons.camera_alt_rounded, color: AppColors.primary),
                title: const Text('الكاميرا'),
                onTap: () { Navigator.pop(ctx); _pickImage(ImageSource.camera); },
              ),
              ListTile(
                leading: const Icon(Icons.photo_library_rounded, color: AppColors.primary),
                title: const Text('المعرض'),
                onTap: () { Navigator.pop(ctx); _pickImage(ImageSource.gallery); },
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final XFile? xFile = await _imagePicker.pickImage(
        source: source,
        maxWidth: 1200,
        imageQuality: 90,
      );
      if (xFile == null || !mounted) return;
      setState(() => _productImagePath = xFile.path);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text('تعذر فتح الكاميرا أو المعرض'),
            backgroundColor: AppColors.error,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            margin: const EdgeInsets.all(16),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.product != null;
    return Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: Text(isEdit ? 'تعديل المنتج' : 'إضافة منتج جديد'),
          backgroundColor: AppColors.bg,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_rounded),
            onPressed: () => Navigator.pop(context),
          ),
        ),
        body: Stack(
          children: [
            SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Product image
                  _buildProductImageSection(),
                  const SizedBox(height: 24),
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
                    _buildTypeDropdown(),
                    const SizedBox(height: 14),
                    _barcodeField(),
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
          ],
        ),
    );
  }

  Widget _buildProductImageSection() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              const Icon(Icons.image_rounded, color: AppColors.primary, size: 22),
              const SizedBox(width: 8),
              Text(
                'صورة المنتج',
                style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
              ),
            ],
          ),
          const SizedBox(height: 14),
          GestureDetector(
            onTap: _showImageSourceSheet,
            child: Container(
              height: 180,
              decoration: BoxDecoration(
                color: AppColors.bgSecondary,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.border),
              ),
              clipBehavior: Clip.antiAlias,
              child: _productImagePath != null
                  ? SizedBox.expand(child: productImageFile(_productImagePath!))
                  : Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.add_photo_alternate_outlined, size: 48, color: AppColors.textTertiary),
                          const SizedBox(height: 8),
                          Text(
                            'اضغط لتحميل صورة',
                            style: TextStyle(fontSize: 13, color: AppColors.textSecondary),
                          ),
                        ],
                      ),
                    ),
            ),
          ),
          const SizedBox(height: 10),
          SizedBox(
            height: 44,
            child: OutlinedButton.icon(
              onPressed: _showImageSourceSheet,
              icon: const Icon(Icons.upload_rounded, size: 20),
              label: const Text('تحميل صورة'),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.primary,
                side: const BorderSide(color: AppColors.primary),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTypeDropdown() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.only(bottom: 8),
          child: Text(
            'النوع',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: AppColors.textSecondary,
            ),
          ),
        ),
        DropdownButtonFormField<int?>(
          value: _selectedTypeId,
          decoration: InputDecoration(
            prefixIcon: const Icon(Icons.layers_rounded, size: 20),
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          ),
          hint: const Text('— لا يوجد —'),
          items: [
            const DropdownMenuItem<int?>(value: null, child: Text('— لا يوجد —')),
            ..._types.map((t) => DropdownMenuItem<int?>(
              value: toInt(t['id']),
              child: Text((t['name'] ?? '').toString()),
            )),
          ],
          onChanged: (v) => setState(() => _selectedTypeId = v),
        ),
      ],
    );
  }

  Widget _barcodeField() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.only(bottom: 8),
          child: Text(
            'رمز المنتج / الباركود (SKU)',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: AppColors.textSecondary,
            ),
          ),
        ),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _skuCtrl,
                decoration: InputDecoration(
                  hintText: 'أدخل الباركود أو الرمز للبحث والمسح',
                  prefixIcon: const Icon(Icons.qr_code_rounded, size: 20),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                ),
              ),
            ),
            const SizedBox(width: 10),
            Container(
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.3),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: IconButton(
                icon: const Icon(Icons.qr_code_scanner_rounded, color: Colors.white, size: 26),
                onPressed: _scanBarcode,
                tooltip: 'قراءة الباركود',
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _field(
    TextEditingController ctrl,
    String label,
    IconData icon, {
    bool isNum = false,
    bool isInt = false,
    String? hint,
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
        hintText: hint,
        prefixIcon: Icon(icon, size: 20),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
    );
  }
}
