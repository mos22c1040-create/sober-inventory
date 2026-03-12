import 'package:flutter/material.dart';

import '../api/api_client.dart';
import '../models/product.dart';

class ProductsScreen extends StatefulWidget {
  final ApiClient api;

  const ProductsScreen({super.key, required this.api});

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  bool _loading = true;
  List<Product> _products = <Product>[];
  String? _error;
  final TextEditingController _barcodeCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadProducts();
  }

  Future<void> _loadProducts() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final res = await widget.api.fetchProducts(page: 1);
      final List list = (res['data'] ?? <dynamic>[]) as List;
      setState(() {
        _products =
            list.map((e) => Product.fromJson(Map<String, dynamic>.from(e as Map))).toList();
      });
    } catch (_) {
      setState(() {
        _error = 'فشل تحميل المنتجات';
      });
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _searchByBarcode() async {
    final sku = _barcodeCtrl.text.trim();
    if (sku.isEmpty) return;

    setState(() {
      _error = null;
    });

    try {
      final res = await widget.api.findByBarcode(sku);
      if (res['success'] == true && res['product'] != null) {
        final product =
            Product.fromJson(Map<String, dynamic>.from(res['product'] as Map));
        if (!mounted) return;
        showDialog<void>(
          context: context,
          builder: (_) => AlertDialog(
            title: Text(product.name),
            content: Text(
              'SKU: ${product.sku ?? '-'}\n'
              'الكمية: ${product.quantity}\n'
              'السعر: ${product.price}',
            ),
          ),
        );
      } else {
        setState(() {
          _error = (res['error'] ?? 'لم يُعثر على منتج بهذا الرمز').toString();
        });
      }
    } catch (_) {
      setState(() {
        _error = 'تعذر الاتصال بالخادم';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('المنتجات')),
      body: Column(
        children: <Widget>[
          Padding(
            padding: const EdgeInsets.all(8),
            child: Row(
              children: <Widget>[
                Expanded(
                  child: TextField(
                    controller: _barcodeCtrl,
                    decoration: const InputDecoration(
                      labelText: 'بحث بالباركود / SKU',
                    ),
                    onSubmitted: (_) => _searchByBarcode(),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.search),
                  onPressed: _searchByBarcode,
                ),
              ],
            ),
          ),
          if (_error != null)
            Padding(
              padding: const EdgeInsets.all(8),
              child: Text(
                _error!,
                style: const TextStyle(color: Colors.red),
              ),
            ),
          Expanded(
            child: _loading
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: _loadProducts,
                    child: ListView.builder(
                      itemCount: _products.length,
                      itemBuilder: (BuildContext context, int index) {
                        final p = _products[index];
                        return ListTile(
                          title: Text(p.name),
                          subtitle: Text(
                            'SKU: ${p.sku ?? '-'} | الكمية: ${p.quantity}',
                          ),
                          trailing: Text(p.price.toString()),
                        );
                      },
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}

