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

class _HomeScreenState extends State<HomeScreen>
    with SingleTickerProviderStateMixin {
  bool _loading = true;
  String? _error;
  Map<String, dynamic> _data = {};
  late AnimationController _animCtrl;

  @override
  void initState() {
    super.initState();
    _animCtrl = AnimationController(
      vsync: this, duration: const Duration(milliseconds: 700));
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

  // ── Build ────────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        body: RefreshIndicator(
          color: AppColors.primary,
          displacement: 60,
          onRefresh: _load,
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [
              _buildAppBar(),
              if (_loading)
                const SliverFillRemaining(child: _DashboardSkeleton())
              else if (_error != null)
                SliverFillRemaining(child: _buildError())
              else ...[
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
                  sliver: SliverToBoxAdapter(child: _buildHeroBanner()),
                ),
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
                  sliver: SliverToBoxAdapter(child: _buildStatsGrid(context)),
                ),
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
                  sliver: SliverToBoxAdapter(
                    child: _buildLowStockAlert(context)),
                ),
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                  sliver: SliverToBoxAdapter(child: _buildChart()),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  // ── App Bar ──────────────────────────────────────────────────────────────────
  Widget _buildAppBar() {
    return SliverAppBar(
      expandedHeight: 0,
      floating: true,
      snap: true,
      backgroundColor: AppColors.bg,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      scrolledUnderElevation: 1,
      title: Row(children: [
        Container(
          width: 34, height: 34,
          decoration: BoxDecoration(
            gradient: AppColors.primaryGradient,
            borderRadius: BorderRadius.circular(10)),
          child: const Icon(Icons.point_of_sale_rounded,
            color: Colors.white, size: 18)),
        const SizedBox(width: 10),
        Text('Sober POS',
          style: GoogleFonts.cairo(
            fontWeight: FontWeight.w800, fontSize: 17,
            color: AppColors.textPrimary)),
      ]),
      actions: [
        if (_error != null)
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: AppColors.primary),
            onPressed: _load),
        const SizedBox(width: 4),
      ],
    );
  }

  // ── Hero banner ───────────────────────────────────────────────────────────────
  Widget _buildHeroBanner() {
    final now = DateTime.now();
    final months = [
      'يناير','فبراير','مارس','أبريل','مايو','يونيو',
      'يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر',
    ];
    final dateStr = '${now.day} ${months[now.month - 1]} ${now.year}';

    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        gradient: AppColors.primaryGradient,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.28),
            blurRadius: 22, offset: const Offset(0, 8)),
        ],
      ),
      child: Row(children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('لوحة التحكم',
                style: GoogleFonts.cairo(
                  color: Colors.white, fontSize: 22,
                  fontWeight: FontWeight.w800, letterSpacing: -0.3)),
              const SizedBox(height: 8),
              Row(children: [
                Container(
                  width: 6, height: 6,
                  decoration: BoxDecoration(
                    color: AppColors.accent,
                    shape: BoxShape.circle),
                ),
                const SizedBox(width: 6),
                Text(dateStr,
                  style: GoogleFonts.cairo(
                    fontSize: 12,
                    color: Colors.white.withValues(alpha: 0.70),
                    fontWeight: FontWeight.w500)),
              ]),
            ],
          ),
        ),
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(18)),
          child: const Icon(Icons.dashboard_rounded,
            color: Colors.white, size: 28)),
      ]),
    );
  }

  // ── Stats Grid ────────────────────────────────────────────────────────────────
  Widget _buildStatsGrid(BuildContext context) {
    final todaySales  = toDouble(_data['today_sales']);
    final todayCount  = toInt(_data['today_count']);
    final productCount= toInt(_data['product_count']);
    final lowStock    = toInt(_data['low_stock_count']);

    return Column(children: [
      Row(children: [
        Expanded(child: _GradientStatCard(
          title: 'مبيعات اليوم',
          value: _compactMoney(todaySales),
          subtitle: 'دينار عراقي',
          icon: Icons.trending_up_rounded,
          gradient: AppColors.primaryGradient,
        )),
        const SizedBox(width: 12),
        Expanded(child: _GradientStatCard(
          title: 'فواتير اليوم',
          value: '$todayCount',
          subtitle: 'فاتورة',
          icon: Icons.receipt_long_rounded,
          gradient: AppColors.secondaryGradient,
        )),
      ]),
      const SizedBox(height: 12),
      Row(children: [
        Expanded(child: _FlatStatCard(
          title: 'المنتجات',
          value: '$productCount',
          icon: Icons.inventory_2_rounded,
          iconColor: AppColors.purple,
          iconBg: AppColors.purpleSurface,
        )),
        const SizedBox(width: 12),
        Expanded(child: _FlatStatCard(
          title: 'مخزون منخفض',
          value: '$lowStock',
          icon: Icons.warning_amber_rounded,
          iconColor: lowStock > 0 ? AppColors.warning : AppColors.textTertiary,
          iconBg: lowStock > 0 ? AppColors.warningBg : AppColors.bgSecondary,
        )),
      ]),
    ]);
  }

  // ── Low Stock Alert ──────────────────────────────────────────────────────────
  Widget _buildLowStockAlert(BuildContext context) {
    final lowStock = toInt(_data['low_stock_count']);
    if (lowStock == 0) return const SizedBox.shrink();
    return GestureDetector(
      onTap: () => Navigator.of(context).push(MaterialPageRoute(
        builder: (_) => ProductsScreen(api: widget.api))),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.warningBg,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: AppColors.warning.withValues(alpha: 0.30))),
        child: Row(children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: AppColors.warning.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(14)),
            child: const Icon(Icons.inventory_2_rounded,
              color: AppColors.warning, size: 22)),
          const SizedBox(width: 14),
          Expanded(child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('تنبيهات المخزون',
                style: GoogleFonts.cairo(
                  fontWeight: FontWeight.w700, fontSize: 14,
                  color: AppColors.textPrimary)),
              Text('$lowStock منتج تحتاج إعادة تخزين',
                style: GoogleFonts.cairo(
                  fontSize: 12, color: AppColors.textSecondary)),
            ],
          )),
          const Icon(Icons.chevron_left_rounded,
            size: 20, color: AppColors.warning),
        ]),
      ),
    );
  }

  // ── Sales Chart ───────────────────────────────────────────────────────────────
  Widget _buildChart() {
    final daily = _data['daily_totals'] as List<dynamic>? ?? [];
    if (daily.isEmpty) return const SizedBox.shrink();

    final maxVal = daily.fold<double>(
      1.0,
      (m, e) {
        final v = toDouble((e as Map)['total']);
        return v > m ? v : m;
      },
    );

    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 12, offset: const Offset(0, 3)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('المبيعات — آخر 7 أيام',
                style: GoogleFonts.cairo(
                  fontWeight: FontWeight.w700, fontSize: 15,
                  color: AppColors.textPrimary)),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: AppColors.primarySurface,
                  borderRadius: BorderRadius.circular(20)),
                child: Text('أسبوعي',
                  style: GoogleFonts.cairo(
                    fontSize: 11, fontWeight: FontWeight.w600,
                    color: AppColors.primary))),
            ],
          ),
          const SizedBox(height: 24),
          SizedBox(
            height: 130,
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: daily.asMap().entries.map((entry) {
                final map   = entry.value as Map<String, dynamic>;
                final total = toDouble(map['total']);
                final label = (map['label'] as String?) ?? '';
                final h     = maxVal > 0 ? (total / maxVal) : 0.0;
                final isLast= entry.key == daily.length - 1;

                return Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 3),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.end,
                      children: [
                        if (isLast && total > 0)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 4),
                            child: Text(_compactMoney(total),
                              style: GoogleFonts.cairo(
                                fontSize: 9, fontWeight: FontWeight.w700,
                                color: AppColors.primary))),
                        TweenAnimationBuilder<double>(
                          tween: Tween(begin: 0, end: h),
                          duration: Duration(
                            milliseconds: 500 + entry.key * 70),
                          curve: Curves.easeOutCubic,
                          builder: (_, v, __) => Container(
                            height: (v * 96).clamp(4.0, 96.0),
                            decoration: BoxDecoration(
                              gradient: isLast
                                  ? AppColors.primaryGradient
                                  : LinearGradient(
                                      colors: [
                                        AppColors.primary.withValues(alpha: 0.25),
                                        AppColors.primary.withValues(alpha: 0.08),
                                      ],
                                      begin: Alignment.bottomCenter,
                                      end: Alignment.topCenter,
                                    ),
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(label,
                          style: GoogleFonts.cairo(
                            fontSize: 10,
                            fontWeight: isLast
                              ? FontWeight.w700 : FontWeight.w400,
                            color: isLast
                              ? AppColors.primary : AppColors.textTertiary),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          textAlign: TextAlign.center),
                      ],
                    ),
                  ),
                );
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }

  // ── Error ─────────────────────────────────────────────────────────────────────
  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: AppColors.errorBg,
                borderRadius: BorderRadius.circular(20)),
              child: const Icon(Icons.wifi_off_rounded,
                color: AppColors.error, size: 36)),
            const SizedBox(height: 16),
            Text('فشل تحميل البيانات',
              style: GoogleFonts.cairo(
                fontSize: 16, fontWeight: FontWeight.w700,
                color: AppColors.textPrimary)),
            const SizedBox(height: 8),
            Text('تحقق من الاتصال بالإنترنت',
              style: GoogleFonts.cairo(
                fontSize: 13, color: AppColors.textSecondary)),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: _load,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('إعادة المحاولة'),
            ),
          ],
        ),
      ),
    );
  }

  String _compactMoney(double v) {
    if (v >= 1000000) return '${(v / 1000000).toStringAsFixed(1)}م';
    if (v >= 1000)    return '${(v / 1000).toStringAsFixed(1)}ك';
    return v.toStringAsFixed(0);
  }
}

// ── Gradient Stat Card ────────────────────────────────────────────────────────
class _GradientStatCard extends StatelessWidget {
  const _GradientStatCard({
    required this.title,
    required this.value,
    required this.subtitle,
    required this.icon,
    required this.gradient,
  });

  final String title;
  final String value;
  final String subtitle;
  final IconData icon;
  final Gradient gradient;

  @override
  Widget build(BuildContext context) {
    return AppCards.elevated(
      gradient: gradient,
      margin: EdgeInsets.zero,
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(12)),
                child: Icon(icon, color: Colors.white, size: 20)),
              Text(title,
                style: GoogleFonts.cairo(
                  fontSize: 12,
                  color: Colors.white.withValues(alpha: 0.80),
                  fontWeight: FontWeight.w500)),
            ],
          ),
          const SizedBox(height: 14),
          AnimatedCounter(
            value: value,
            style: GoogleFonts.cairo(
              fontSize: 24, fontWeight: FontWeight.w800,
              color: Colors.white, letterSpacing: -0.5)),
          const SizedBox(height: 2),
          Text(subtitle,
            style: GoogleFonts.cairo(
              fontSize: 11,
              color: Colors.white.withValues(alpha: 0.60))),
        ],
      ),
    );
  }
}

// ── Flat Stat Card ────────────────────────────────────────────────────────────
class _FlatStatCard extends StatelessWidget {
  const _FlatStatCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.iconColor,
    required this.iconBg,
  });

  final String title;
  final String value;
  final IconData icon;
  final Color iconColor;
  final Color iconBg;

  @override
  Widget build(BuildContext context) {
    return AppCards.modern(
      margin: EdgeInsets.zero,
      padding: const EdgeInsets.all(18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: iconBg, borderRadius: BorderRadius.circular(12)),
                child: Icon(icon, color: iconColor, size: 20)),
              Text(title,
                style: GoogleFonts.cairo(
                  fontSize: 12, color: AppColors.textSecondary,
                  fontWeight: FontWeight.w500)),
            ],
          ),
          const SizedBox(height: 14),
          AnimatedCounter(
            value: value,
            style: GoogleFonts.cairo(
              fontSize: 24, fontWeight: FontWeight.w800,
              color: AppColors.textPrimary, letterSpacing: -0.5)),
        ],
      ),
    );
  }
}

// ── Dashboard Skeleton ────────────────────────────────────────────────────────
class _DashboardSkeleton extends StatelessWidget {
  const _DashboardSkeleton();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(20),
      child: Column(children: [
        const SkeletonLoader(height: 90, borderRadius: 24),
        const SizedBox(height: 16),
        Row(children: const [
          Expanded(child: SkeletonLoader(height: 110, borderRadius: 20)),
          SizedBox(width: 12),
          Expanded(child: SkeletonLoader(height: 110, borderRadius: 20)),
        ]),
        const SizedBox(height: 12),
        Row(children: const [
          Expanded(child: SkeletonLoader(height: 90, borderRadius: 20)),
          SizedBox(width: 12),
          Expanded(child: SkeletonLoader(height: 90, borderRadius: 20)),
        ]),
        const SizedBox(height: 16),
        const SkeletonLoader(height: 200, borderRadius: 24),
      ]),
    );
  }
}
