import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import 'sales_screen.dart';
import 'expenses_screen.dart';
import 'purchases_screen.dart';
import 'reports_screen.dart';
import 'categories_screen.dart';
import 'users_screen.dart';
import 'settings_screen.dart';
import 'activity_log_screen.dart';
import 'products_screen.dart';

class MoreScreen extends StatelessWidget {
  const MoreScreen({super.key, required this.api});

  final ApiClient api;

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        body: SafeArea(
          child: CustomScrollView(
            slivers: [
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 8),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'جميع الأقسام',
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.w800,
                          color: AppColors.textPrimary,
                          letterSpacing: -0.5,
                        ),
                      ),
                      const SizedBox(height: 4),
                      const Text(
                        'الوصول السريع لجميع ميزات النظام',
                        style: TextStyle(
                          fontSize: 13,
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
                sliver: SliverGrid(
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    childAspectRatio: 1.1,
                    crossAxisSpacing: 12,
                    mainAxisSpacing: 12,
                  ),
                  delegate: SliverChildListDelegate(
                    _buildMenuItems(context),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  List<Widget> _buildMenuItems(BuildContext context) {
    final items = [
      _MenuItem(
        icon: Icons.receipt_long_rounded,
        label: 'المبيعات',
        subtitle: 'عرض الفواتير',
        color: AppColors.primary,
        bg: AppColors.primary.withValues(alpha: 0.1),
        onTap: () => _push(context, SalesScreen(api: api)),
      ),
      _MenuItem(
        icon: Icons.money_off_rounded,
        label: 'المصروفات',
        subtitle: 'إدارة المصاريف',
        color: AppColors.error,
        bg: AppColors.errorBg,
        onTap: () => _push(context, ExpensesScreen(api: api)),
        adminOnly: true,
      ),
      _MenuItem(
        icon: Icons.shopping_bag_rounded,
        label: 'المشتريات',
        subtitle: 'استلام البضاعة',
        color: const Color(0xFF7C3AED),
        bg: const Color(0xFFF0EBFF),
        onTap: () => _push(context, PurchasesScreen(api: api)),
        adminOnly: true,
      ),
      _MenuItem(
        icon: Icons.bar_chart_rounded,
        label: 'التقارير',
        subtitle: 'إحصائيات وأرباح',
        color: AppColors.success,
        bg: AppColors.successBg,
        onTap: () => _push(context, ReportsScreen(api: api)),
        adminOnly: true,
      ),
      _MenuItem(
        icon: Icons.category_rounded,
        label: 'التصنيفات',
        subtitle: 'تصنيفات المنتجات',
        color: AppColors.accent,
        bg: AppColors.warningBg,
        onTap: () => _push(context, CategoriesScreen(api: api)),
      ),
      _MenuItem(
        icon: Icons.inventory_2_rounded,
        label: 'إدارة المنتجات',
        subtitle: 'إضافة وتعديل',
        color: const Color(0xFF0891B2),
        bg: const Color(0xFFE0F7FA),
        onTap: () => _push(context, ProductsScreen(api: api, editMode: true)),
      ),
      _MenuItem(
        icon: Icons.people_rounded,
        label: 'المستخدمين',
        subtitle: 'إدارة الحسابات',
        color: const Color(0xFFDB2777),
        bg: const Color(0xFFFCE7F3),
        onTap: () => _push(context, UsersScreen(api: api)),
        adminOnly: true,
      ),
      _MenuItem(
        icon: Icons.history_rounded,
        label: 'سجل النشاط',
        subtitle: 'تتبع العمليات',
        color: AppColors.textSecondary,
        bg: AppColors.surfaceVariant,
        onTap: () => _push(context, ActivityLogScreen(api: api)),
        adminOnly: true,
      ),
      _MenuItem(
        icon: Icons.settings_rounded,
        label: 'الإعدادات',
        subtitle: 'إعدادات النظام',
        color: AppColors.bgDark,
        bg: AppColors.outline,
        onTap: () => _push(context, SettingsScreen(api: api)),
        adminOnly: true,
      ),
    ];

    return items.map((item) => _MenuCard(item: item)).toList();
  }

  void _push(BuildContext context, Widget screen) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(builder: (_) => screen),
    );
  }
}

class _MenuItem {
  final IconData icon;
  final String label;
  final String subtitle;
  final Color color;
  final Color bg;
  final VoidCallback onTap;
  final bool adminOnly;

  const _MenuItem({
    required this.icon,
    required this.label,
    required this.subtitle,
    required this.color,
    required this.bg,
    required this.onTap,
    this.adminOnly = false,
  });
}

class _MenuCard extends StatelessWidget {
  const _MenuCard({required this.item});

  final _MenuItem item;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: item.onTap,
      child: Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: AppColors.card,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 12,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: item.bg,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(item.icon, color: item.color, size: 24),
                ),
                if (item.adminOnly)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: AppColors.accent.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Text(
                      'مدير',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                        color: AppColors.accent,
                      ),
                    ),
                  ),
              ],
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.label,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  item.subtitle,
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
