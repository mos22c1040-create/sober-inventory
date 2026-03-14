import 'dart:io';

import 'package:google_mlkit_commons/google_mlkit_commons.dart';
import 'package:google_mlkit_selfie_segmentation/google_mlkit_selfie_segmentation.dart';
import 'package:image/image.dart' as img;
import 'package:path_provider/path_provider.dart';

/// Service that removes image background using ML Kit Selfie Segmentation
/// and replaces it with solid white (RGB: 255, 255, 255).
/// Supported on Android and iOS only; throws on web/unsupported platforms.
class BackgroundRemovalService {
  static const double _confidenceThreshold = 0.5;
  static const int _whiteR = 255, _whiteG = 255, _whiteB = 255;

  /// Processes [inputPath], applies selfie segmentation, replaces background
  /// with white, and saves the result to a temporary file.
  /// Returns the path to the processed image file, or throws on failure.
  static Future<String> removeBackgroundAndSaveToTemp(String inputPath) async {
    final inputFile = File(inputPath);
    if (!await inputFile.exists()) {
      throw BackgroundRemovalException('الملف غير موجود');
    }

    final bytes = await inputFile.readAsBytes();
    final decoded = img.decodeImage(bytes);
    if (decoded == null) {
      throw BackgroundRemovalException('تعذر قراءة الصورة');
    }

    final segmenter = SelfieSegmenter(
      mode: SegmenterMode.single,
      enableRawSizeMask: false,
    );

    try {
      final inputImage = InputImage.fromFilePath(inputPath);
      final mask = await segmenter.processImage(inputImage);
      if (mask == null) {
        throw BackgroundRemovalException('لم يتم إنشاء قناع التجزئة');
      }

      final outImage = _applyMaskWhiteBackground(decoded, mask);
      final dir = await getTemporaryDirectory();
      final outFile = File('${dir.path}/product_white_bg_${DateTime.now().millisecondsSinceEpoch}.jpg');
      await outFile.writeAsBytes(img.encodeJpg(outImage, quality: 92));
      return outFile.path;
    } finally {
      segmenter.close();
    }
  }

  /// Applies segmentation mask to [image]: foreground pixels kept, background set to white.
  /// With enableRawSizeMask: false, mask dimensions match input image.
  static img.Image _applyMaskWhiteBackground(img.Image image, SegmentationMask mask) {
    final w = image.width;
    final h = image.height;
    final confidences = mask.confidences;
    final maskW = mask.width;
    final maskH = mask.height;

    final out = img.Image(width: w, height: h);
    for (int y = 0; y < h; y++) {
      for (int x = 0; x < w; x++) {
        final mx = (maskW != w && maskW > 0) ? (x * maskW / w).floor().clamp(0, maskW - 1) : x;
        final my = (maskH != h && maskH > 0) ? (y * maskH / h).floor().clamp(0, maskH - 1) : y;
        final idx = my * maskW + mx;
        final confidence = idx < confidences.length ? confidences[idx] : 0.0;

        if (confidence >= _confidenceThreshold) {
          final p = image.getPixel(x, y);
          out.setPixelRgba(x, y, p.r.toInt(), p.g.toInt(), p.b.toInt(), p.a.toInt());
        } else {
          out.setPixelRgba(x, y, _whiteR, _whiteG, _whiteB, 255);
        }
      }
    }
    return out;
  }
}

class BackgroundRemovalException implements Exception {
  final String message;
  BackgroundRemovalException(this.message);
  @override
  String toString() => message;
}
