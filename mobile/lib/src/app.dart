import 'package:flutter/material.dart';

import '../api/api_client.dart';
import '../screens/login_screen.dart';
import '../theme/app_theme.dart';

class SoberPosApp extends StatefulWidget {
  const SoberPosApp({super.key, required this.api});

  final ApiClient api;

  @override
  State<SoberPosApp> createState() => _SoberPosAppState();
}

class _SoberPosAppState extends State<SoberPosApp> {
  final GlobalKey<NavigatorState> _navKey = GlobalKey<NavigatorState>();

  @override
  void initState() {
    super.initState();
    widget.api.setOnUnauthorized(_goToLogin);
  }

  void _goToLogin() {
    final context = _navKey.currentContext;
    if (context == null || !context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: const Text('انتهت الجلسة، يرجى تسجيل الدخول مرة أخرى'),
        backgroundColor: Theme.of(context).colorScheme.error,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute<void>(
        builder: (_) => LoginScreen(api: widget.api),
      ),
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      navigatorKey: _navKey,
      title: 'Sober POS',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      builder: (context, child) {
        return Directionality(
          textDirection: TextDirection.rtl,
          child: child ?? const SizedBox.shrink(),
        );
      },
      home: LoginScreen(api: widget.api),
    );
  }
}
