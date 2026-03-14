import 'package:flutter/material.dart';

import 'product_image_file_stub.dart' if (dart.library.io) 'product_image_file_io.dart' as impl;

Widget productImageFile(String path) => impl.productImageFile(path);
