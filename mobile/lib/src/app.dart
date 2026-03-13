import 'package:flutter/material.dart';

import '../api/api_client.dart';
import '../screens/login_screen.dart';
import '../theme/app_theme.dart';

class SoberPosApp extends StatelessWidget {
  const SoberPosApp({super.key, required this.api});

  final ApiClient api;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Sober POS',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      builder: (context, child) {
        return Directionality(
          textDirection: TextDirection.rtl,
          child: child ?? const SizedBox.shrink(),
        );
      },
      home: LoginScreen(api: api),
    );
  }
}
