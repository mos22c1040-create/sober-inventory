import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../utils/api_parse.dart';

class SalesScreen extends StatefulWidget {
  const SalesScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<SalesScreen> createState() => _SalesScreenState();
}

class _SalesScreenState extends State<SalesScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _sales = [];
  String? _error;
  Map<String, dynamic> _stats = {};
  int _page = 1;
  int _pages = 1;
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

  Future<void> _load({int page = 1}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final r = await widget.api.getSales(page: page, perPage: 20);
      if (mounted) {
        setState(() {
          final rawList = r['data'] as List? ?? [];
          _sales = rawList.map((e) {
            final m = Map<String, dynamic>.from(e as Map);
            m['total'] = toDouble(m['total']);
            m['id'] = toInt(m['id']);
            return m;
          }).toList();
          _stats = {
            'today_total': toDouble(r['today_total']),
            'today_count': toInt(r['today_count']),
            'monthly_total': toDouble(r['monthly_total']),
          };
          _page = page;
          _pages = toInt(r['pages']).clamp(1, 999999);
        });
      }
    } catch (_) {
      if (mounted) setState(() => _error = 'فشل تحميل المبيعات');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _cancelSale(int id) async {
    if (_csrfToken.isEmpty) {
      await _loadCsrf();
      if (_csrfToken.isEmpty) return;
    }
    try {
      final r = await widget.api.cancelSale(id: id, csrfToken: _csrfToken);
      if (r['success'] == true && mounted) {
        _showSnack('تم إلغاء الفاتورة');
        _load(page: _page);
      } else {
        _showSnack((r['error'] ?? 'تعذر الإلغاء').toString(), isError: true);
      }
    } catch (_) {
      if (mounted) _showSnack('تعذر الاتصال', isError: true);
    }
  }

  void _showSnack(String msg, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: isError ? AppColors.error : AppColors.success,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin: const EdgeInsets.all(16),
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: const Text('المبيعات'),
          backgroundColor: AppColors.bg,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_rounded),
            onPressed: () => Navigator.pop(context),
          ),
        ),
        body: RefreshIndicator(
          color: AppColors.primary,
          onRefresh: () => _load(page: _page),
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [
              SliverToBoxAdapter(child: _buildStats()),
              if (_error != null)
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppColors.errorBg,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(_error!,
                          style: const TextStyle(color: AppColors.error)),
                    ),
                  ),
                ),
              if (_loading)
                const SliverFillRemaining(
                  child: Center(
                    child: CircularProgressIndicator(color: AppColors.primary),
                  ),
                )
              else if (_sales.isEmpty)
                const SliverFillRemaining(
                  child: Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.receipt_long_rounded,
                            size: 56, color: AppColors.textHint),
                        SizedBox(height: 16),
                        Text('لا توجد مبيعات',
                            style: TextStyle(
                                color: AppColors.textSecondary,
                                fontWeight: FontWeight.w600)),
                      ],
                    ),
                  ),
                )
              else
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 20),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (ctx, i) => _SaleCard(
                        sale: _sales[i],
                        onCancel: _csrfToken.isNotEmpty
                            ? () async {
                                final confirm = await showDialog<bool>(
                                  context: context,
                                  builder: (_) => Directionality(
                                    textDirection: TextDirection.rtl,
                                    child: AlertDialog(
                                      shape: RoundedRectangleBorder(
                                          borderRadius:
                                              BorderRadius.circular(20)),
                                      title: const Text('إلغاء الفاتورة'),
                                      content: Text(
                                          'هل تريد إلغاء الفاتورة #${_sales[i]['invoice_number']}؟'),
                                      actions: [
                                        TextButton(
                                            onPressed: () =>
                                                Navigator.pop(context, false),
                                            child: const Text('لا')),
                                        ElevatedButton(
                                          onPressed: () =>
                                              Navigator.pop(context, true),
                                          style: ElevatedButton.styleFrom(
                                              backgroundColor: AppColors.error),
                                          child: const Text('إلغاء الفاتورة'),
                                        ),
                                      ],
                                    ),
                                  ),
                                );
                                if (confirm == true) {
                                  _cancelSale(toInt(_sales[i]['id']));
                                }
                              }
                            : null,
                      ),
                      childCount: _sales.length,
                    ),
                  ),
                ),
              if (!_loading && _pages > 1)
                SliverToBoxAdapter(child: _buildPagination()),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStats() {
    final todayTotal = toDouble(_stats['today_total']);
    final todayCount = toInt(_stats['today_count']);
    final monthlyTotal = toDouble(_stats['monthly_total']);

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      child: Row(
        children: [
          Expanded(
              child: _statChip('مبيعات اليوم',
                  _fmt(todayTotal), AppColors.primary, AppColors.primary.withValues(alpha: 0.1))),
          const SizedBox(width: 10),
          Expanded(
              child: _statChip('فواتير اليوم', '$todayCount',
                  AppColors.success, AppColors.successBg)),
          const SizedBox(width: 10),
          Expanded(
              child: _statChip('مبيعات الشهر',
                  _fmt(monthlyTotal), const Color(0xFF7C3AED), const Color(0xFFF0EBFF))),
        ],
      ),
    );
  }

  Widget _statChip(String label, String value, Color color, Color bg) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        children: [
          Text(value,
              style: TextStyle(
                  fontSize: 16, fontWeight: FontWeight.w800, color: color)),
          const SizedBox(height: 2),
          Text(label,
              style: const TextStyle(
                  fontSize: 10,
                  color: AppColors.textSecondary,
                  fontWeight: FontWeight.w500),
              textAlign: TextAlign.center),
        ],
      ),
    );
  }

  Widget _buildPagination() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          IconButton.outlined(
            onPressed: _page > 1 ? () => _load(page: _page - 1) : null,
            icon: const Icon(Icons.chevron_right_rounded),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text('$_page / $_pages',
                style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary)),
          ),
          IconButton.outlined(
            onPressed: _page < _pages ? () => _load(page: _page + 1) : null,
            icon: const Icon(Icons.chevron_left_rounded),
          ),
        ],
      ),
    );
  }

  String _fmt(double n) {
    if (n >= 1000000) return '${(n / 1e6).toStringAsFixed(1)}م';
    if (n >= 1000) return '${(n / 1000).toStringAsFixed(1)}ك';
    return n.toStringAsFixed(0);
  }
}

class _SaleCard extends StatelessWidget {
  const _SaleCard({required this.sale, required this.onCancel});

  final Map<String, dynamic> sale;
  final VoidCallback? onCancel;

  @override
  Widget build(BuildContext context) {
    final status = (sale['status'] ?? '').toString();
    final isPaid = status == 'paid';
    final statusColor = isPaid ? AppColors.success : AppColors.error;
    final statusBg = isPaid ? AppColors.successBg : AppColors.errorBg;
    final statusLabel = isPaid ? 'مدفوعة' : 'ملغاة';
    final total = toDouble(sale['total']).toStringAsFixed(0);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
              color: Colors.black.withValues(alpha: 0.04), blurRadius: 10)
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Icons.receipt_rounded,
                color: AppColors.primary, size: 22),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '#${sale['invoice_number'] ?? '—'}',
                  style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 14,
                      color: AppColors.textPrimary),
                ),
                const SizedBox(height: 3),
                Text(
                  '${sale['customer_name'] ?? '—'} · ${(sale['cashier_name'] ?? '').toString()}',
                  style: const TextStyle(
                      fontSize: 12, color: AppColors.textSecondary),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '$total د.ع',
                style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w800,
                    color: AppColors.textPrimary),
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                        color: statusBg,
                        borderRadius: BorderRadius.circular(20)),
                    child: Text(
                      statusLabel,
                      style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w700,
                          color: statusColor),
                    ),
                  ),
                  if (isPaid && onCancel != null) ...[
                    const SizedBox(width: 6),
                    GestureDetector(
                      onTap: onCancel,
                      child: Container(
                        padding: const EdgeInsets.all(4),
                        decoration: BoxDecoration(
                            color: AppColors.errorBg,
                            borderRadius: BorderRadius.circular(8)),
                        child: const Icon(Icons.cancel_outlined,
                            color: AppColors.error, size: 14),
                      ),
                    ),
                  ],
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}
