import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';

class PnLDashboardScreen extends StatefulWidget {
  final ApiClient api;

  const PnLDashboardScreen({super.key, required this.api});

  @override
  State<PnLDashboardScreen> createState() => _PnLDashboardScreenState();
}

class _PnLDashboardScreenState extends State<PnLDashboardScreen> {
  final NumberFormat _currencyFormat = NumberFormat('#,##0.00');

  bool _isLoading = false;
  String? _errorMessage;
  PnLData? _pnlData;

  DateTime _startDate = DateTime(DateTime.now().year, DateTime.now().month, 1);
  DateTime _endDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    _fetchPnLData();
  }

  Future<void> _fetchPnLData() async {
    setState(() { _isLoading = true; _errorMessage = null; });
    try {
      final startDateStr = DateFormat('yyyy-MM-dd').format(_startDate);
      final endDateStr = DateFormat('yyyy-MM-dd').format(_endDate);
      final response = await widget.api.fetchPnL(startDate: startDateStr, endDate: endDateStr);
      setState(() { _pnlData = PnLData.fromJson(response); _isLoading = false; });
    } catch (e) {
      setState(() {
        _isLoading = false;
        _errorMessage = e.toString().contains('403') ? 'ليس لديك صلاحية الوصول إلى هذا التقرير' : 'فشل تحميل التقرير';
      });
    }
  }

  Future<void> _selectDateRange() async {
    final picked = await showDateRangePicker(
      context: context,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDateRange: DateTimeRange(start: _startDate, end: _endDate),
      builder: (context, child) => Theme(data: Theme.of(context).copyWith(colorScheme: ColorScheme.fromSeed(seedColor: AppColors.primary, brightness: Brightness.light)), child: child!),
    );
    if (picked != null) { setState(() { _startDate = picked.start; _endDate = picked.end; }); _fetchPnLData(); }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      appBar: AppBar(title: Text('التقرير المالي', style: GoogleFonts.cairo(fontWeight: FontWeight.w700)), centerTitle: true, elevation: 0, backgroundColor: Colors.transparent),
      body: _isLoading ? const _LoadingState() : _errorMessage != null ? _ErrorState(message: _errorMessage!, onRetry: _fetchPnLData) : _pnlData == null ? const EmptyState(icon: Icons.analytics_outlined, title: 'لا توجد بيانات') : _buildContent(),
    );
  }

  Widget _buildContent() {
    return RefreshIndicator(onRefresh: _fetchPnLData, color: AppColors.primary, child: SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        _buildDateFilter(),
        const SizedBox(height: 20),
        _buildSummaryCards(),
        const SizedBox(height: 24),
        _buildChartSection(),
        const SizedBox(height: 24),
        _buildDetailedBreakdown(),
      ]),
    ));
  }

  Widget _buildDateFilter() {
    final dateFormat = DateFormat('yyyy/MM/dd');
    return AppCards.modern(
      onTap: _selectDateRange,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
        Row(children: [
          Icon(Icons.date_range, color: AppColors.primary),
          const SizedBox(width: 10),
          Text('${dateFormat.format(_startDate)} - ${dateFormat.format(_endDate)}', style: GoogleFonts.cairo(fontWeight: FontWeight.w600, color: AppColors.textPrimary)),
        ]),
        Icon(Icons.arrow_drop_down, color: AppColors.primary),
      ]),
    );
  }

  Widget _buildSummaryCards() {
    return Wrap(spacing: 12, runSpacing: 12, children: [
      _SummaryCard(title: 'صافي الربح', value: _pnlData!.netProfit, icon: Icons.account_balance_wallet, gradient: AppColors.primaryGradient, isHighlighted: true),
      _SummaryCard(title: 'المبيعات الصافية', value: _pnlData!.netSales, icon: Icons.trending_up, color: AppColors.secondary),
      _SummaryCard(title: 'تكلفة البضاعة', value: _pnlData!.cogs, icon: Icons.inventory_2, color: const Color(0xFFF59E0B)),
      _SummaryCard(title: 'المرتجعات', value: _pnlData!.totalReturns, icon: Icons.undo, color: AppColors.error),
    ]);
  }

  Widget _buildChartSection() {
    if (_pnlData == null || _pnlData!.grossSales == 0) return const SizedBox.shrink();

    return AppCards.modern(
      padding: const EdgeInsets.all(20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('تحليل المبيعات', style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
        const SizedBox(height: 20),
        SizedBox(height: 200, child: Row(children: [
          Expanded(flex: 2, child: PieChart(PieChartData(
            sectionsSpace: 3,
            centerSpaceRadius: 45,
            sections: [
              PieChartSectionData(value: _pnlData!.cogs, title: '${(_pnlData!.cogs / _pnlData!.grossSales * 100).toStringAsFixed(1)}%', color: const Color(0xFFF59E0B), radius: 55, titleStyle: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
              PieChartSectionData(value: _pnlData!.totalReturns, title: '${(_pnlData!.totalReturns / _pnlData!.grossSales * 100).toStringAsFixed(1)}%', color: AppColors.error.withValues(alpha: 0.8), radius: 55, titleStyle: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
              PieChartSectionData(value: _pnlData!.netProfit, title: '${(_pnlData!.netProfit / _pnlData!.grossSales * 100).toStringAsFixed(1)}%', color: AppColors.primary, radius: 55, titleStyle: GoogleFonts.cairo(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white)),
            ],
          ))),
          Expanded(child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
            _LegendItem(label: 'التكلفة', color: const Color(0xFFF59E0B)),
            const SizedBox(height: 10),
            _LegendItem(label: 'المرتجعات', color: AppColors.error.withValues(alpha: 0.8)),
            const SizedBox(height: 10),
            _LegendItem(label: 'الربح', color: AppColors.primary),
          ])),
        ])),
      ]),
    );
  }

  Widget _buildDetailedBreakdown() {
    return AppCards.modern(
      padding: const EdgeInsets.all(20),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('التفاصيل', style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
        const SizedBox(height: 16),
        _DetailRow(label: 'المبيعات الإجمالية', value: _pnlData!.grossSales),
        _DetailRow(label: 'المرتجعات', value: _pnlData!.totalReturns, isNegative: true),
        _DetailRow(label: 'المبيعات الصافية', value: _pnlData!.netSales),
        const Divider(height: 24),
        _DetailRow(label: 'تكلفة البضاعة المباعة', value: _pnlData!.cogs, isNegative: true),
        const Divider(height: 24),
        _DetailRow(label: 'صافي الربح', value: _pnlData!.netProfit, isHighlighted: true),
      ]),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  final String title;
  final double value;
  final IconData icon;
  final Gradient? gradient;
  final Color? color;
  final bool isHighlighted;

  const _SummaryCard({required this.title, required this.value, required this.icon, this.gradient, this.color, this.isHighlighted = false});

  @override
  Widget build(BuildContext context) {
    final bgColor = color?.withValues(alpha: 0.1) ?? AppColors.primarySurface;
    return SizedBox(
      width: (MediaQuery.of(context).size.width - 52) / 2,
      child: gradient != null
          ? AppCards.elevated(gradient: gradient!, padding: const EdgeInsets.all(16), child: _buildCardContent(Colors.white, Colors.white))
          : AppCards.modern(padding: const EdgeInsets.all(16), child: _buildCardContent(color!, bgColor)),
    );
  }

  Widget _buildCardContent(Color iconColor, Color textColor) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Row(children: [
        Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: textColor.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)),
          child: Icon(icon, color: iconColor, size: 18)),
        const Spacer(),
      ]),
      const SizedBox(height: 10),
      Text(title, style: GoogleFonts.cairo(fontSize: 12, color: textColor.withValues(alpha: 0.8))),
      const SizedBox(height: 4),
      AnimatedCounter(value: '${value.toStringAsFixed(0)} د.ع', style: GoogleFonts.cairo(fontSize: 18, fontWeight: FontWeight.w800, color: textColor)),
    ]);
  }
}

class _LegendItem extends StatelessWidget {
  final String label;
  final Color color;
  const _LegendItem({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Row(children: [
      Container(width: 12, height: 12, decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(3))),
      const SizedBox(width: 8),
      Text(label, style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary)),
    ]);
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final double value;
  final bool isNegative;
  final bool isHighlighted;

  const _DetailRow({required this.label, required this.value, this.isNegative = false, this.isHighlighted = false});

  @override
  Widget build(BuildContext context) {
    final formatter = NumberFormat('#,##0.00');
    return Padding(padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
        Text(label, style: GoogleFonts.cairo(fontSize: 14, color: AppColors.textSecondary)),
        Text('${isNegative ? '-' : ''}${formatter.format(value)}', style: GoogleFonts.cairo(fontSize: 14, fontWeight: isHighlighted ? FontWeight.w700 : FontWeight.w500, color: isHighlighted ? AppColors.primary : (isNegative ? AppColors.error : AppColors.textPrimary))),
      ]),
    );
  }
}

class _LoadingState extends StatelessWidget {
  const _LoadingState();

  @override
  Widget build(BuildContext context) {
    return Padding(padding: const EdgeInsets.all(20), child: Column(children: [
      const SkeletonLoader(height: 50, borderRadius: 12),
      const SizedBox(height: 12),
      Row(children: [Expanded(child: SkeletonLoader(height: 100, borderRadius: 16)), const SizedBox(width: 12), Expanded(child: SkeletonLoader(height: 100, borderRadius: 16))]),
      const SizedBox(height: 20),
      const SkeletonLoader(height: 250, borderRadius: 16),
    ]));
  }
}

class _ErrorState extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorState({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(child: Padding(padding: const EdgeInsets.all(32), child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
      Container(padding: const EdgeInsets.all(20), decoration: BoxDecoration(color: AppColors.errorBg, borderRadius: BorderRadius.circular(16)),
        child: Icon(Icons.error_outline, size: 48, color: AppColors.error)),
      const SizedBox(height: 16),
      Text(message, style: GoogleFonts.cairo(fontSize: 14, color: AppColors.textSecondary), textAlign: TextAlign.center),
      const SizedBox(height: 20),
      ElevatedButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh), label: const Text('إعادة المحاولة')),
    ])));
  }
}

class PnLData {
  final double grossSales, totalReturns, netSales, cogs, netProfit, startDate, endDate;
  PnLData({required this.grossSales, required this.totalReturns, required this.netSales, required this.cogs, required this.netProfit, required this.startDate, required this.endDate});
  factory PnLData.fromJson(Map<String, dynamic> json) => PnLData(grossSales: (json['gross_sales'] ?? 0).toDouble(), totalReturns: (json['total_returns'] ?? 0).toDouble(), netSales: (json['net_sales'] ?? 0).toDouble(), cogs: (json['cogs'] ?? 0).toDouble(), netProfit: (json['net_profit'] ?? 0).toDouble(), startDate: json['start_date'] ?? '', endDate: json['end_date'] ?? '');
}