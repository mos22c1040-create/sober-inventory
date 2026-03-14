import 'package:blue_thermal_printer/blue_thermal_printer.dart';
import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import '../services/printer_service.dart';

class PrinterSettingsScreen extends StatefulWidget {
  const PrinterSettingsScreen({super.key});

  @override
  State<PrinterSettingsScreen> createState() => _PrinterSettingsScreenState();
}

class _PrinterSettingsScreenState extends State<PrinterSettingsScreen> {
  final PrinterService _printerService = PrinterService();
  List<BluetoothDevice> _devices = [];
  bool _scanning = false;
  bool _connecting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _initPrinter();
  }

  Future<void> _initPrinter() async {
    await _printerService.init();
    setState(() {});
  }

  Future<void> _scanDevices() async {
    setState(() {
      _scanning = true;
      _error = null;
    });

    try {
      final devices = await _printerService.scanDevices();
      setState(() {
        _devices = devices;
        _scanning = false;
      });
    } catch (e) {
      setState(() {
        _error = 'Failed to scan devices';
        _scanning = false;
      });
    }
  }

  Future<void> _connectToDevice(BluetoothDevice device) async {
    setState(() {
      _connecting = true;
      _error = null;
    });

    try {
      final success = await _printerService.connect(device);
      if (success) {
        _showSnackBar('Connected to ${device.name}');
      } else {
        _showSnackBar('Failed to connect', isError: true);
      }
    } catch (e) {
      _showSnackBar('Connection error: $e', isError: true);
    } finally {
      setState(() => _connecting = false);
    }
  }

  Future<void> _disconnect() async {
    await _printerService.disconnect();
    setState(() {});
    _showSnackBar('Disconnected');
  }

  Future<void> _testPrint() async {
    try {
      final success = await _printerService.testPrint();
      if (success) {
        _showSnackBar('Test print sent!');
      } else {
        _showSnackBar('Test print failed', isError: true);
      }
    } catch (e) {
      _showSnackBar('Error: $e', isError: true);
    }
  }

  Future<void> _clearDefaultPrinter() async {
    await _printerService.clearDefaultPrinter();
    await _printerService.disconnect();
    setState(() {});
    _showSnackBar('Default printer cleared');
  }

  void _showSnackBar(String msg, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: isError ? AppColors.error : AppColors.success,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: const Text('إعدادات الطابعة'),
          backgroundColor: AppColors.bg,
          elevation: 0,
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildConnectionStatus(),
              const SizedBox(height: 24),
              _buildDeviceList(),
              const SizedBox(height: 24),
              _buildActions(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildConnectionStatus() {
    final isConnected = _printerService.isConnected;
    final device = _printerService.connectedDevice;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: isConnected ? AppColors.successBg : AppColors.warningBg,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isConnected ? AppColors.success.withValues(alpha: 0.3) : AppColors.warning.withValues(alpha: 0.3),
        ),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: isConnected ? AppColors.success : AppColors.warning,
              shape: BoxShape.circle,
            ),
            child: Icon(
              isConnected ? Icons.check_rounded : Icons.bluetooth_disabled_rounded,
              color: Colors.white,
              size: 24,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isConnected ? 'متصل' : 'غير متصل',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: isConnected ? AppColors.success : AppColors.warning,
                  ),
                ),
                if (device != null)
                  Text(
                    device.name ?? 'Unknown Device',
                    style: const TextStyle(
                      fontSize: 13,
                      color: AppColors.textSecondary,
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDeviceList() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              'الأجهزة المتاحة',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: AppColors.textPrimary,
              ),
            ),
            Row(
              children: [
                if (_scanning)
                  const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                else
                  IconButton(
                    onPressed: _scanDevices,
                    icon: const Icon(Icons.refresh_rounded, color: AppColors.primary),
                  ),
              ],
            ),
          ],
        ),
        const SizedBox(height: 12),
        if (_error != null)
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppColors.errorBg,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Row(
              children: [
                const Icon(Icons.error_outline, color: AppColors.error, size: 20),
                const SizedBox(width: 8),
                Text(_error!, style: const TextStyle(color: AppColors.error)),
              ],
            ),
          ),
        if (_devices.isEmpty && !_scanning)
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: AppColors.card,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.border),
            ),
            child: Column(
              children: [
                Icon(Icons.bluetooth_searching_rounded, size: 48, color: AppColors.textTertiary),
                const SizedBox(height: 12),
                const Text(
                  'لم يتم العثور على أجهزة',
                  style: TextStyle(color: AppColors.textSecondary),
                ),
                const SizedBox(height: 8),
                TextButton(
                  onPressed: _scanDevices,
                  child: const Text('بحث снова'),
                ),
              ],
            ),
          )
        else
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: _devices.length,
            itemBuilder: (context, index) {
              final device = _devices[index];
              final isSelected = _printerService.connectedDevice?.address == device.address;
              final isConnectedDevice = isSelected && _printerService.isConnected;

              return Container(
                margin: const EdgeInsets.only(bottom: 8),
                decoration: BoxDecoration(
                  color: AppColors.card,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: isConnectedDevice ? AppColors.success : AppColors.border,
                    width: isConnectedDevice ? 2 : 1,
                  ),
                ),
                child: ListTile(
                  leading: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: isConnectedDevice ? AppColors.successBg : AppColors.primarySurface,
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      Icons.print_rounded,
                      color: isConnectedDevice ? AppColors.success : AppColors.primary,
                    ),
                  ),
                  title: Text(
                    device.name ?? 'Unknown',
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                  subtitle: Text(
                    device.address ?? '',
                    style: const TextStyle(fontSize: 12, color: AppColors.textTertiary),
                  ),
                  trailing: _connecting
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : isConnectedDevice
                          ? const Icon(Icons.check_circle, color: AppColors.success)
                          : TextButton(
                              onPressed: () => _connectToDevice(device),
                              child: const Text('اتصال'),
                            ),
                ),
              );
            },
          ),
      ],
    );
  }

  Widget _buildActions() {
    final isConnected = _printerService.isConnected;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'إجراءات',
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.w700,
            color: AppColors.textPrimary,
          ),
        ),
        const SizedBox(height: 12),
        if (isConnected) ...[
          _buildActionButton(
            icon: Icons.print_rounded,
            label: 'اختبار الطباعة',
            onTap: _testPrint,
            color: AppColors.primary,
          ),
          const SizedBox(height: 8),
          _buildActionButton(
            icon: Icons.link_off_rounded,
            label: 'قطع الاتصال',
            onTap: _disconnect,
            color: AppColors.error,
          ),
        ],
        const SizedBox(height: 8),
        _buildActionButton(
          icon: Icons.delete_outline_rounded,
          label: 'مسح الطابعة الافتراضية',
          onTap: _clearDefaultPrinter,
          color: AppColors.warning,
        ),
      ],
    );
  }

  Widget _buildActionButton({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
    required Color color,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: ListTile(
        leading: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            shape: BoxShape.circle,
          ),
          child: Icon(icon, color: color, size: 20),
        ),
        title: Text(label, style: TextStyle(color: color)),
        trailing: Icon(Icons.chevron_left, color: color),
        onTap: onTap,
      ),
    );
  }
}
