import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import '../api/api_client.dart';

class PurchaseScreen extends StatefulWidget {
  final ApiClient api;

  const PurchaseScreen({super.key, required this.api});

  @override
  State<PurchaseScreen> createState() => _PurchaseScreenState();
}

class _PurchaseScreenState extends State<PurchaseScreen> {
  final TextEditingController _searchController = TextEditingController();
  final TextEditingController _supplierController = TextEditingController();
  final NumberFormat _currencyFormat = NumberFormat('#,##0.00');

  List<Map<String, dynamic>> _products = [];
  List<PurchaseItem> _cartItems = [];
  bool _isLoading = false;
  bool _isSubmitting = false;
  String? _errorMessage;
  Map<String, dynamic>? _selectedProduct;

  @override
  void initState() {
    super.initState();
    _loadProducts();
  }

  @override
  void dispose() {
    _searchController.dispose();
    _supplierController.dispose();
    super.dispose();
  }

  Future<void> _loadProducts() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final products = await widget.api.fetchProducts(page: 1, perPage: 1000);
      setState(() {
        _products = List<Map<String, dynamic>>.from(products['data'] ?? []);
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _addToCart() {
    if (_selectedProduct == null) {
      _showError('الرجاء اختيار منتج');
      return;
    }

    final productId = _selectedProduct!['id'] as int;
    final existingIndex = _cartItems.indexWhere((item) => item.productId == productId);

    if (existingIndex >= 0) {
      _showError('المنتج موجود بالفعل في القائمة');
      return;
    }

    final productName = _selectedProduct!['name'] as String? ?? 'منتج';
    final currentCost = (_selectedProduct!['cost'] as num?)?.toDouble() ?? 0.0;

    setState(() {
      _cartItems.add(PurchaseItem(
        productId: productId,
        productName: productName,
        quantity: 1,
        unitCost: currentCost,
      ));
      _searchController.clear();
      _selectedProduct = null;
    });
  }

  void _updateQuantity(int index, int quantity) {
    if (quantity < 1) return;
    setState(() {
      _cartItems[index].quantity = quantity;
    });
  }

  void _updateUnitCost(int index, double cost) {
    if (cost < 0) return;
    setState(() {
      _cartItems[index].unitCost = cost;
    });
  }

  void _removeItem(int index) {
    setState(() {
      _cartItems.removeAt(index);
    });
  }

  double get _totalCost {
    return _cartItems.fold(0.0, (sum, item) => sum + (item.quantity * item.unitCost));
  }

  List<Map<String, dynamic>> get _payloadItems {
    return _cartItems.map((item) => {
      'product_id': item.productId,
      'quantity': item.quantity,
      'unit_cost': item.unitCost,
    }).toList();
  }

  Future<void> _submitPurchase() async {
    if (_cartItems.isEmpty) {
      _showError('الرجاء إضافة منتجات للقائمة');
      return;
    }

    setState(() {
      _isSubmitting = true;
      _errorMessage = null;
    });

    try {
      await widget.api.submitPurchase(
        items: _payloadItems,
        supplier: _supplierController.text.trim(),
      );

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text('تم تحديث المخزون بنجاح'),
            backgroundColor: Colors.green,
            behavior: SnackBarBehavior.floating,
          ),
        );
        _resetForm();
      }
    } catch (e) {
      setState(() {
        _isSubmitting = false;
        if (e.toString().contains('400') || e.toString().contains('422')) {
          _errorMessage = 'بيانات غير صالحة. تحقق من الكميات والتكاليف.';
        } else {
          _errorMessage = 'فشل حفظ طلب الشراء';
        }
      });
    }
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _resetForm() {
    setState(() {
      _cartItems = [];
      _supplierController.clear();
      _isSubmitting = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('شراء بضاعة'),
        centerTitle: true,
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Column(
              children: [
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _buildProductSelector(),
                        const SizedBox(height: 24),
                        _buildCartSection(),
                      ],
                    ),
                  ),
                ),
                _buildBottomSection(),
              ],
            ),
    );
  }

  Widget _buildProductSelector() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'اختيار المنتج',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 12),
        Autocomplete<Map<String, dynamic>>(
          optionsBuilder: (TextEditingValue textEditingValue) {
            if (textEditingValue.text.isEmpty) {
              return const Iterable<Map<String, dynamic>>.empty();
            }
            return _products.where((product) {
              final name = (product['name'] as String? ?? '').toLowerCase();
              final sku = (product['sku'] as String? ?? '').toLowerCase();
              final search = textEditingValue.text.toLowerCase();
              return name.contains(search) || sku.contains(search);
            });
          },
          displayStringForOption: (product) => product['name'] as String? ?? 'منتج',
          fieldViewBuilder: (context, controller, focusNode, onSubmitted) {
            _searchController.text = controller.text;
            return TextField(
              controller: controller,
              focusNode: focusNode,
              decoration: InputDecoration(
                hintText: 'ابحث عن منتج بالاسم أو الرمز',
                prefixIcon: const Icon(Icons.search),
                filled: true,
                fillColor: Colors.grey.shade100,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide.none,
                ),
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 14,
                ),
              ),
              onChanged: (value) {
                _searchController.text = value;
              },
            );
          },
          optionsViewBuilder: (context, onSelected, options) {
            return Align(
              alignment: Alignment.topCenter,
              child: Material(
                elevation: 4,
                borderRadius: BorderRadius.circular(12),
                child: Container(
                  constraints: const BoxConstraints(maxHeight: 200),
                  child: ListView.builder(
                    padding: EdgeInsets.zero,
                    shrinkWrap: true,
                    itemCount: options.length,
                    itemBuilder: (context, index) {
                      final product = options.elementAt(index);
                      return ListTile(
                        title: Text(product['name'] ?? ''),
                        subtitle: Text(
                          'التكلفة: ${_currencyFormat.format((product['cost'] as num?)?.toDouble() ?? 0.0)}',
                          style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                        ),
                        trailing: Text(
                          'المخزون: ${product['quantity'] ?? 0}',
                          style: TextStyle(color: Colors.grey.shade500, fontSize: 12),
                        ),
                        onTap: () {
                          onSelected(product);
                          setState(() {
                            _selectedProduct = product;
                          });
                        },
                      );
                    },
                  ),
                ),
              ),
            );
          },
          onSelected: (product) {
            setState(() {
              _selectedProduct = product;
            });
          },
        ),
        const SizedBox(height: 12),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: _addToCart,
            icon: const Icon(Icons.add_shopping_cart),
            label: const Text('إضافة للمنظومة'),
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildCartSection() {
    if (_cartItems.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(32),
        alignment: Alignment.center,
        child: Column(
          children: [
            Icon(
              Icons.shopping_bag_outlined,
              size: 64,
              color: Colors.grey.shade300,
            ),
            const SizedBox(height: 16),
            Text(
              'القائمة فارغة',
              style: TextStyle(
                color: Colors.grey.shade600,
                fontSize: 16,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'أضف منتجات من الأعلى',
              style: TextStyle(
                color: Colors.grey.shade400,
                fontSize: 13,
              ),
            ),
          ],
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              'قائمة الشراء',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            Text(
              '${_cartItems.length} منتج',
              style: TextStyle(
                color: Colors.grey.shade600,
                fontSize: 13,
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        ...List.generate(_cartItems.length, (index) {
          final item = _cartItems[index];
          return _buildCartItemCard(item, index);
        }),
      ],
    );
  }

  Widget _buildCartItemCard(PurchaseItem item, int index) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    item.productName,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 15,
                    ),
                  ),
                ),
                IconButton(
                  icon: Icon(Icons.delete_outline, color: Colors.red.shade400),
                  onPressed: () => _removeItem(index),
                  constraints: const BoxConstraints(),
                  padding: EdgeInsets.zero,
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'الكمية',
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey.shade600,
                        ),
                      ),
                      const SizedBox(height: 4),
                      TextField(
                        keyboardType: TextInputType.number,
                        textAlign: TextAlign.center,
                        decoration: InputDecoration(
                          isDense: true,
                          contentPadding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 10,
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                        inputFormatters: [
                          FilteringTextInputFormatter.digitsOnly,
                        ],
                        controller: TextEditingController(
                          text: item.quantity.toString(),
                        ),
                        onChanged: (value) {
                          final qty = int.tryParse(value) ?? 1;
                          _updateQuantity(index, qty);
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'تكلفة الوحدة',
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey.shade600,
                        ),
                      ),
                      const SizedBox(height: 4),
                      TextField(
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        textAlign: TextAlign.center,
                        decoration: InputDecoration(
                          isDense: true,
                          contentPadding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 10,
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                        ),
                        inputFormatters: [
                          FilteringTextInputFormatter.allow(RegExp(r'^\d*\.?\d{0,2}')),
                        ],
                        controller: TextEditingController(
                          text: item.unitCost.toStringAsFixed(2),
                        ),
                        onChanged: (value) {
                          final cost = double.tryParse(value) ?? 0.0;
                          _updateUnitCost(index, cost);
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      'المجموع',
                      style: TextStyle(
                        fontSize: 11,
                        color: Colors.grey.shade600,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _currencyFormat.format(item.quantity * item.unitCost),
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBottomSection() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.1),
            blurRadius: 10,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(
        child: Column(
          children: [
            TextField(
              controller: _supplierController,
              decoration: InputDecoration(
                labelText: 'المورد (اختياري)',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'إجمالي التكلفة',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                Text(
                  '${_currencyFormat.format(_totalCost)}',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).primaryColor,
                  ),
                ),
              ],
            ),
            if (_errorMessage != null) ...[
              const SizedBox(height: 8),
              Text(
                _errorMessage!,
                style: TextStyle(color: Colors.red.shade700, fontSize: 13),
              ),
            ],
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _cartItems.isNotEmpty && !_isSubmitting
                    ? _submitPurchase
                    : null,
                icon: _isSubmitting
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.shopping_cart_checkout),
                label: Text(_isSubmitting ? 'جاري الحفظ...' : 'إرسال طلب الشراء'),
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class PurchaseItem {
  final int productId;
  final String productName;
  int quantity;
  double unitCost;

  PurchaseItem({
    required this.productId,
    required this.productName,
    required this.quantity,
    required this.unitCost,
  });
}