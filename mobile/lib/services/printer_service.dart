import 'dart:typed_data';
import 'package:blue_thermal_printer/blue_thermal_printer.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

class PrinterService {
  static final PrinterService _instance = PrinterService._internal();
  factory PrinterService() => _instance;
  PrinterService._internal();

  final BlueThermalPrinter _printer = BlueThermalPrinter.instance;
  BluetoothDevice? _connectedDevice;
  bool _isConnected = false;

  BluetoothDevice? get connectedDevice => _connectedDevice;
  bool get isConnected => _isConnected;

  static const String _printerKey = 'default_printer_name';
  static const String _printerAddressKey = 'default_printer_address';

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    final savedName = prefs.getString(_printerKey);
    final savedAddress = prefs.getString(_printerAddressKey);

    if (savedName != null && savedAddress != null) {
      await _autoConnect(savedName, savedAddress);
    }
  }

  Future<void> _autoConnect(String name, String address) async {
    try {
      final devices = await _printer.getBondedDevices();
      BluetoothDevice? device;
      for (var d in devices) {
        if (d.address == address) {
          device = d;
          break;
        }
      }
      
      if (device != null) {
        final connected = await _printer.connect(device);
        _isConnected = connected;
        if (connected) {
          _connectedDevice = device;
        }
      }
    } catch (e) {
      _isConnected = false;
      _connectedDevice = null;
    }
  }

  Future<List<BluetoothDevice>> scanDevices() async {
    try {
      final devices = await _printer.getBondedDevices();
      return devices.toList();
    } catch (e) {
      return [];
    }
  }

  Future<bool> connect(BluetoothDevice device) async {
    try {
      final result = await _printer.connect(device);
      _isConnected = result;
      if (result) {
        _connectedDevice = device;
        await _saveDefaultPrinter(device);
      }
      return result;
    } catch (e) {
      _isConnected = false;
      return false;
    }
  }

  Future<void> disconnect() async {
    try {
      await _printer.disconnect();
      _isConnected = false;
      _connectedDevice = null;
    } catch (e) {
      _isConnected = false;
    }
  }

  Future<void> _saveDefaultPrinter(BluetoothDevice device) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_printerKey, device.name ?? 'Unknown');
    await prefs.setString(_printerAddressKey, device.address ?? '');
  }

  Future<void> clearDefaultPrinter() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_printerKey);
    await prefs.remove(_printerAddressKey);
  }

  Future<Uint8List> _generateQRCode(String data) async {
    final qrPainter = QrPainter(
      data: data,
      version: QrVersions.auto,
      errorCorrectionLevel: QrErrorCorrectLevel.M,
    );
    
    final picData = await qrPainter.toImageData(120);
    if (picData == null) return Uint8List(0);
    
    final byteData = picData.buffer.asUint8List();
    return byteData;
  }

  Future<void> printReceipt(Map<String, dynamic> saleData) async {
    if (!_isConnected || _connectedDevice == null) {
      throw PrinterException('Printer not connected');
    }

    try {
      final items = saleData['items'] as List<Map<String, dynamic>>;
      final storeName = saleData['store_name'] as String? ?? 'Sober Inventory';
      final invoiceId = saleData['invoice_id'] as String? ?? 'N/A';
      final date = saleData['date'] as String? ?? DateTime.now().toString().split('.').first;
      final tax = (saleData['tax'] as num?)?.toDouble() ?? 0.0;
      final total = (saleData['total'] as num?)?.toDouble() ?? 0.0;
      final discount = (saleData['discount'] as num?)?.toDouble() ?? 0.0;

      _printer.printNewLine();
      _printer.printCustom(storeName, 1, 1);
      _printer.printNewLine();
      
      _printer.printCustom('Date: $date', 0, 0);
      _printer.printCustom('Invoice: $invoiceId', 0, 0);
      
      _printer.printNewLine();
      _printer.printCustom('-' * 32, 0, 0);
      
      for (final item in items) {
        final name = item['name']?.toString() ?? '';
        final qty = item['quantity']?.toString() ?? '0';
        final price = (item['price'] as num?)?.toDouble() ?? 0.0;
        final itemTotal = (item['total'] as num?)?.toDouble() ?? 0.0;
        
        _printer.printCustom(name.length > 18 ? '${name.substring(0, 16)}..' : name, 0, 0);
        _printer.printCustom('$qty x ${price.toStringAsFixed(0)}', 0, 2);
        _printer.printCustom(itemTotal.toStringAsFixed(0), 1, 2);
      }
      
      _printer.printCustom('-' * 32, 0, 0);
      
      if (discount > 0) {
        _printer.printCustom('Discount:', 0, 0);
        _printer.printCustom('-${discount.toStringAsFixed(0)}', 0, 2);
      }
      
      if (tax > 0) {
        _printer.printCustom('Tax:', 0, 0);
        _printer.printCustom(tax.toStringAsFixed(0), 0, 2);
      }
      
      _printer.printNewLine();
      _printer.printCustom('TOTAL:', 1, 0);
      _printer.printCustom(total.toStringAsFixed(0), 1, 2);
      
      _printer.printNewLine();
      _printer.printCustom('Thank You!', 0, 1);
      _printer.printCustom('See you soon', 0, 1);
      
      _printer.printNewLine();
      
      final qrData = 'INV:$invoiceId';
      final qrBytes = await _generateQRCode(qrData);
      _printer.printImageBytes(qrBytes);
      
      _printer.printNewLine();
      _printer.printCustom('Scan for e-invoice', 0, 1);
      
      _printer.printNewLine();
      _printer.printNewLine();
      _printer.printNewLine();
      
    } catch (e) {
      throw PrinterException('Failed to print: $e');
    }
  }

  Future<bool> testPrint() async {
    if (!_isConnected) {
      throw PrinterException('Printer not connected');
    }

    try {
      _printer.printNewLine();
      _printer.printCustom('Sober Inventory', 1, 1);
      _printer.printNewLine();
      _printer.printCustom('Test Print', 0, 1);
      _printer.printNewLine();
      _printer.printCustom('Connection successful!', 0, 0);
      _printer.printNewLine();
      _printer.printNewLine();
      _printer.printNewLine();
      return true;
    } catch (e) {
      return false;
    }
  }
}

class PrinterException implements Exception {
  final String message;
  PrinterException(this.message);
  
  @override
  String toString() => message;
}
