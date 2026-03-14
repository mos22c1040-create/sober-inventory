import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../utils/api_parse.dart';

/// شاشة تفاصيل الفاتورة / الإيصال — مطابقة لصفحة الموقع
class SaleDetailScreen extends StatefulWidget {
  const SaleDetailScreen({
    super.key,
    required this.api,
    required this.saleId,
  });

  final ApiClient api;
  final int saleId;

  @override
  State<SaleDetailScreen> createState() => _SaleDetailScreenState();
}

class _SaleDetailScreenState extends State<SaleDetailScreen> {
  bool _loading = true;
  String? _error;
  Map<String, dynamic>? _sale;
  List<Map<String, dynamic>> _items = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await widget.api.getSaleDetails(widget.saleId);
      if (mounted) {
        setState(() {
          _sale = data['sale'] is Map
              ? Map<String, dynamic>.from(data['sale'] as Map)
              : null;
          final rawItems = data['items'];
          _items = rawItems is List
              ? (rawItems)
                  .map((e) => Map<String, dynamic>.from(e as Map))
                  .toList()
              : [];
          _loading = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _error = 'فشل تحميل تفاصيل الفاتورة';
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: Text(
            _sale != null
                ? 'فاتورة #${_sale!['invoice_number'] ?? widget.saleId}'
                : 'تفاصيل الفاتورة',
          ),
          backgroundColor: AppColors.bg,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_rounded),
            onPressed: () => Navigator.pop(context),
          ),
        ),
        body: _loading
            ? const Center(
                child: CircularProgressIndicator(color: AppColors.primary),
              )
            : _error != null
                ? Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.error_outline_rounded,
                            size: 56,
                            color: AppColors.error,
                          ),
                          const SizedBox(height: 16),
                          Text(
                            _error!,
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              color: AppColors.textSecondary,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const SizedBox(height: 24),
                          FilledButton.icon(
                            onPressed: _load,
                            icon: const Icon(Icons.refresh_rounded, size: 20),
                            label: const Text('إعادة المحاولة'),
                            style: FilledButton.styleFrom(
                              backgroundColor: AppColors.primary,
                              foregroundColor: Colors.white,
                            ),
                          ),
                        ],
                      ),
                    ),
                  )
                : RefreshIndicator(
                    color: AppColors.primary,
                    onRefresh: _load,
                    child: SingleChildScrollView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          _buildHeader(),
                          const SizedBox(height: 20),
                          _buildItems(),
                          const SizedBox(height: 16),
                          _buildTotals(),
                        ],
                      ),
                    ),
                  ),
      ),
    );
  }

  Widget _buildHeader() {
    if (_sale == null) return const SizedBox.shrink();
    final status = (_sale!['status'] ?? '').toString();
    final isPaid = status == 'paid';
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'العميل: ${_sale!['customer_name'] ?? '—'}',
                style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 15,
                  color: AppColors.textPrimary,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: isPaid ? AppColors.successBg : AppColors.errorBg,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  isPaid ? 'مدفوعة' : 'ملغاة',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: isPaid ? AppColors.success : AppColors.error,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            'الكاشير: ${_sale!['cashier_name'] ?? '—'}',
            style: const TextStyle(
              fontSize: 13,
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'التاريخ: ${_sale!['created_at'] ?? '—'}',
            style: const TextStyle(
              fontSize: 12,
              color: AppColors.textHint,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'طريقة الدفع: ${_sale!['payment_method'] == 'card' ? 'بطاقة' : (_sale!['payment_method'] == 'mixed' ? 'مختلط' : 'نقدي')}',
            style: const TextStyle(
              fontSize: 12,
              color: AppColors.textHint,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildItems() {
    if (_items.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: AppColors.card,
          borderRadius: BorderRadius.circular(16),
        ),
        child: const Center(
          child: Text(
            'لا توجد أصناف',
            style: TextStyle(
              color: AppColors.textHint,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
      );
    }
    return Container(
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 16, 10),
            child: Text(
              'الأصناف',
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 14,
                color: AppColors.textPrimary,
              ),
            ),
          ),
          ..._items.map((item) {
            final name = (item['product_name'] ?? item['name'] ?? '—').toString();
            final qty = toInt(item['quantity']);
            final price = toDouble(item['unit_price'] ?? item['price']);
            final total = toDouble(item['total']);
            return Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          name,
                          style: const TextStyle(
                            fontWeight: FontWeight.w600,
                            fontSize: 13,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        Text(
                          '$qty × ${price.toStringAsFixed(0)}',
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppColors.textHint,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Text(
                    '${total.toStringAsFixed(0)} د.ع',
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                      color: AppColors.textPrimary,
                    ),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildTotals() {
    if (_sale == null) return const SizedBox.shrink();
    final total = toDouble(_sale!['total']);
    final discount = toDouble(_sale!['discount'] ?? 0);
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        children: [
          if (discount > 0) ...[
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'الخصم',
                  style: TextStyle(
                    fontSize: 14,
                    color: AppColors.textSecondary,
                  ),
                ),
                Text(
                  '${discount.toStringAsFixed(0)} د.ع',
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 14,
                    color: AppColors.textPrimary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
          ],
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'الإجمالي',
                style: TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 16,
                  color: AppColors.textPrimary,
                ),
              ),
              Text(
                '${total.toStringAsFixed(0)} د.ع',
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 18,
                  color: AppColors.primary,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
