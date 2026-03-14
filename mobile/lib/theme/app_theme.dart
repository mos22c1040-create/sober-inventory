import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show SystemUiOverlayStyle;
import 'package:google_fonts/google_fonts.dart';

// ─── Color System ────────────────────────────────────────────────────────────
class AppColors {
  // Primary — Indigo (aligns with web dashboard)
  static const Color primary        = Color(0xFF4F46E5);
  static const Color primaryLight   = Color(0xFF818CF8);
  static const Color primaryDark    = Color(0xFF4338CA);
  static const Color primarySurface = Color(0xFFEEEEFF);

  // Secondary — Emerald
  static const Color secondary        = Color(0xFF059669);
  static const Color secondaryLight   = Color(0xFF10B981);
  static const Color secondarySurface = Color(0xFFECFDF5);

  // Accent — Amber
  static const Color accent        = Color(0xFFF59E0B);
  static const Color accentSurface = Color(0xFFFEF3C7);

  // Semantic
  static const Color success    = Color(0xFF10B981);
  static const Color successBg  = Color(0xFFD1FAE5);
  static const Color error      = Color(0xFFEF4444);
  static const Color errorBg    = Color(0xFFFEE2E2);
  static const Color warning    = Color(0xFFF59E0B);
  static const Color warningBg  = Color(0xFFFEF3C7);
  static const Color info       = Color(0xFF3B82F6);
  static const Color infoBg     = Color(0xFFDBEAFE);

  // Purple stat card
  static const Color purple        = Color(0xFF7C3AED);
  static const Color purpleSurface = Color(0xFFEDE9FE);

  // Neutrals
  static const Color bg            = Color(0xFFF9FAFB);
  static const Color bgSecondary   = Color(0xFFF3F4F6);
  static const Color card          = Color(0xFFFFFFFF);
  static const Color surface       = Color(0xFFFFFFFF);
  static const Color border        = Color(0xFFE5E7EB);
  static const Color borderLight   = Color(0xFFF3F4F6);
  static const Color divider       = Color(0xFFE5E7EB);

  // Text
  static const Color textPrimary   = Color(0xFF111827);
  static const Color textSecondary = Color(0xFF6B7280);
  static const Color textTertiary  = Color(0xFF9CA3AF);
  static const Color textOnPrimary = Color(0xFFFFFFFF);
  static const Color textOnDark    = Color(0xFFFFFFFF);

  // Nav (always dark)
  static const Color navBg       = Color(0xFF0A0E1A);
  static const Color navActive   = Color(0xFF818CF8);
  static const Color navInactive = Color(0xFF6B7280);

  // Dark surfaces
  static const Color bgDark   = Color(0xFF0A0E1A);
  static const Color cardDark = Color(0xFF161F3A);

  // Gradients
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFF4F46E5), Color(0xFF818CF8)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient secondaryGradient = LinearGradient(
    colors: [Color(0xFF059669), Color(0xFF10B981)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient accentGradient = LinearGradient(
    colors: [Color(0xFFF59E0B), Color(0xFFFBBF24)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient purpleGradient = LinearGradient(
    colors: [Color(0xFF7C3AED), Color(0xFFA78BFA)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  // Legacy aliases
  static const Color outline          = border;
  static const Color textHint         = textTertiary;
  static const Color surfaceVariant   = bgSecondary;
  static const Color premiumGradientA = primary;
  // keep old gradient name working
  static const LinearGradient premiumGradient = primaryGradient;
}

// ─── Theme ───────────────────────────────────────────────────────────────────
class AppTheme {
  static ThemeData get light {
    final base = ThemeData(
      useMaterial3: true,
      fontFamily: GoogleFonts.cairo().fontFamily,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.primary,
        primary: AppColors.primary,
        secondary: AppColors.secondary,
        surface: AppColors.surface,
        error: AppColors.error,
        onPrimary: Colors.white,
        onSecondary: Colors.white,
        onSurface: AppColors.textPrimary,
        brightness: Brightness.light,
      ),
      scaffoldBackgroundColor: AppColors.bg,

      // AppBar
      appBarTheme: AppBarTheme(
        elevation: 0,
        scrolledUnderElevation: 0.5,
        centerTitle: true,
        backgroundColor: AppColors.bg,
        foregroundColor: AppColors.textPrimary,
        systemOverlayStyle: const SystemUiOverlayStyle(
          statusBarColor: Colors.transparent,
          statusBarIconBrightness: Brightness.dark,
        ),
        titleTextStyle: GoogleFonts.cairo(
          fontSize: 17,
          fontWeight: FontWeight.w700,
          color: AppColors.textPrimary,
          letterSpacing: -0.3,
        ),
      ),

      // Cards
      cardTheme: CardThemeData(
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: const BorderSide(color: AppColors.border, width: 1),
        ),
        color: AppColors.card,
        surfaceTintColor: Colors.transparent,
        margin: EdgeInsets.zero,
      ),

      // Inputs
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.bgSecondary,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: AppColors.border, width: 1.5),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: AppColors.error, width: 1.5),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: AppColors.error, width: 2),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        hintStyle: GoogleFonts.cairo(color: AppColors.textTertiary, fontSize: 14),
        prefixIconColor: AppColors.textSecondary,
        suffixIconColor: AppColors.textSecondary,
      ),

      // Buttons
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          minimumSize: const Size(0, 52),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          textStyle: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.w700),
        ),
      ),

      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.primary,
          minimumSize: const Size(0, 52),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
          side: const BorderSide(color: AppColors.border, width: 1.5),
          textStyle: GoogleFonts.cairo(fontSize: 15, fontWeight: FontWeight.w600),
        ),
      ),

      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.primary,
          textStyle: GoogleFonts.cairo(fontSize: 14, fontWeight: FontWeight.w600),
        ),
      ),

      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 0,
        shape: CircleBorder(),
      ),

      chipTheme: ChipThemeData(
        backgroundColor: AppColors.bgSecondary,
        selectedColor: AppColors.primarySurface,
        labelStyle: GoogleFonts.cairo(fontSize: 13, fontWeight: FontWeight.w500),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        side: const BorderSide(color: AppColors.border),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      ),

      listTileTheme: ListTileThemeData(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        titleTextStyle: GoogleFonts.cairo(
          fontSize: 15, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
        subtitleTextStyle: GoogleFonts.cairo(
          fontSize: 13, color: AppColors.textSecondary),
      ),

      dividerTheme: const DividerThemeData(
        color: AppColors.divider, thickness: 1, space: 1),

      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        backgroundColor: AppColors.textPrimary,
        contentTextStyle: GoogleFonts.cairo(color: Colors.white, fontSize: 14),
        elevation: 0,
      ),

      dialogTheme: DialogThemeData(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        backgroundColor: AppColors.card,
        elevation: 0,
        titleTextStyle: GoogleFonts.cairo(
          fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
      ),

      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: AppColors.card,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(28))),
        elevation: 0,
      ),

      progressIndicatorTheme: const ProgressIndicatorThemeData(
        color: AppColors.primary,
        linearTrackColor: AppColors.border,
      ),
    );

    return base.copyWith(
      textTheme: GoogleFonts.cairoTextTheme(base.textTheme),
    );
  }
}

// ─── Card Components ──────────────────────────────────────────────────────────
class AppCards {
  /// Standard white card with 1px border + subtle shadow
  static Widget modern({
    required Widget child,
    EdgeInsetsGeometry? padding,
    EdgeInsetsGeometry? margin,
    VoidCallback? onTap,
    Color? color,
  }) {
    return Container(
      margin: margin ?? const EdgeInsets.symmetric(vertical: 6),
      decoration: BoxDecoration(
        color: color ?? AppColors.card,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border, width: 1),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(20),
          child: Padding(
            padding: padding ?? const EdgeInsets.all(16),
            child: child,
          ),
        ),
      ),
    );
  }

  /// Gradient card with colored glow shadow
  static Widget elevated({
    required Widget child,
    EdgeInsetsGeometry? padding,
    EdgeInsetsGeometry? margin,
    VoidCallback? onTap,
    Gradient? gradient,
    Color? color,
  }) {
    return Container(
      margin: margin ?? const EdgeInsets.symmetric(vertical: 6),
      decoration: BoxDecoration(
        gradient: gradient,
        color: gradient == null ? (color ?? AppColors.card) : null,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: (gradient?.colors.first ?? AppColors.primary)
                .withValues(alpha: 0.22),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(20),
          child: Padding(
            padding: padding ?? const EdgeInsets.all(16),
            child: child,
          ),
        ),
      ),
    );
  }
}

// ─── Skeleton Loader ──────────────────────────────────────────────────────────
class SkeletonLoader extends StatefulWidget {
  final double width;
  final double height;
  final double borderRadius;

  const SkeletonLoader({
    super.key,
    this.width = double.infinity,
    this.height = 20,
    this.borderRadius = 10,
  });

  @override
  State<SkeletonLoader> createState() => _SkeletonLoaderState();
}

class _SkeletonLoaderState extends State<SkeletonLoader>
    with SingleTickerProviderStateMixin {
  late AnimationController _ctrl;
  late Animation<double> _anim;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this, duration: const Duration(milliseconds: 1400))
      ..repeat();
    _anim = Tween<double>(begin: -2, end: 2).animate(
      CurvedAnimation(parent: _ctrl, curve: Curves.easeInOutSine));
  }

  @override
  void dispose() { _ctrl.dispose(); super.dispose(); }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _anim,
      builder: (_, __) => Container(
        width: widget.width,
        height: widget.height,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(widget.borderRadius),
          gradient: LinearGradient(
            begin: Alignment(-1.0 + _anim.value, 0),
            end: Alignment(1.0 + _anim.value, 0),
            colors: const [
              AppColors.border,
              AppColors.bgSecondary,
              AppColors.border,
            ],
            stops: const [0.0, 0.5, 1.0],
          ),
        ),
      ),
    );
  }
}

class SkeletonListTile extends StatelessWidget {
  const SkeletonListTile({super.key});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 20),
      child: Row(children: [
        const SkeletonLoader(width: 52, height: 52, borderRadius: 14),
        const SizedBox(width: 14),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const SkeletonLoader(height: 15),
            const SizedBox(height: 8),
            SkeletonLoader(
              width: MediaQuery.of(context).size.width * 0.35, height: 12),
          ]),
        ),
      ]),
    );
  }
}

// ─── Empty State ──────────────────────────────────────────────────────────────
class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  final Widget? action;

  const EmptyState({
    super.key,
    required this.icon,
    required this.title,
    this.subtitle,
    this.action,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80, height: 80,
              decoration: BoxDecoration(
                color: AppColors.primarySurface,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(
                  color: AppColors.primary.withValues(alpha: 0.15)),
              ),
              child: Icon(icon, size: 36, color: AppColors.primary),
            ),
            const SizedBox(height: 20),
            Text(title,
              style: GoogleFonts.cairo(
                fontSize: 17, fontWeight: FontWeight.w700,
                color: AppColors.textPrimary),
              textAlign: TextAlign.center),
            if (subtitle != null) ...[
              const SizedBox(height: 6),
              Text(subtitle!,
                style: GoogleFonts.cairo(
                  fontSize: 13, color: AppColors.textSecondary),
                textAlign: TextAlign.center),
            ],
            if (action != null) ...[
              const SizedBox(height: 24),
              action!,
            ],
          ],
        ),
      ),
    );
  }
}

// ─── Animated Counter ─────────────────────────────────────────────────────────
class AnimatedCounter extends StatelessWidget {
  final String value;
  final TextStyle? style;
  final Duration duration;

  const AnimatedCounter({
    super.key,
    required this.value,
    this.style,
    this.duration = const Duration(milliseconds: 500),
  });

  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: duration,
      builder: (_, opacity, __) =>
          Opacity(opacity: opacity, child: Text(value, style: style)),
    );
  }
}

// ─── Section Header ───────────────────────────────────────────────────────────
class SectionHeader extends StatelessWidget {
  final String title;
  final String? actionLabel;
  final VoidCallback? onAction;

  const SectionHeader({
    super.key,
    required this.title,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 10),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(title,
            style: GoogleFonts.cairo(
              fontSize: 15, fontWeight: FontWeight.w700,
              color: AppColors.textPrimary)),
          if (actionLabel != null)
            TextButton(
              onPressed: onAction,
              child: Text(actionLabel!,
                style: GoogleFonts.cairo(
                  fontSize: 13, fontWeight: FontWeight.w600,
                  color: AppColors.primary)),
            ),
        ],
      ),
    );
  }
}

// ─── Status Badge ─────────────────────────────────────────────────────────────
class AppBadge extends StatelessWidget {
  final String label;
  final Color color;
  final Color? bgColor;
  final double fontSize;

  const AppBadge({
    super.key,
    required this.label,
    required this.color,
    this.bgColor,
    this.fontSize = 11,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bgColor ?? color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(label,
        style: GoogleFonts.cairo(
          fontSize: fontSize, fontWeight: FontWeight.w700, color: color)),
    );
  }
}

// ─── Primary Button with Scale Feedback ───────────────────────────────────────
class AppPrimaryButton extends StatefulWidget {
  final String label;
  final IconData? icon;
  final VoidCallback? onPressed;
  final bool loading;
  final Color? color;
  final Gradient? gradient;
  final double height;

  const AppPrimaryButton({
    super.key,
    required this.label,
    this.icon,
    this.onPressed,
    this.loading = false,
    this.color,
    this.gradient,
    this.height = 52,
  });

  @override
  State<AppPrimaryButton> createState() => _AppPrimaryButtonState();
}

class _AppPrimaryButtonState extends State<AppPrimaryButton>
    with SingleTickerProviderStateMixin {
  late AnimationController _ctrl;
  late Animation<double> _scale;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this, duration: const Duration(milliseconds: 100),
      lowerBound: 0.0, upperBound: 0.04);
    _scale = Tween<double>(begin: 1.0, end: 0.96).animate(
      CurvedAnimation(parent: _ctrl, curve: Curves.easeOut));
  }

  @override
  void dispose() { _ctrl.dispose(); super.dispose(); }

  @override
  Widget build(BuildContext context) {
    return ScaleTransition(
      scale: _scale,
      child: GestureDetector(
        onTapDown: (_) { if (widget.onPressed != null) _ctrl.forward(); },
        onTapUp: (_) { _ctrl.reverse(); widget.onPressed?.call(); },
        onTapCancel: () => _ctrl.reverse(),
        child: Container(
          height: widget.height,
          decoration: BoxDecoration(
            gradient: widget.gradient ??
                (widget.color == null ? AppColors.primaryGradient : null),
            color: widget.gradient == null ? widget.color : null,
            borderRadius: BorderRadius.circular(14),
            boxShadow: [
              BoxShadow(
                color: (widget.color ?? AppColors.primary)
                    .withValues(alpha: 0.30),
                blurRadius: 14,
                offset: const Offset(0, 5),
              ),
            ],
          ),
          child: Center(
            child: widget.loading
                ? const SizedBox(
                    width: 22, height: 22,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.5, color: Colors.white))
                : Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (widget.icon != null) ...[
                        Icon(widget.icon, color: Colors.white, size: 20),
                        const SizedBox(width: 8),
                      ],
                      Text(widget.label,
                        style: GoogleFonts.cairo(
                          fontSize: 15, fontWeight: FontWeight.w700,
                          color: Colors.white)),
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}

// ─── Icon Button ─────────────────────────────────────────────────────────────
class AppIconButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback? onTap;
  final Color color;
  final Color bgColor;
  final double size;

  const AppIconButton({
    super.key,
    required this.icon,
    this.onTap,
    required this.color,
    required this.bgColor,
    this.size = 48,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: size, height: size,
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: color.withValues(alpha: 0.15), width: 1),
        ),
        child: Icon(icon, color: color, size: size * 0.45),
      ),
    );
  }
}
