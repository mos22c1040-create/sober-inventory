import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../models/product.dart';
import '../utils/api_parse.dart';

class PurchasesScreen extends StatefulWidget {
  const PurchasesScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<PurchasesScreen> createState() => _PurchasesScreenState();
}

class _PurchasesScreenState extends State<PurchasesScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _items = [];
  String? _error;
  String _csrfToken = '';

  @override
  void initState() {
    super.initState();
    _load();
    _loadCsrf();
  }

  Future<void> _loadCsrf() async {
    try {
      final r = await widget.api.getMe();
      if (mounted) setState(() => _csrfToken = (r['csrf_token'] ?? '').toString());
    } catch (_) {}
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final r = await widget.api.getPurchases();
      if (mounted) {
        setState(() {
          _items = (r['data'] as List? ?? [])
              .map((e) => Map<String, dynamic>.from(e as Map))
              .toList();
        });
      }
    } catch (_) {
      if (mounted) setState(() => _error = 'فشل تحميل المشتريات');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _showForm() {
    Navigator.of(context).push(MaterialPageRoute<void>(
      builder: (_) => _NewPurchaseScreen(
        api: widget.api,
        csrfToken: _csrfToken,
        onSaved: () { Navigator.pop(context); _load(); },
      ),
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: const Text('المشتريات'),
          backgroundColor: AppColors.bg,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_rounded),
            onPressed: () => Navigator.pop(context),
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.add_circle_rounded, color: AppColors.primary, size: 28),
              onPressed: () => _showForm(),
            ),
          ],
        ),
        body: RefreshIndicator(
          color: AppColors.primary,
          onRefresh: _load,
          child: _loading
              ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
              : _items.isEmpty
                  ? const Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.shopping_bag_outlined, size: 56, color: AppColors.textHint),
                          SizedBox(height: 16),
                          Text('لا توجد مشتريات', style: TextStyle(color: AppColors.textSecondary, fontWeight: FontWeight.w600)),
                        ],
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                      itemCount: _items.length,
                      itemBuilder: (ctx, i) {
                        final p = _items[i];
                        return Container(
                          margin: const EdgeInsets.only(bottom: 10),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: AppColors.card,
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8)],
                          ),
                          child: Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(10),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF0EBFF),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: const Icon(Icons.shopping_bag_rounded, color: Color(0xFF7C3AED), size: 22),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      (p['supplier'] ?? 'بدون مورد').toString(),
                                      style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      (p['created_at'] ?? '').toString().substring(0, 10),
                                      style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
                                    ),
                                  ],
                                ),
                              ),
                              Text(
                                '${toDouble(p['total']).toStringAsFixed(0)} د.ع',
                                style: const TextStyle(
                                  fontWeight: FontWeight.w800,
                                  color: Color(0xFF7C3AED),
                                  fontSize: 14,
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
        ),
      ),
    );
  }
}

class _NewPurchaseScreen extends StatefulWidget {
  const _NewPurchaseScreen({
    required this.api,
    required this.csrfToken,
    required this.onSaved,
  });

  final ApiClient api;
  final String csrfToken;
  final VoidCallback onSaved;

  @override
  State<_NewPurchaseScreen> createState() => _NewPurchaseScreenState();
}

class _NewPurchaseScreenState extends State<_NewPurchaseScreen> {
  final _supplierCtrl = TextEditingController();
  final _searchCtrl = TextEditingController();
  List<Product> _products = [];
  final List<Map<String, dynamic>> _cart = [];
  bool _loadingProducts = true;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _loadProducts();
  }

  Future<void> _loadProducts({String q = ''}) async {
    setState(() => _loadingProducts = true);
    try {
      final r = await widget.api.getPosProducts(search: q);
      if (mounted) {
        setState(() {
          _products = (r['products'] as List? ?? [])
              .map((e) => Product.fromJson(Map<String, dynamic>.from(e as Map)))
              .toList();
        });
      }
    } catch (_) {} finally {
      if (mounted) setState(() => _loadingProducts = false);
    }
  }

  void _addProduct(Product p) {
    setState(() {
      final i = _cart.indexWhere((e) => e['product_id'] == p.id);
      if (i >= 0) {
        _cart[i]['quantity'] = toInt(_cart[i]['quantity']) + 1;
      } else {
        _cart.add({'product_id': p.id, 'quantity': 1, 'unit_cost': p.price, 'name': p.name});
      }
    });
  }

  Future<void> _save() async {
    if (_cart.isEmpty) return;
    setState(() => _saving = true);
    try {
      final items = _cart.map<Map<String, dynamic>>((e) => {
        'product_id': e['product_id'],
        'quantity': e['quantity'],
        'unit_cost': e['unit_cost'],
      }).toList();
      final r = await widget.api.createPurchase(
        items: items,
        supplier: _supplierCtrl.text.trim(),
        csrfToken: widget.csrfToken,
      );
      if (r['success'] == true) widget.onSaved();
    } catch (_) {} finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: const Text('طلب شراء جديد'),
          backgroundColor: AppColors.bg,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_rounded),
            onPressed: () => Navigator.pop(context),
          ),
        ),
        body: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
              child: TextField(
                controller: _supplierCtrl,
                decoration: InputDecoration(
                  hintText: 'اسم المورد (اختياري)',
                  prefixIcon: const Icon(Icons.business_rounded),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
              child: TextField(
                controller: _searchCtrl,
                decoration: InputDecoration(
                  hintText: 'بحث عن منتج...',
                  prefixIcon: const Icon(Icons.search_rounded, color: AppColors.primary),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
                onSubmitted: (v) => _loadProducts(q: v),
              ),
            ),
            Expanded(
              child: _loadingProducts
                  ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
                  : ListView.builder(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      itemCount: _products.length,
                      itemBuilder: (ctx, i) {
                        final p = _products[i];
                        final inCart = _cart.indexWhere((e) => e['product_id'] == p.id);
                        return ListTile(
                          title: Text(p.name),
                          subtitle: Text('متوفر: ${p.quantity}'),
                          trailing: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (inCart >= 0)
                                Text('${_cart[inCart]['quantity']}',
                                    style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.primary)),
                              const SizedBox(width: 8),
                              IconButton(
                                icon: const Icon(Icons.add_circle, color: AppColors.primary),
                                onPressed: () => _addProduct(p),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
            ),
            if (_cart.isNotEmpty)
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppColors.card,
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                  boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 16)],
                ),
                child: Column(
                  children: [
                    Text('${_cart.length} صنف في الطلب', style: const TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      height: 50,
                      child: ElevatedButton(
                        onPressed: _saving ? null : _save,
                        child: _saving
                            ? const CircularProgressIndicator(strokeWidth: 2, color: Colors.white)
                            : const Text('تأكيد الطلب'),
                      ),
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}
