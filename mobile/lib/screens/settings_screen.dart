import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';

class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  bool _loading = true;
  bool _saving = false;
  String _csrfToken = '';
  final _appNameCtrl = TextEditingController();
  final _currencyCtrl = TextEditingController();
  final _timezoneCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final r = await widget.api.getMe();
      final s = await widget.api.getSettings();
      if (mounted) {
        setState(() {
          _csrfToken = (r['csrf_token'] ?? '').toString();
          _appNameCtrl.text = (s['app_name'] ?? 'نظام المخزون').toString();
          _currencyCtrl.text = (s['currency_symbol'] ?? 'د.ع').toString();
          _timezoneCtrl.text = (s['timezone'] ?? 'Asia/Baghdad').toString();
        });
      }
    } catch (_) {} finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    try {
      final r = await widget.api.saveSettings(
        appName: _appNameCtrl.text.trim(),
        currencySymbol: _currencyCtrl.text.trim(),
        timezone: _timezoneCtrl.text.trim(),
        csrfToken: _csrfToken,
      );
      if (r['success'] == true && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: const Text('تم حفظ الإعدادات'),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          margin: const EdgeInsets.all(16),
        ));
      }
    } catch (_) {} finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: const Text('إعدادات النظام'),
          backgroundColor: AppColors.bg,
          leading: IconButton(icon: const Icon(Icons.arrow_back_ios_rounded), onPressed: () => Navigator.pop(context)),
        ),
        body: _loading
            ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
            : SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [AppColors.bgDark, Color(0xFF1A3070)],
                        ),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.settings_rounded, color: Colors.white, size: 36),
                          const SizedBox(width: 16),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('إعدادات النظام', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 16)),
                              Text('تخصيص التطبيق', style: TextStyle(color: Colors.white.withValues(alpha: 0.6), fontSize: 12)),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),
                    Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: AppColors.card,
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12)],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('إعدادات عامة', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                          const SizedBox(height: 16),
                          TextField(
                            controller: _appNameCtrl,
                            decoration: InputDecoration(
                              labelText: 'اسم التطبيق',
                              prefixIcon: const Icon(Icons.storefront_rounded),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                          ),
                          const SizedBox(height: 14),
                          TextField(
                            controller: _currencyCtrl,
                            decoration: InputDecoration(
                              labelText: 'رمز العملة',
                              prefixIcon: const Icon(Icons.attach_money_rounded),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                          ),
                          const SizedBox(height: 14),
                          TextField(
                            controller: _timezoneCtrl,
                            decoration: InputDecoration(
                              labelText: 'المنطقة الزمنية',
                              prefixIcon: const Icon(Icons.access_time_rounded),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              hintText: 'مثال: Asia/Baghdad',
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      height: 54,
                      child: ElevatedButton.icon(
                        onPressed: _saving ? null : _save,
                        icon: _saving
                            ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                            : const Icon(Icons.save_rounded),
                        label: Text(_saving ? 'جاري الحفظ...' : 'حفظ الإعدادات'),
                      ),
                    ),
                  ],
                ),
              ),
      ),
    );
  }
}
