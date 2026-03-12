import 'package:flutter/material.dart';

import 'api/api_client.dart';
import 'src/app.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  const baseUrl = 'https://sober-inventory-production-0b31.up.railway.app';
  final ApiClient api = await ApiClient.create(baseUrl);
  runApp(SoberPosApp(api: api));
}
