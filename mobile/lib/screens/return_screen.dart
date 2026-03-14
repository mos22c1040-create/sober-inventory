import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import '../api/api_client.dart';

class ReturnScreen extends StatefulWidget {
  final ApiClient api;

  const ReturnScreen({super.key, required this.api});

  @override
  State<ReturnScreen> createState() => _ReturnScreenState();
}

class _ReturnScreenState extends State<ReturnScreen> {
  final TextEditingController _saleIdController = TextEditingController();
  final TextEditingController _reasonController = TextEditingController();
  final NumberFormat _currencyFormat = NumberFormat('#,##0.00');

  bool _isLoading = false;
  bool _isSubmitting = false;
  String? _errorMessage;
  Map<String, dynamic>? _saleData;
  List<SaleItemReturn> _returnItems = [];

  @override
  void dispose() {
    _saleIdController.dispose();
    _reasonController.dispose();
    super.dispose();
  }

  Future<void> _searchSale() async {
    final saleIdText = _saleIdController.text.trim();
    if (saleIdText.isEmpty) {
      _showError('الرجاء إدخال رقم الفاتورة');
      return;
    }

    final saleId = int.tryParse(saleIdText);
    if (saleId == null || saleId <= 0) {
      _showError('رقم الفاتورة غير صالح');
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _saleData = null;
      _returnItems = [];
    });

    try {
      final response = await widget.api.getSaleDetails(saleId);
      final sale = response['sale'] as Map<String, dynamic>?;
      final items = response['items'] as List<dynamic>?;

      if (sale == null) {
        _showError('الفاتورة غير موجودة');
        return;
      }

      if (sale['status'] == 'cancelled') {
        _showError('لا يمكن إرجاع فاتورة ملغاة');
        return;
      }

      setState(() {
        _saleData = sale;
        _returnItems = (items ?? []).map((item) {
          return SaleItemReturn(
            productId: item['product_id'] as int,
            productName: item['product_name'] as String? ?? 'منتج',
            originalQuantity: item['quantity'] as int,
            unitPrice: (item['unit_price'] as num).toDouble(),
            returnQuantity: 0,
          );
        }).toList();
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _isLoading = false;
        _errorMessage = 'فشل تحميل الفاتورة';
      });
    }
  }

  void _updateReturnQuantity(int index, int quantity) {
    setState(() {
      _returnItems[index].returnQuantity = quantity;
    });
  }

  double get _totalRefund {
    return _returnItems.fold(0.0, (sum, item) {
      return sum + (item.returnQuantity * item.unitPrice);
    });
  }

  List<Map<String, dynamic>> get _selectedReturnItems {
    return _returnItems
        .where((item) => item.returnQuantity > 0)
        .map((item) => {
              'product_id': item.productId,
              'quantity': item.returnQuantity,
              'unit_price': item.unitPrice,
            })
        .toList();
  }

  Future<void> _submitReturn() async {
    if (_saleData == null) {
      _showError('الرجاء البحث عن فاتورة أولاً');
      return;
    }

    final selectedItems = _selectedReturnItems;
    if (selectedItems.isEmpty) {
      _showError('الرجاء تحديد منتجات للإرجاع');
      return;
    }

    final csrfToken = await _getCsrfToken();

    setState(() {
      _isSubmitting = true;
      _errorMessage = null;
    });

    try {
      final payload = {
        'sale_id': int.parse(_saleIdController.text.trim()),
        'reason': _reasonController.text.trim(),
        'csrf_token': csrfToken,
        'items': selectedItems,
      };

      await widget.api.submitReturn(payload);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('تم معالجة الإرجاع بنجاح'),
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
          _errorMessage = 'بيانات غير صالحة. تحقق من الكميات المراد إرجاعها.';
        } else {
          _errorMessage = 'فشل معالجة الإرجاع';
        }
      });
    }
  }

  Future<String> _getCsrfToken() async {
    try {
      final me = await widget.api.getMe();
      return me['csrf_token'] ?? '';
    } catch (e) {
      return '';
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
      _saleIdController.clear();
      _reasonController.clear();
      _saleData = null;
      _returnItems = [];
      _isSubmitting = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('إرجاع المنتجات'),
        centerTitle: true,
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _saleData == null
              ? _buildSearchSection()
              : _buildReturnForm(),
    );
  }

  Widget _buildSearchSection() {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.receipt_long_rounded,
            size: 80,
            color: Colors.grey.shade300,
          ),
          const SizedBox(height: 24),
          const Text(
            'البحث عن فاتورة',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'أدخل رقم الفاتورة للبدء',
            style: TextStyle(
              color: Colors.grey.shade600,
            ),
          ),
          const SizedBox(height: 32),
          TextField(
            controller: _saleIdController,
            keyboardType: TextInputType.number,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
            decoration: InputDecoration(
              hintText: 'رقم الفاتورة',
              filled: true,
              fillColor: Colors.grey.shade100,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(16),
                borderSide: BorderSide.none,
              ),
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 24,
                vertical: 20,
              ),
            ),
            inputFormatters: [
              FilteringTextInputFormatter.digitsOnly,
            ],
          ),
          if (_errorMessage != null) ...[
            const SizedBox(height: 16),
            Text(
              _errorMessage!,
              style: TextStyle(color: Colors.red.shade700),
            ),
          ],
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _searchSale,
              icon: const Icon(Icons.search),
              label: const Text('بحث'),
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
    );
  }

  Widget _buildReturnForm() {
    return Column(
      children: [
        // Sale Info Header
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          color: Theme.of(context).primaryColor.withValues(alpha: 0.1),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'فاتورة رقم: ${_saleData!['invoice_number'] ?? ''}',
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  Text(
                    'العميل: ${_saleData!['customer_name'] ?? '—'}',
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                  ),
                ],
              ),
              IconButton(
                icon: const Icon(Icons.close),
                onPressed: _resetForm,
              ),
            ],
          ),
        ),

        // Items List
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: _returnItems.length,
            itemBuilder: (context, index) {
              final item = _returnItems[index];
              return _buildReturnItemCard(item, index);
            },
          ),
        ),

        // Bottom Section
        Container(
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
                  controller: _reasonController,
                  decoration: InputDecoration(
                    labelText: 'سبب الإرجاع (اختياري)',
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
                      'المبلغ المسترد',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    Text(
                      '${_currencyFormat.format(_totalRefund)}',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: _totalRefund > 0 ? Colors.red.shade700 : Colors.grey,
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
                    onPressed: _totalRefund > 0 && !_isSubmitting
                        ? _submitReturn
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
                        : const Icon(Icons.undo),
                    label: Text(_isSubmitting ? 'جاري المعالجة...' : 'معالجة الإرجاع'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red.shade600,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                      disabledBackgroundColor: Colors.grey.shade300,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildReturnItemCard(SaleItemReturn item, int index) {
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
                Text(
                  '${_currencyFormat.format(item.unitPrice)}',
                  style: TextStyle(
                    color: Colors.grey.shade600,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Text(
                  'المباع: ${item.originalQuantity}',
                  style: TextStyle(
                    color: Colors.grey.shade600,
                    fontSize: 13,
                  ),
                ),
                const Spacer(),
                Container(
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      IconButton(
                        icon: const Icon(Icons.remove, size: 20),
                        onPressed: item.returnQuantity > 0
                            ? () => _updateReturnQuantity(
                                  index,
                                  item.returnQuantity - 1,
                                )
                            : null,
                        constraints: const BoxConstraints(
                          minWidth: 36,
                          minHeight: 36,
                        ),
                        padding: EdgeInsets.zero,
                      ),
                      Container(
                        constraints: const BoxConstraints(minWidth: 32),
                        alignment: Alignment.center,
                        child: Text(
                          '${item.returnQuantity}',
                          style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.add, size: 20),
                        onPressed: item.returnQuantity < item.originalQuantity
                            ? () => _updateReturnQuantity(
                                  index,
                                  item.returnQuantity + 1,
                                )
                            : null,
                        constraints: const BoxConstraints(
                          minWidth: 36,
                          minHeight: 36,
                        ),
                        padding: EdgeInsets.zero,
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (item.returnQuantity > 0)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(
                  'مسترد: ${_currencyFormat.format(item.returnQuantity * item.unitPrice)}',
                  style: TextStyle(
                    color: Colors.red.shade700,
                    fontWeight: FontWeight.w500,
                    fontSize: 13,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class SaleItemReturn {
  final int productId;
  final String productName;
  final int originalQuantity;
  final double unitPrice;
  int returnQuantity;

  SaleItemReturn({
    required this.productId,
    required this.productName,
    required this.originalQuantity,
    required this.unitPrice,
    this.returnQuantity = 0,
  });
}