import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../screens/login_screen.dart';

class SoberPosApp extends StatelessWidget {
  const SoberPosApp({super.key});

  @override
  Widget build(BuildContext context) {
    // TODO: عدّل هذا إلى رابط السيرفر الخاص بك (HTTPs في الإنتاج)
    const baseUrl = 'https://your-backend-domain.com';

    return MaterialApp(
      title: 'Sober POS Mobile',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.indigo),
        useMaterial3: true,
        fontFamily: 'Roboto',
      ),
      home: LoginScreen(api: ApiClient(baseUrl)),
    );
  }
}

