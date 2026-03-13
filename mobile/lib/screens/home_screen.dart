import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';

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
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );
    _load();
  }

  @override
  void dispose() {
    _animCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
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
        body: RefreshIndicator(
          color: AppColors.primary,
          onRefresh: _load,
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [
              _buildAppBar(),
              if (_loading)
                const SliverFillRemaining(
                  child: _LoadingCards(),
                )
              else ...[
                SliverToBoxAdapter(
                  child: Column(
                    children: [
                      _buildHeader(),
                      const SizedBox(height: 4),
                      _buildStatsRow(context),
                      const SizedBox(height: 20),
                      _buildChartSection(context),
                      const SizedBox(height: 24),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildAppBar() {
    return SliverAppBar(
      expandedHeight: 0,
      floating: true,
      snap: true,
      backgroundColor: AppColors.bg,
      elevation: 0,
      title: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [AppColors.primary, AppColors.primaryDark],
              ),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(Icons.point_of_sale_rounded,
                color: Colors.white, size: 18),
          ),
          const SizedBox(width: 12),
          const Text(
            'Sober POS',
            style: TextStyle(
              fontWeight: FontWeight.w800,
              fontSize: 18,
              color: AppColors.textPrimary,
            ),
          ),
        ],
      ),
      actions: [
        if (_error != null)
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: AppColors.primary),
            onPressed: _load,
          ),
        const SizedBox(width: 4),
      ],
    );
  }

  Widget _buildHeader() {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 4, 20, 20),
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.bgDark, const Color(0xFF1A3070)],
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: AppColors.bgDark.withValues(alpha: 0.35),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'مرحباً 👋',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.6),
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 6),
                const Text(
                  'لوحة التحكم',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.5,
                  ),
                ),
                const SizedBox(height: 10),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 7,
                        height: 7,
                        decoration: BoxDecoration(
                          color: AppColors.success,
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        _formatDate(),
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.75),
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(18),
            ),
            child: const Icon(
              Icons.dashboard_rounded,
              color: Colors.white,
              size: 32,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsRow(BuildContext context) {
    final todaySales = (_data['today_sales'] as num?)?.toDouble() ?? 0.0;
    final todayCount = (_data['today_count'] as int?) ?? 0;
    final productCount = (_data['product_count'] as int?) ?? 0;
    final lowStock = (_data['low_stock_count'] as int?) ?? 0;

    final stats = [
      _StatData('مبيعات اليوم', _formatMoney(todaySales), Icons.trending_up_rounded,
          AppColors.primary, AppColors.primary.withValues(alpha: 0.1)),
      _StatData('فواتير اليوم', '$todayCount', Icons.receipt_rounded,
          AppColors.success, AppColors.successBg),
      _StatData('المنتجات', '$productCount', Icons.inventory_2_rounded,
          const Color(0xFF7C3AED), const Color(0xFFF0EBFF)),
      _StatData('مخزون منخفض', '$lowStock', Icons.warning_rounded,
          lowStock > 0 ? AppColors.warning : AppColors.textSecondary,
          lowStock > 0 ? AppColors.warningBg : AppColors.bg),
    ];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(child: _StatCard(data: stats[0], anim: _animCtrl, delay: 0)),
              const SizedBox(width: 12),
              Expanded(child: _StatCard(data: stats[1], anim: _animCtrl, delay: 100)),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(child: _StatCard(data: stats[2], anim: _animCtrl, delay: 200)),
              const SizedBox(width: 12),
              Expanded(child: _StatCard(data: stats[3], anim: _animCtrl, delay: 300)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildChartSection(BuildContext context) {
    final daily = _data['daily_totals'] as List<dynamic>? ?? [];
    if (daily.isEmpty) return const SizedBox.shrink();

    final maxVal = daily.fold<double>(
      1.0,
      (m, e) {
        final t = (e as Map)['total'] as num?;
        return t != null && t > m ? t.toDouble() : m;
      },
    );

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Container(
        padding: const EdgeInsets.all(22),
        decoration: BoxDecoration(
          color: AppColors.card,
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 16,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'المبيعات — آخر 7 أيام',
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 15,
                    color: AppColors.textPrimary,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Text(
                    'أسبوعي',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primary,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            SizedBox(
              height: 140,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: daily.asMap().entries.map((entry) {
                  final map = entry.value as Map<String, dynamic>;
                  final total = (map['total'] as num?)?.toDouble() ?? 0.0;
                  final label = (map['label'] as String?) ?? '';
                  final h = maxVal > 0 ? (total / maxVal) : 0.0;
                  final isLast = entry.key == daily.length - 1;

                  return Expanded(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          if (isLast && total > 0)
                            Padding(
                              padding: const EdgeInsets.only(bottom: 4),
                              child: Text(
                                _formatMoney(total),
                                style: TextStyle(
                                  fontSize: 9,
                                  fontWeight: FontWeight.w700,
                                  color: AppColors.primary,
                                ),
                              ),
                            ),
                          AnimatedContainer(
                            duration: Duration(milliseconds: 600 + entry.key * 80),
                            curve: Curves.easeOutCubic,
                            height: _loading ? 4 : (h * 100).clamp(4.0, 100.0),
                            decoration: BoxDecoration(
                              gradient: isLast
                                  ? LinearGradient(
                                      colors: [AppColors.primary, AppColors.primaryLight],
                                      begin: Alignment.bottomCenter,
                                      end: Alignment.topCenter,
                                    )
                                  : LinearGradient(
                                      colors: [
                                        AppColors.primary.withValues(alpha: 0.3),
                                        AppColors.primary.withValues(alpha: 0.15),
                                      ],
                                      begin: Alignment.bottomCenter,
                                      end: Alignment.topCenter,
                                    ),
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            label,
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: isLast ? FontWeight.w700 : FontWeight.w400,
                              color: isLast ? AppColors.primary : AppColors.textHint,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatMoney(double n) {
    if (n >= 1000000) return '${(n / 1e6).toStringAsFixed(1)}م';
    if (n >= 1000) return '${(n / 1000).toStringAsFixed(1)}ك';
    return n.toStringAsFixed(0);
  }

  String _formatDate() {
    final now = DateTime.now();
    const days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    const months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    return '${days[now.weekday % 7]}، ${now.day} ${months[now.month - 1]}';
  }
}

class _StatData {
  final String label;
  final String value;
  final IconData icon;
  final Color color;
  final Color bg;
  const _StatData(this.label, this.value, this.icon, this.color, this.bg);
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.data,
    required this.anim,
    required this.delay,
  });

  final _StatData data;
  final AnimationController anim;
  final int delay;

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: anim,
      builder: (context, child) {
        final t = ((anim.value - delay / 1000.0) / 0.4).clamp(0.0, 1.0);
        final curved = Curves.easeOutCubic.transform(t);
        return Opacity(
          opacity: curved,
          child: Transform.translate(
            offset: Offset(0, 16 * (1 - curved)),
            child: child,
          ),
        );
      },
      child: Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: AppColors.card,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 14,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: data.bg,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(data.icon, color: data.color, size: 22),
            ),
            const SizedBox(height: 16),
            Text(
              data.value,
              style: const TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w800,
                color: AppColors.textPrimary,
                letterSpacing: -0.5,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              data.label,
              style: const TextStyle(
                fontSize: 12,
                color: AppColors.textSecondary,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _LoadingCards extends StatelessWidget {
  const _LoadingCards();

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          _shimmer(height: 140, radius: 24),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(child: _shimmer(height: 110, radius: 20)),
              const SizedBox(width: 12),
              Expanded(child: _shimmer(height: 110, radius: 20)),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(child: _shimmer(height: 110, radius: 20)),
              const SizedBox(width: 12),
              Expanded(child: _shimmer(height: 110, radius: 20)),
            ],
          ),
          const SizedBox(height: 16),
          _shimmer(height: 200, radius: 24),
        ],
      ),
    );
  }

  Widget _shimmer({required double height, required double radius}) {
    return Container(
      height: height,
      decoration: BoxDecoration(
        color: AppColors.outline,
        borderRadius: BorderRadius.circular(radius),
      ),
    );
  }
}
