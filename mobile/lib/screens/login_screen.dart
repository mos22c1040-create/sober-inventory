import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import 'main_shell.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen>
    with SingleTickerProviderStateMixin {
  final _emailCtrl = TextEditingController();
  final _passCtrl  = TextEditingController();
  final _formKey   = GlobalKey<FormState>();

  bool _loading  = false;
  bool _obscure  = true;
  String? _error;

  late AnimationController _animCtrl;
  late Animation<double>  _fadeAnim;
  late Animation<Offset>  _slideAnim;

  @override
  void initState() {
    super.initState();
    _animCtrl = AnimationController(
      vsync: this, duration: const Duration(milliseconds: 800));
    _fadeAnim = CurvedAnimation(parent: _animCtrl, curve: Curves.easeOut);
    _slideAnim = Tween<Offset>(
      begin: const Offset(0, 0.10), end: Offset.zero)
        .animate(CurvedAnimation(parent: _animCtrl, curve: Curves.easeOutCubic));
    _animCtrl.forward();
  }

  @override
  void dispose() {
    _animCtrl.dispose();
    _emailCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  // ── Business Logic (unchanged) ──────────────────────────────────────────────
  Future<void> _handleLogin() async {
    if (_emailCtrl.text.trim().isEmpty || _passCtrl.text.isEmpty) {
      setState(() => _error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور');
      return;
    }
    setState(() { _loading = true; _error = null; });
    try {
      final res = await widget.api.login(
        email: _emailCtrl.text.trim(), password: _passCtrl.text);
      if (res['success'] == true) {
        if (!mounted) return;
        Navigator.of(context).pushReplacement(MaterialPageRoute<void>(
          builder: (_) => MainShell(api: widget.api)));
      } else {
        setState(() =>
          _error = (res['error'] ?? res['message'] ?? 'البريد أو كلمة المرور خاطئة').toString());
      }
    } on DioException catch (e) {
      String msg = 'تعذر الاتصال بالخادم';
      final d = e.response?.data;
      if (d is Map) { msg = (d['error'] ?? d['message'] ?? msg).toString(); }
      else if (e.type == DioExceptionType.connectionTimeout ||
               e.type == DioExceptionType.connectionError) {
        msg = 'تحقق من الإنترنت أو أن السيرفر يعمل';
      }
      setState(() => _error = msg);
    } catch (_) {
      setState(() => _error = 'تعذر الاتصال بالخادم');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ── UI ──────────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.sizeOf(context);
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
      ),
      child: Directionality(
        textDirection: TextDirection.rtl,
        child: Scaffold(
          backgroundColor: AppColors.navBg,
          body: Stack(
            children: [
              // ── Background blobs ─────────────────────────────────────
              Positioned(
                top: -size.height * 0.12,
                right: -size.width * 0.30,
                child: _glow(size.width * 0.85,
                  AppColors.primary.withValues(alpha: 0.18))),
              Positioned(
                bottom: size.height * 0.08,
                left: -size.width * 0.25,
                child: _glow(size.width * 0.60,
                  AppColors.secondary.withValues(alpha: 0.10))),

              // ── Main content ─────────────────────────────────────────
              SafeArea(
                child: Center(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 24, vertical: 24),
                    child: FadeTransition(
                      opacity: _fadeAnim,
                      child: SlideTransition(
                        position: _slideAnim,
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            _buildBrandSection(),
                            const SizedBox(height: 36),
                            _buildCard(),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _glow(double size, Color color) => Container(
    width: size, height: size,
    decoration: BoxDecoration(shape: BoxShape.circle, color: color));

  // ── Brand ──────────────────────────────────────────────────────────────────
  Widget _buildBrandSection() {
    return Column(children: [
      Container(
        width: 80, height: 80,
        decoration: BoxDecoration(
          gradient: AppColors.primaryGradient,
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: AppColors.primary.withValues(alpha: 0.45),
              blurRadius: 28, offset: const Offset(0, 10)),
          ],
        ),
        child: const Icon(Icons.point_of_sale_rounded,
          size: 38, color: Colors.white),
      ),
      const SizedBox(height: 18),
      Text('Sober POS',
        style: GoogleFonts.cairo(
          fontSize: 30, fontWeight: FontWeight.w800,
          color: Colors.white, letterSpacing: -0.5)),
      const SizedBox(height: 4),
      Text('نظام المخزون والمبيعات',
        style: GoogleFonts.cairo(
          fontSize: 13, color: Colors.white.withValues(alpha: 0.45),
          fontWeight: FontWeight.w500)),
    ]);
  }

  // ── Card ───────────────────────────────────────────────────────────────────
  Widget _buildCard() {
    return Container(
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(28),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.10), width: 1.5),
      ),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Heading
            Text('أهلاً بعودتك',
              style: GoogleFonts.cairo(
                fontSize: 22, fontWeight: FontWeight.w700,
                color: Colors.white),
              textAlign: TextAlign.center),
            const SizedBox(height: 4),
            Text('سجّل دخولك لإدارة أعمالك',
              style: GoogleFonts.cairo(
                fontSize: 13, color: Colors.white.withValues(alpha: 0.45)),
              textAlign: TextAlign.center),

            const SizedBox(height: 28),

            // Email
            _buildField(
              controller: _emailCtrl,
              hint: 'البريد الإلكتروني',
              icon: Icons.email_outlined,
              type: TextInputType.emailAddress,
            ),
            const SizedBox(height: 14),

            // Password
            _buildField(
              controller: _passCtrl,
              hint: 'كلمة المرور',
              icon: Icons.lock_outline_rounded,
              obscure: _obscure,
              suffix: GestureDetector(
                onTap: () => setState(() => _obscure = !_obscure),
                child: Icon(
                  _obscure
                    ? Icons.visibility_off_outlined
                    : Icons.visibility_outlined,
                  size: 20,
                  color: Colors.white.withValues(alpha: 0.45)),
              ),
            ),

            // Error
            if (_error != null) ...[
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 14, vertical: 12),
                decoration: BoxDecoration(
                  color: AppColors.error.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: AppColors.error.withValues(alpha: 0.25)),
                ),
                child: Row(children: [
                  const Icon(Icons.error_outline_rounded,
                    color: AppColors.error, size: 18),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(_error!,
                      style: GoogleFonts.cairo(
                        color: AppColors.error, fontSize: 13,
                        fontWeight: FontWeight.w500))),
                ]),
              ),
            ],

            const SizedBox(height: 24),

            // Login button
            AppPrimaryButton(
              label: 'تسجيل الدخول',
              icon: Icons.login_rounded,
              onPressed: _loading ? null : _handleLogin,
              loading: _loading,
            ),

            const SizedBox(height: 16),

            // Divider hint
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  width: 20, height: 1,
                  color: Colors.white.withValues(alpha: 0.12)),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 10),
                  child: Text('آمن ومشفّر',
                    style: GoogleFonts.cairo(
                      fontSize: 11,
                      color: Colors.white.withValues(alpha: 0.30))),
                ),
                Container(
                  width: 20, height: 1,
                  color: Colors.white.withValues(alpha: 0.12)),
              ],
            ),
          ],
        ),
      ),
    );
  }

  // ── Text Field ─────────────────────────────────────────────────────────────
  Widget _buildField({
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    TextInputType? type,
    bool obscure = false,
    Widget? suffix,
  }) {
    return TextField(
      controller: controller,
      obscureText: obscure,
      keyboardType: type,
      style: GoogleFonts.cairo(color: Colors.white, fontSize: 15),
      onSubmitted: (_) => _handleLogin(),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: GoogleFonts.cairo(
          color: Colors.white.withValues(alpha: 0.35), fontSize: 14),
        prefixIcon: Icon(icon,
          color: Colors.white.withValues(alpha: 0.45), size: 20),
        suffixIcon: suffix != null
            ? Padding(
                padding: const EdgeInsets.only(left: 12),
                child: suffix)
            : null,
        filled: true,
        fillColor: Colors.white.withValues(alpha: 0.05),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(
            color: Colors.white.withValues(alpha: 0.12))),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(
            color: Colors.white.withValues(alpha: 0.12))),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(
            color: AppColors.primary, width: 2)),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 18, vertical: 16),
      ),
    );
  }
}
