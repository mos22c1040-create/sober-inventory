import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../utils/api_parse.dart';
import '../models/product.dart';
import 'sales_screen.dart';
import 'expenses_screen.dart';
import 'purchases_screen.dart';
import 'reports_screen.dart';
import 'categories_screen.dart';
import 'types_screen.dart';
import 'users_screen.dart';
import 'settings_screen.dart';
import 'activity_log_screen.dart';
import 'products_screen.dart';
import 'pnl_dashboard_screen.dart';
import 'return_screen.dart';
import 'barcode_scanner_screen.dart';
import 'product_form_screen.dart';

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
        icon: Icons.qr_code_scanner_rounded,
        label: 'مسح باركود',
        subtitle: 'بحث عن منتج بالباركود',
        color: const Color(0xFF6366F1),
        bg: const Color(0xFFEEF2FF),
        onTap: () => _openBarcodeScanAndSearch(context),
      ),
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
        subtitle: 'قائمة واستلام البضاعة',
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
        icon: Icons.analytics_rounded,
        label: 'التقرير المالي',
        subtitle: 'الأرباح والخسائر',
        color: const Color(0xFF059669),
        bg: const Color(0xFFD1FAE5),
        onTap: () => _push(context, PnLDashboardScreen(api: api)),
        adminOnly: true,
      ),
      _MenuItem(
        icon: Icons.undo_rounded,
        label: 'إرجاع المنتجات',
        subtitle: 'استرجاع البضاعة',
        color: const Color(0xFFDC2626),
        bg: const Color(0xFFFEE2E2),
        onTap: () => _push(context, ReturnScreen(api: api)),
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
        icon: Icons.layers_rounded,
        label: 'الأنواع',
        subtitle: 'أنواع المنتجات',
        color: const Color(0xFF0D9488),
        bg: const Color(0xFFCCFBF1),
        onTap: () => _push(context, TypesScreen(api: api)),
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

  Future<void> _openBarcodeScanAndSearch(BuildContext context) async {
    final barcode = await Navigator.of(context).push<String>(
      MaterialPageRoute<String>(
        builder: (_) => const BarcodeScannerScreen(title: 'مسح باركود للبحث'),
      ),
    );
    if (barcode == null || barcode.isEmpty) return;
    try {
      final res = await api.findByBarcode(barcode);
      final product = res['product'];
      if (!context.mounted) return;
      if (product == null || product is! Map) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('لم يُعثر على منتج بالباركود: $barcode'),
            backgroundColor: AppColors.error,
            behavior: SnackBarBehavior.floating,
          ),
        );
        return;
      }
      final prod = Map<String, dynamic>.from(product);
      showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
        builder: (ctx) => Directionality(
          textDirection: TextDirection.rtl,
          child: SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Row(
                    children: [
                      Icon(Icons.check_circle_rounded, color: AppColors.success, size: 28),
                      const SizedBox(width: 10),
                      const Text(
                        'تم العثور على المنتج',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w800,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Text(
                    (prod['name'] ?? '—').toString(),
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'السعر: ${toDouble(prod['price']).toStringAsFixed(0)} د.ع · الكمية: ${toInt(prod['quantity'])}',
                    style: const TextStyle(fontSize: 13, color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 20),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: () => Navigator.pop(ctx),
                          child: const Text('إغلاق'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: FilledButton.icon(
                          onPressed: () async {
                            Navigator.pop(ctx);
                            final csrf = await api.getCsrfToken();
                            if (!context.mounted) return;
                            Navigator.of(context).push(
                              MaterialPageRoute<void>(
                                builder: (_) => ProductFormScreen(
                                  api: api,
                                  csrfToken: csrf,
                                  product: Product.fromJson(prod),
                                  onSaved: () => Navigator.pop(context),
                                ),
                              ),
                            );
                          },
                          icon: const Icon(Icons.edit_rounded, size: 18),
                          label: const Text('تعديل المنتج'),
                          style: FilledButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      );
    } catch (_) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('المنتج غير موجود أو تعذر الاتصال — $barcode'),
            backgroundColor: AppColors.error,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    }
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
