import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import 'home_screen.dart';
import 'products_screen.dart';
import 'pos_screen.dart';
import 'more_screen.dart';
import 'profile_screen.dart';

class MainShell extends StatefulWidget {
  const MainShell({super.key, required this.api});

  final ApiClient api;

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _index = 0;
  late List<Widget> _screens;

  static const _items = [
    _NavItem(Icons.grid_view_rounded,    Icons.grid_view_outlined,       'الرئيسية'),
    _NavItem(Icons.inventory_2_rounded,  Icons.inventory_2_outlined,     'المنتجات'),
    _NavItem(Icons.point_of_sale_rounded,Icons.point_of_sale_outlined,   'البيع'),
    _NavItem(Icons.apps_rounded,         Icons.apps_outlined,            'المزيد'),
    _NavItem(Icons.person_rounded,       Icons.person_outline_rounded,   'حسابي'),
  ];

  @override
  void initState() {
    super.initState();
    _screens = [
      HomeScreen(api: widget.api),
      ProductsScreen(api: widget.api),
      PosScreen(api: widget.api),
      MoreScreen(api: widget.api),
      ProfileScreen(api: widget.api),
    ];
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: AnnotatedRegion<SystemUiOverlayStyle>(
        value: const SystemUiOverlayStyle(
          statusBarColor: Colors.transparent,
          statusBarIconBrightness: Brightness.dark,
          systemNavigationBarColor: AppColors.navBg,
          systemNavigationBarIconBrightness: Brightness.light,
        ),
        child: Scaffold(
          body: IndexedStack(index: _index, children: _screens),
          bottomNavigationBar: _BottomBar(
            currentIndex: _index,
            items: _items,
            onTap: (i) => setState(() => _index = i),
          ),
        ),
      ),
    );
  }
}

// ─── Bottom Bar ────────────────────────────────────────────────────────────────
class _BottomBar extends StatelessWidget {
  const _BottomBar({
    required this.currentIndex,
    required this.items,
    required this.onTap,
  });

  final int currentIndex;
  final List<_NavItem> items;
  final ValueChanged<int> onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: AppColors.navBg,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        boxShadow: [
          BoxShadow(
            color: Color(0x66000000),
            blurRadius: 30,
            offset: Offset(0, -6),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 10, 12, 8),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: List.generate(
              items.length,
              (i) => _NavButton(
                item: items[i],
                selected: currentIndex == i,
                onTap: () => onTap(i),
                isCenterPOS: i == 2,
              ),
            ),
          ),
        ),
      ),
    );
  }
}

// ─── Nav Item model ───────────────────────────────────────────────────────────
class _NavItem {
  final IconData activeIcon;
  final IconData inactiveIcon;
  final String label;
  const _NavItem(this.activeIcon, this.inactiveIcon, this.label);
}

// ─── Nav Button ───────────────────────────────────────────────────────────────
class _NavButton extends StatelessWidget {
  const _NavButton({
    required this.item,
    required this.selected,
    required this.onTap,
    this.isCenterPOS = false,
  });

  final _NavItem item;
  final bool selected;
  final VoidCallback onTap;
  final bool isCenterPOS;

  @override
  Widget build(BuildContext context) {
    // The center POS button gets a special elevated treatment
    if (isCenterPOS) {
      return GestureDetector(
        onTap: onTap,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 260),
              curve: Curves.easeOutCubic,
              width: 54,
              height: 54,
              decoration: BoxDecoration(
                gradient: selected
                    ? AppColors.primaryGradient
                    : const LinearGradient(
                        colors: [Color(0xFF1E2A4A), Color(0xFF1A2340)]),
                borderRadius: BorderRadius.circular(18),
                boxShadow: selected
                    ? [
                        BoxShadow(
                          color: AppColors.primary.withValues(alpha: 0.45),
                          blurRadius: 18,
                          offset: const Offset(0, 6),
                        ),
                      ]
                    : [],
              ),
              child: Icon(
                selected ? item.activeIcon : item.inactiveIcon,
                size: 24,
                color: selected ? Colors.white : AppColors.navInactive,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              item.label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                color: selected ? AppColors.navActive : AppColors.navInactive,
              ),
            ),
          ],
        ),
      );
    }

    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: SizedBox(
        width: 64,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 230),
              curve: Curves.easeOutCubic,
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: selected
                    ? AppColors.navActive.withValues(alpha: 0.15)
                    : Colors.transparent,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                selected ? item.activeIcon : item.inactiveIcon,
                size: 22,
                color: selected ? AppColors.navActive : AppColors.navInactive,
              ),
            ),
            const SizedBox(height: 3),
            AnimatedDefaultTextStyle(
              duration: const Duration(milliseconds: 180),
              style: TextStyle(
                fontSize: 10,
                fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                color: selected ? AppColors.navActive : AppColors.navInactive,
              ),
              child: Text(item.label),
            ),
          ],
        ),
      ),
    );
  }
}
