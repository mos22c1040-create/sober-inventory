import 'package:flutter/material.dart';
import 'package:share_plus/share_plus.dart';

import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../utils/api_parse.dart';

class ReportsScreen extends StatefulWidget {
  const ReportsScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends State<ReportsScreen> {
  bool _loading = true;
  bool _exporting = false;
  Map<String, dynamic> _data = {};
  List<Map<String, dynamic>> _lowStockProducts = [];
  DateTime _from = DateTime.now().subtract(const Duration(days: 30));
  DateTime _to = DateTime.now();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final r = await widget.api.getReports(
        from: _from.toIso8601String().substring(0, 10),
        to: _to.toIso8601String().substring(0, 10),
      );
      final lowStock = await widget.api.getLowStockProducts(limit: 30);
      if (mounted) {
        setState(() {
          _data = r;
          _lowStockProducts = lowStock;
        });
      }
    } catch (_) {} finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _fmt(double n) {
    if (n >= 1000000) return '${(n / 1e6).toStringAsFixed(1)}م';
    if (n >= 1000) return '${(n / 1000).toStringAsFixed(1)}ك';
    return n.toStringAsFixed(0);
  }

  @override
  Widget build(BuildContext context) {
    final profit = _data['profit'] as Map? ?? {};
    final revenue = toDouble(profit['total_revenue']);
    final cost = toDouble(profit['total_cost']);
    final gross = toDouble(profit['gross_profit']);
    final topProducts = _data['top_products'] as List? ?? [];
    final salesByDay = _data['sales_by_day'] as List? ?? [];

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: const Text('التقارير'),
          backgroundColor: AppColors.bg,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_rounded),
            onPressed: () => Navigator.pop(context),
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.filter_alt_rounded, color: AppColors.primary),
              onPressed: _pickDateRange,
            ),
          ],
        ),
        body: _loading
            ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
            : RefreshIndicator(
                color: AppColors.primary,
                onRefresh: _load,
                child: SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Date range indicator
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.calendar_month_rounded, color: AppColors.primary, size: 16),
                            const SizedBox(width: 8),
                            Text(
                              '${_from.toIso8601String().substring(0, 10)}  →  ${_to.toIso8601String().substring(0, 10)}',
                              style: const TextStyle(fontWeight: FontWeight.w600, color: AppColors.primary, fontSize: 13),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),
                      // تصدير CSV (مثل الموقع)
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: _exporting ? null : () => _exportCsv(isSales: true),
                              icon: _exporting ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.file_download_rounded, size: 18),
                              label: const Text('تصدير المبيعات CSV'),
                              style: OutlinedButton.styleFrom(foregroundColor: AppColors.primary),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: _exporting ? null : () => _exportCsv(isSales: false),
                              icon: const Icon(Icons.table_chart_rounded, size: 18),
                              label: const Text('تصدير المنتجات CSV'),
                              style: OutlinedButton.styleFrom(foregroundColor: AppColors.primary),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),

                      // Profit cards
                      Row(
                        children: [
                          Expanded(child: _profitCard('الإيرادات', _fmt(revenue), AppColors.primary, AppColors.primary.withValues(alpha: 0.1))),
                          const SizedBox(width: 10),
                          Expanded(child: _profitCard('التكلفة', _fmt(cost), AppColors.error, AppColors.errorBg)),
                          const SizedBox(width: 10),
                          Expanded(child: _profitCard('الأرباح', _fmt(gross), AppColors.success, AppColors.successBg)),
                        ],
                      ),
                      const SizedBox(height: 20),

                      // Top products
                      if (topProducts.isNotEmpty) ...[
                        _section('أكثر المنتجات مبيعاً', Icons.star_rounded),
                        const SizedBox(height: 12),
                        ...topProducts.take(10).map((p) {
                          final mp = p as Map;
                          return Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                            decoration: BoxDecoration(
                              color: AppColors.card,
                              borderRadius: BorderRadius.circular(14),
                              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6)],
                            ),
                            child: Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: AppColors.primary.withValues(alpha: 0.1),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: const Icon(Icons.inventory_2_rounded, color: AppColors.primary, size: 16),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text((mp['name'] ?? '').toString(),
                                          style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                                      Text('${mp['qty_sold'] ?? 0} وحدة',
                                          style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
                                    ],
                                  ),
                                ),
                                Text(
                                  '${_fmt(toDouble(mp['revenue']))} د.ع',
                                  style: const TextStyle(fontWeight: FontWeight.w700, color: AppColors.primary),
                                ),
                              ],
                            ),
                          );
                        }),
                        const SizedBox(height: 20),
                      ],

                      // Sales by day
                      if (salesByDay.isNotEmpty) ...[
                        _section('المبيعات اليومية', Icons.bar_chart_rounded),
                        const SizedBox(height: 12),
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: AppColors.card,
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8)],
                          ),
                          child: Column(
                            children: salesByDay.take(15).map((d) {
                              final md = d as Map;
                              final t = toDouble(md['total']);
                              final maxT = (salesByDay as List).fold<double>(1.0, (m, e) {
                                final v = toDouble((e as Map)['total']);
                                return v > m ? v : m;
                              });
                              return Padding(
                                padding: const EdgeInsets.only(bottom: 10),
                                child: Row(
                                  children: [
                                    SizedBox(
                                      width: 80,
                                      child: Text(
                                        (md['day'] ?? '').toString().substring(5),
                                        style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
                                      ),
                                    ),
                                    Expanded(
                                      child: ClipRRect(
                                        borderRadius: BorderRadius.circular(6),
                                        child: LinearProgressIndicator(
                                          value: maxT > 0 ? (t / maxT) : 0,
                                          backgroundColor: AppColors.outline,
                                          color: AppColors.primary,
                                          minHeight: 10,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 10),
                                    SizedBox(
                                      width: 60,
                                      child: Text(
                                        _fmt(t),
                                        textAlign: TextAlign.end,
                                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            }).toList(),
                          ),
                        ),
                      ],

                      // تقرير المخزون المنخفض
                      if (_lowStockProducts.isNotEmpty) ...[
                        const SizedBox(height: 20),
                        _section('تنبيهات المخزون — منتجات تحتاج إعادة تخزين', Icons.warning_amber_rounded),
                        const SizedBox(height: 12),
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: AppColors.warningBg,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: AppColors.warning.withValues(alpha: 0.3)),
                          ),
                          child: Column(
                            children: _lowStockProducts.take(15).map((p) {
                              final qty = toInt(p['quantity']);
                              final thresh = toInt(p['low_stock_threshold']);
                              return Padding(
                                padding: const EdgeInsets.only(bottom: 10),
                                child: Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.all(8),
                                      decoration: BoxDecoration(
                                        color: AppColors.warning.withValues(alpha: 0.2),
                                        borderRadius: BorderRadius.circular(10),
                                      ),
                                      child: Icon(Icons.inventory_2_outlined, color: AppColors.warning, size: 18),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            (p['name'] ?? '').toString(),
                                            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
                                          ),
                                          Text(
                                            'الكمية: $qty / الحد: $thresh',
                                            style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
                                          ),
                                        ],
                                      ),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: AppColors.warning.withValues(alpha: 0.2),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Text(
                                        '$qty',
                                        style: TextStyle(fontWeight: FontWeight.w700, fontSize: 12, color: AppColors.warning),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            }).toList(),
                          ),
                        ),
                        if (_lowStockProducts.length > 15)
                          Padding(
                            padding: const EdgeInsets.only(top: 8),
                            child: Text(
                              '+${_lowStockProducts.length - 15} منتج آخر',
                              style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                            ),
                          ),
                      ],
                    ],
                  ),
                ),
              ),
      ),
    );
  }

  Future<void> _exportCsv({required bool isSales}) async {
    if (_exporting) return;
    setState(() => _exporting = true);
    final from = _from.toIso8601String().substring(0, 10);
    final to = _to.toIso8601String().substring(0, 10);
    try {
      final bytes = isSales
          ? await widget.api.getReportExportSalesBytes(from: from, to: to)
          : await widget.api.getReportExportProductsBytes(from: from, to: to);
      if (!mounted) return;
      if (bytes.isEmpty) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('لا توجد بيانات للتصدير أو الصلاحية غير كافية'),
            backgroundColor: AppColors.error,
            behavior: SnackBarBehavior.floating,
          ),
        );
        setState(() => _exporting = false);
        return;
      }
      final name = isSales ? 'sales_$from\_$to.csv' : 'products_$from\_$to.csv';
      final xfile = XFile.fromData(bytes, name: name);
      await Share.shareXFiles([xfile], text: isSales ? 'تقرير المبيعات' : 'أكثر المنتجات مبيعاً');
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('تعذر التصدير: ${e.toString()}'),
            backgroundColor: AppColors.error,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _exporting = false);
    }
  }

  Future<void> _pickDateRange() async {
    final range = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDateRange: DateTimeRange(start: _from, end: _to),
    );
    if (range != null) {
      setState(() { _from = range.start; _to = range.end; });
      _load();
    }
  }

  Widget _profitCard(String label, String value, Color color, Color bg) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(14)),
      child: Column(
        children: [
          Text(value, style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: color)),
          const SizedBox(height: 4),
          Text(label, style: const TextStyle(fontSize: 10, color: AppColors.textSecondary, fontWeight: FontWeight.w500), textAlign: TextAlign.center),
        ],
      ),
    );
  }

  Widget _section(String label, IconData icon) {
    return Row(
      children: [
        Icon(icon, color: AppColors.primary, size: 20),
        const SizedBox(width: 8),
        Text(label, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
      ],
    );
  }
}
