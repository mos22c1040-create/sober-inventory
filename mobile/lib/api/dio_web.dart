import 'package:dio/browser.dart';
import 'package:dio/dio.dart';

void attachWebCredentials(Dio dio) {
  dio.httpClientAdapter = BrowserHttpClientAdapter()..withCredentials = true;
}
