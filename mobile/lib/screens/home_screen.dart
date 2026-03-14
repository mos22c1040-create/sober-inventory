import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../utils/api_parse.dart';
import 'products_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> with SingleTickerProviderStateMixin {
  bool _loading = true;
  String? _error;
  Map<String, dynamic> _data = {};
  late AnimationController _animCtrl;

  @override
  void initState() {
    super.initState();
    _animCtrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 800));
    _load();
  }

  @override
  void dispose() {
    _animCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final res = await widget.api.getDashboard();
      setState(() => _data = res);
      _animCtrl.forward(from: 0);
    } catch (_) {
      setState(() => _error = 'فشل تحميل البيانات');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        body: RefreshIndicator(color: AppColors.primary, onRefresh: _load,
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [
              _buildAppBar(),
              if (_loading) const SliverFillRemaining(child: _LoadingState())
              else ...[
                SliverToBoxAdapter(child: Column(children: [
                  _buildHeader(),
                  _buildStatsRow(context),
                  _buildLowStockAlert(context),
                  _buildChartSection(context),
                  const SizedBox(height: 24),
                ])),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildAppBar() {
    return SliverAppBar(
      expandedHeight: 0, floating: true, snap: true,
      backgroundColor: AppColors.bg, elevation: 0,
      title: Row(children: [
        Container(width: 36, height: 36, decoration: BoxDecoration(gradient: AppColors.primaryGradient, borderRadius: BorderRadius.circular(10)),
          child: const Icon(Icons.point_of_sale_rounded, color: Colors.white, size: 18)),
        const SizedBox(width: 12),
        Text('Sober POS', style: GoogleFonts.cairo(fontWeight: FontWeight.w800, fontSize: 18, color: AppColors.textPrimary)),
      ]),
      actions: [
        if (_error != null) IconButton(icon: const Icon(Icons.refresh_rounded, color: AppColors.primary), onPressed: _load),
        const SizedBox(width: 4),
      ],
    );
  }

  Widget _buildHeader() {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 8, 20, 20),
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(gradient: AppColors.primaryGradient, borderRadius: BorderRadius.circular(24), boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.3), blurRadius: 20, offset: const Offset(0, 8))]),
      child: Row(children: [
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('مرحباً 👋', style: GoogleFonts.cairo(color: Colors.white.withValues(alpha: 0.7), fontSize: 14, fontWeight: FontWeight.w500)),
          const SizedBox(height: 6),
          Text('لوحة التحكم', style: GoogleFonts.cairo(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800, letterSpacing: -0.5)),
          const SizedBox(height: 10),
          Container(padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6), decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(20)),
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              Container(width: 7, height: 7, decoration: const BoxDecoration(color: AppColors.accent, shape: BoxShape.circle)),
              const SizedBox(width: 6),
              Text(_formatDate(), style: GoogleFonts.cairo(color: Colors.white.withValues(alpha: 0.75), fontSize: 11, fontWeight: FontWeight.w500)),
            ])),
        ])),
        Container(padding: const EdgeInsets.all(16), decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(18)),
          child: const Icon(Icons.dashboard_rounded, color: Colors.white, size: 32)),
      ]),
    );
  }

  Widget _buildStatsRow(BuildContext context) {
    final todaySales = toDouble(_data['today_sales']);
    final todayCount = toInt(_data['today_count']);
    final productCount = toInt(_data['product_count']);
    final lowStock = toInt(_data['low_stock_count']);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(children: [
        Row(children: [
          Expanded(child: _PremiumStatCard(title: 'مبيعات اليوم', value: _formatMoney(todaySales), icon: Icons.trending_up_rounded, gradient: AppColors.primaryGradient, delay: 0)),
          const SizedBox(width: 12),
          Expanded(child: _PremiumStatCard(title: 'فواتير اليوم', value: '$todayCount', icon: Icons.receipt_rounded, gradient: AppColors.accentGradient, delay: 100)),
        ]),
        const SizedBox(height: 12),
        Row(children: [
          Expanded(child: _SecondaryStatCard(title: 'المنتجات', value: '$productCount', icon: Icons.inventory_2_rounded, color: const Color(0xFF7C3AED), delay: 200)),
          const SizedBox(width: 12),
          Expanded(child: _SecondaryStatCard(title: 'مخزون منخفض', value: '$lowStock', icon: Icons.warning_rounded, color: lowStock > 0 ? AppColors.warning : AppColors.textTertiary, delay: 300)),
        ]),
      ]),
    );
  }

  Widget _buildLowStockAlert(BuildContext context) {
    final lowStock = toInt(_data['low_stock_count']);
    if (lowStock == 0) return const SizedBox.shrink();
    return Padding(padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
      child: AppCards.modern(
        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (context) => ProductsScreen(api: widget.api))),
        padding: const EdgeInsets.all(14),
        child: Row(children: [
          Container(padding: const EdgeInsets.all(10), decoration: BoxDecoration(color: AppColors.warningBg, borderRadius: BorderRadius.circular(12)),
            child: Icon(Icons.inventory_2_outlined, color: AppColors.warning, size: 24)),
          const SizedBox(width: 14),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('تنبيهات المخزون', style: GoogleFonts.cairo(fontWeight: FontWeight.w700, fontSize: 14, color: AppColors.textPrimary)),
            const SizedBox(height: 2),
            Text('$lowStock منتج تحتاج إعادة تخزين', style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary)),
          ])),
          Icon(Icons.arrow_forward_ios_rounded, size: 14, color: AppColors.warning),
        ]),
      ),
    );
  }

  Widget _buildChartSection(BuildContext context) {
    final daily = _data['daily_totals'] as List<dynamic>? ?? [];
    if (daily.isEmpty) return const SizedBox.shrink();
    final maxVal = daily.fold<double>(1.0, (m, e) => toDouble((e as Map)['total']) > m ? toDouble((e as Map)['total']) : m);

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: AppCards.modern(
        padding: const EdgeInsets.all(22),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
            Text('المبيعات — آخر 7 أيام', style: GoogleFonts.cairo(fontWeight: FontWeight.w700, fontSize: 15, color: AppColors.textPrimary)),
            Container(padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5), decoration: BoxDecoration(color: AppColors.primarySurface, borderRadius: BorderRadius.circular(20)),
              child: Text('أسبوعي', style: GoogleFonts.cairo(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.primary))),
          ]),
          const SizedBox(height: 24),
          SizedBox(height: 140, child: Row(crossAxisAlignment: CrossAxisAlignment.end, children: daily.asMap().entries.map((entry) {
            final map = entry.value as Map<String, dynamic>;
            final total = toDouble(map['total']);
            final label = (map['label'] as String?) ?? '';
            final h = maxVal > 0 ? (total / maxVal) : 0.0;
            final isLast = entry.key == daily.length - 1;
            return Expanded(child: Padding(padding: const EdgeInsets.symmetric(horizontal: 4),
              child: Column(mainAxisAlignment: MainAxisAlignment.end, children: [
                if (isLast && total > 0) Padding(padding: const EdgeInsets.only(bottom: 4),
                  child: Text(_formatMoney(total), style: GoogleFonts.cairo(fontSize: 9, fontWeight: FontWeight.w700, color: AppColors.primary))),
                TweenAnimationBuilder<double>(tween: Tween(begin: 0, end: h), duration: Duration(milliseconds: 600 + entry.key * 80), curve: Curves.easeOutCubic,
                  builder: (context, value, child) => Container(
                    height: _loading ? 4 : (value * 100).clamp(4.0, 100.0),
                    decoration: BoxDecoration(
                      gradient: isLast ? AppColors.primaryGradient : LinearGradient(
                        colors: [AppColors.primary.withValues(alpha: 0.3), AppColors.primary.withValues(alpha: 0.1)],
                        begin: Alignment.bottomCenter,
                        end: Alignment.topCenter,
                      ),
                      borderRadius: BorderRadius.circular(8),
                    ),
                  )),
                const SizedBox(height: 8),
                Text(label, style: GoogleFonts.cairo(fontSize: 10, fontWeight: isLast ? FontWeight.w700 : FontWeight.w400, color: isLast ? AppColors.primary : AppColors.textTertiary), maxLines: 1, overflow: TextOverflow.ellipsis, textAlign: TextAlign.center),
              ]),
            ));
          }).toList())),
        ]),
      ),
    );
  }

  String _formatDate() {
    final now = DateTime.now();
    final months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    return '${now.day} ${months[now.month - 1]} ${now.year}';
  }

  String _formatMoney(double amount) {
    if (amount >= 1000000) return '${(amount / 1000000).toStringAsFixed(1)}م';
    if (amount >= 1000) return '${(amount / 1000).toStringAsFixed(1)}ك';
    return amount.toStringAsFixed(0);
  }
}

// === Premium Stat Card with Gradient ===
class _PremiumStatCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Gradient gradient;
  final int delay;

  const _PremiumStatCard({required this.title, required this.value, required this.icon, required this.gradient, required this.delay});

  @override
  Widget build(BuildContext context) {
    return AppCards.elevated(
      gradient: gradient,
      padding: const EdgeInsets.all(16),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(10)),
            child: Icon(icon, color: Colors.white, size: 20)),
          Text(title, style: GoogleFonts.cairo(fontSize: 12, color: Colors.white.withValues(alpha: 0.8))),
        ]),
        const SizedBox(height: 12),
        AnimatedCounter(value: value, style: GoogleFonts.cairo(fontSize: 22, fontWeight: FontWeight.w800, color: Colors.white)),
      ]),
    );
  }
}

// === Secondary Stat Card ===
class _SecondaryStatCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;
  final int delay;

  const _SecondaryStatCard({required this.title, required this.value, required this.icon, required this.color, required this.delay});

  @override
  Widget build(BuildContext context) {
    return AppCards.modern(padding: const EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
        Container(padding: const EdgeInsets.all(8), decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(10)),
          child: Icon(icon, color: color, size: 20)),
        Text(title, style: GoogleFonts.cairo(fontSize: 12, color: AppColors.textSecondary)),
      ]),
      const SizedBox(height: 12),
      AnimatedCounter(value: value, style: GoogleFonts.cairo(fontSize: 22, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
    ]));
  }
}

// === Loading State ===
class _LoadingState extends StatelessWidget {
  const _LoadingState();

  @override
  Widget build(BuildContext context) {
    return Padding(padding: const EdgeInsets.all(20), child: Column(children: [
      Row(children: [Expanded(child: SkeletonLoader(height: 100, borderRadius: 16)), const SizedBox(width: 12), Expanded(child: SkeletonLoader(height: 100, borderRadius: 16))]),
      const SizedBox(height: 12),
      Row(children: [Expanded(child: SkeletonLoader(height: 100, borderRadius: 16)), const SizedBox(width: 12), Expanded(child: SkeletonLoader(height: 100, borderRadius: 16))]),
      const SizedBox(height: 20),
      const SkeletonLoader(height: 200, borderRadius: 16),
    ]));
  }
}