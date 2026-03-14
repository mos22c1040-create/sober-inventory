import 'dart:io';

import 'package:flutter/material.dart';

Widget productImageFile(String path) {
  final file = File(path);
  if (!file.existsSync()) return const SizedBox.shrink();
  return Image.file(
    file,
    fit: BoxFit.cover,
    errorBuilder: (_, __, ___) => const SizedBox.shrink(),
  );
}
