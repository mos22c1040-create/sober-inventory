import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
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

class _LoginScreenState extends State<LoginScreen> with SingleTickerProviderStateMixin {
  final TextEditingController _emailCtrl = TextEditingController();
  final TextEditingController _passCtrl = TextEditingController();
  bool _loading = false;
  bool _obscure = true;
  String? _error;
  bool _keepLoggedIn = false;
  late AnimationController _animCtrl;
  late Animation<double> _fadeAnim;
  late Animation<Offset> _slideAnim;

  @override
  void initState() {
    super.initState();
    _animCtrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 900));
    _fadeAnim = CurvedAnimation(parent: _animCtrl, curve: Curves.easeOut);
    _slideAnim = Tween<Offset>(begin: const Offset(0, 0.08), end: Offset.zero).animate(CurvedAnimation(parent: _animCtrl, curve: Curves.easeOutCubic));
    _animCtrl.forward();
  }

  @override
  void dispose() {
    _animCtrl.dispose();
    _emailCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  // === BUSINESS LOGIC (UNCHANGED) ===
  Future<void> _handleLogin() async {
    if (_emailCtrl.text.trim().isEmpty || _passCtrl.text.isEmpty) {
      setState(() => _error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور');
      return;
    }
    setState(() { _loading = true; _error = null; });
    try {
      final res = await widget.api.login(email: _emailCtrl.text.trim(), password: _passCtrl.text);
      if (res['success'] == true) {
        if (!mounted) return;
        Navigator.of(context).pushReplacement(MaterialPageRoute<void>(builder: (_) => MainShell(api: widget.api)));
      } else {
        setState(() { _error = (res['error'] ?? res['message'] ?? 'البريد أو كلمة المرور خاطئة').toString(); });
      }
    } on DioException catch (e) {
      String msg = 'تعذر الاتصال بالخادم';
      final d = e.response?.data;
      if (d is Map) { msg = (d['error'] ?? d['message'] ?? msg).toString(); }
      else if (e.type == DioExceptionType.connectionTimeout || e.type == DioExceptionType.connectionError) { msg = 'تحقق من الإنترنت أو أن السيرفر يعمل'; }
      setState(() => _error = msg);
    } catch (_) { setState(() => _error = 'تعذر الاتصال بالخادم'); }
    finally { if (mounted) setState(() => _loading = false); }
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.sizeOf(context);
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        body: Stack(children: [
          // Background decorations
          Positioned(top: -size.height * 0.15, right: -size.width * 0.35, child: _buildGlowCircle(size.width * 0.9, AppColors.primary.withValues(alpha: 0.2))),
          Positioned(bottom: size.height * 0.1, left: -size.width * 0.3, child: _buildGlowCircle(size.width * 0.7, AppColors.secondary.withValues(alpha: 0.1))),
          Positioned(top: size.height * 0.4, left: size.width * 0.55, child: _buildGlowCircle(size.width * 0.3, AppColors.primaryLight.withValues(alpha: 0.1))),

          // Main content
          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
                child: FadeTransition(opacity: _fadeAnim, child: SlideTransition(position: _slideAnim, child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                  const SizedBox(height: 16),
                  _buildLogo(),
                  const SizedBox(height: 32),
                  _buildLoginCard(),
                ]))),
              ),
            ),
          ),
        ]),
      ),
    );
  }

  Widget _buildGlowCircle(double size, Color color) => Container(width: size, height: size, decoration: BoxDecoration(shape: BoxShape.circle, color: color));

  Widget _buildLogo() {
    return Column(children: [
      Container(
        width: 90, 
        height: 90, 
        decoration: BoxDecoration(
          gradient: AppColors.primaryGradient, 
          borderRadius: BorderRadius.circular(26), 
          boxShadow: [
            BoxShadow(
              color: AppColors.primary.withValues(alpha: 0.4), 
              blurRadius: 24, 
              offset: const Offset(0, 8)
            )
          ]
        ),
        child: const Icon(Icons.point_of_sale_rounded, size: 44, color: Colors.white)
      ),
      const SizedBox(height: 20),
      Text('Sober POS', style: GoogleFonts.cairo(fontSize: 32, fontWeight: FontWeight.w800, color: Colors.white, letterSpacing: -0.5)),
      const SizedBox(height: 6),
      Text('نظام المخزون والمبيعات', style: GoogleFonts.cairo(fontSize: 14, color: Colors.white.withValues(alpha: 0.5))),
    ]);
  }

  Widget _buildLoginCard() {
    return Container(
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.08), borderRadius: BorderRadius.circular(28), border: Border.all(color: Colors.white.withValues(alpha: 0.15), width: 1.5)),
      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Text('مرحباً بعودتك', style: GoogleFonts.cairo(fontSize: 22, fontWeight: FontWeight.w700, color: Colors.white), textAlign: TextAlign.center),
        const SizedBox(height: 6),
        Text('سجل دخولك لإدارة أعمالك', style: GoogleFonts.cairo(fontSize: 13, color: Colors.white.withValues(alpha: 0.5)), textAlign: TextAlign.center),
        const SizedBox(height: 28),

        // Email Field
        _buildModernTextField(controller: _emailCtrl, hint: 'البريد الإلكتروني', icon: Icons.email_outlined, type: TextInputType.emailAddress),
        const SizedBox(height: 16),

        // Password Field
        _buildModernTextField(controller: _passCtrl, hint: 'كلمة المرور', icon: Icons.lock_outline_rounded, obscure: _obscure, suffix: IconButton(icon: Icon(_obscure ? Icons.visibility_off_outlined : Icons.visibility_outlined, size: 20, color: Colors.white.withValues(alpha: 0.5)), onPressed: () => setState(() => _obscure = !_obscure))),

        // Remember & Forgot
        const SizedBox(height: 16),
        Row(children: [
          GestureDetector(onTap: () => setState(() => _keepLoggedIn = !_keepLoggedIn),
            child: Row(children: [
              Container(width: 22, height: 22, decoration: BoxDecoration(color: _keepLoggedIn ? AppColors.primary : Colors.transparent, borderRadius: BorderRadius.circular(6), border: Border.all(color: Colors.white.withValues(alpha: 0.3), width: 1.5)),
                child: _keepLoggedIn ? const Icon(Icons.check, size: 14, color: Colors.white) : null),
              const SizedBox(width: 10),
              Text('تذكرني', style: GoogleFonts.cairo(fontSize: 13, color: Colors.white.withValues(alpha: 0.7))),
            ])),
          const Spacer(),
          TextButton(onPressed: () {}, child: Text('نسيت كلمة المرور؟', style: GoogleFonts.cairo(fontSize: 13, color: AppColors.accent))),
        ]),

        // Error Message
        if (_error != null) ...[
          const SizedBox(height: 16),
          Container(padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12), decoration: BoxDecoration(color: AppColors.error.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(12), border: Border.all(color: AppColors.error.withValues(alpha: 0.3))),
            child: Row(children: [
              const Icon(Icons.error_outline_rounded, color: AppColors.error, size: 18),
              const SizedBox(width: 10),
              Expanded(child: Text(_error!, style: GoogleFonts.cairo(color: AppColors.error, fontSize: 13, fontWeight: FontWeight.w500))),
            ])),
        ],

        const SizedBox(height: 24),

        // Login Button
        _loading
            ? Container(padding: const EdgeInsets.symmetric(vertical: 16), decoration: BoxDecoration(gradient: AppColors.primaryGradient, borderRadius: BorderRadius.circular(14)), child: const Center(child: SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))))
            : GestureDetector(
              onTap: _handleLogin,
              child: Container(padding: const EdgeInsets.symmetric(vertical: 16), decoration: BoxDecoration(gradient: AppColors.primaryGradient, borderRadius: BorderRadius.circular(14), boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.4), blurRadius: 12, offset: const Offset(0, 4))]),
                child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                  const Icon(Icons.login_rounded, color: Colors.white, size: 20),
                  const SizedBox(width: 10),
                  Text('تسجيل الدخول', style: GoogleFonts.cairo(fontSize: 16, fontWeight: FontWeight.w700, color: Colors.white)),
                ])),
            ),
      ]),
    );
  }

  Widget _buildModernTextField({required TextEditingController controller, required String hint, required IconData icon, TextInputType? type, bool obscure = false, Widget? suffix}) {
    return TextField(
      controller: controller,
      obscureText: obscure,
      keyboardType: type,
      style: GoogleFonts.cairo(color: Colors.white, fontSize: 15),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: GoogleFonts.cairo(color: Colors.white.withValues(alpha: 0.4), fontSize: 14),
        prefixIcon: Icon(icon, color: Colors.white.withValues(alpha: 0.5), size: 20),
        suffixIcon: suffix,
        filled: true,
        fillColor: Colors.white.withValues(alpha: 0.05),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.15))),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.15))),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: AppColors.primary, width: 2)),
        contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
      ),
    );
  }
}