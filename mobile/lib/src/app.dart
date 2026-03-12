import 'package:flutter/material.dart';

import '../api/api_client.dart';
import '../screens/login_screen.dart';

class SoberPosApp extends StatelessWidget {
  const SoberPosApp({super.key, required this.api});

  final ApiClient api;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Sober POS Mobile',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.indigo),
        useMaterial3: true,
        fontFamily: 'Roboto',
      ),
      home: LoginScreen(api: api),
    );
  }
}
