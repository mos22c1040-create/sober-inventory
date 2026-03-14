import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../utils/api_parse.dart';

class TypesScreen extends StatefulWidget {
  const TypesScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<TypesScreen> createState() => _TypesScreenState();
}

class _TypesScreenState extends State<TypesScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _items = [];
  String _csrfToken = '';

  @override
  void initState() {
    super.initState();
    _load();
    _loadCsrf();
  }

  Future<void> _loadCsrf() async {
    try {
      final r = await widget.api.getMe();
      if (mounted) setState(() => _csrfToken = (r['csrf_token'] ?? '').toString());
    } catch (_) {}
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final list = await widget.api.getTypes();
      if (mounted) setState(() => _items = list);
    } catch (_) {} finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _delete(int id) async {
    try {
      await widget.api.deleteType(id: id, csrfToken: _csrfToken);
      _load();
    } catch (_) {}
  }

  void _showForm([Map<String, dynamic>? type]) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _TypeForm(
        api: widget.api,
        csrfToken: _csrfToken,
        type: type,
        onSaved: () { Navigator.pop(context); _load(); },
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
          title: const Text('الأنواع'),
          backgroundColor: AppColors.bg,
          leading: IconButton(icon: const Icon(Icons.arrow_back_ios_rounded), onPressed: () => Navigator.pop(context)),
          actions: [
            IconButton(
              icon: const Icon(Icons.add_circle_rounded, color: AppColors.primary, size: 28),
              onPressed: () => _showForm(),
            ),
          ],
        ),
        body: _loading
            ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
            : RefreshIndicator(
                color: AppColors.primary,
                onRefresh: _load,
                child: _items.isEmpty
                    ? const Center(child: Text('لا توجد أنواع', style: TextStyle(color: AppColors.textSecondary)))
                    : ListView.builder(
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                        itemCount: _items.length,
                        itemBuilder: (ctx, i) {
                          final t = _items[i];
                          return Container(
                            margin: const EdgeInsets.only(bottom: 10),
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: AppColors.card,
                              borderRadius: BorderRadius.circular(16),
                              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8)],
                            ),
                            child: Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(10),
                                  decoration: BoxDecoration(
                                    color: AppColors.primary.withValues(alpha: 0.1),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: const Icon(Icons.layers_rounded, color: AppColors.primary, size: 20),
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text((t['name'] ?? '').toString(), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                                      if ((t['description'] ?? '').toString().isNotEmpty)
                                        Text((t['description'] ?? '').toString(), style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
                                    ],
                                  ),
                                ),
                                Row(
                                  children: [
                                    IconButton(
                                      icon: const Icon(Icons.edit_rounded, color: AppColors.primary, size: 20),
                                      onPressed: () => _showForm(t),
                                    ),
                                    IconButton(
                                      icon: const Icon(Icons.delete_rounded, color: AppColors.error, size: 20),
                                      onPressed: () => _delete(toInt(t['id'])),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          );
                        },
                      ),
              ),
      ),
    );
  }
}

class _TypeForm extends StatefulWidget {
  const _TypeForm({
    required this.api,
    required this.csrfToken,
    required this.onSaved,
    this.type,
  });

  final ApiClient api;
  final String csrfToken;
  final Map<String, dynamic>? type;
  final VoidCallback onSaved;

  @override
  State<_TypeForm> createState() => _TypeFormState();
}

class _TypeFormState extends State<_TypeForm> {
  final _nameCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _nameCtrl.text = (widget.type?['name'] ?? '').toString();
    _descCtrl.text = (widget.type?['description'] ?? '').toString();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _descCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_nameCtrl.text.trim().isEmpty) return;
    setState(() => _saving = true);
    try {
      final Map<String, dynamic> r;
      if (widget.type != null) {
        r = await widget.api.updateType(
          id: toInt(widget.type!['id']),
          name: _nameCtrl.text.trim(),
          description: _descCtrl.text.trim(),
          csrfToken: widget.csrfToken,
        );
      } else {
        r = await widget.api.createType(
          name: _nameCtrl.text.trim(),
          description: _descCtrl.text.trim(),
          csrfToken: widget.csrfToken,
        );
      }
      if (r['success'] == true) widget.onSaved();
    } catch (_) {} finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Container(
        margin: const EdgeInsets.all(12),
        padding: EdgeInsets.only(left: 24, right: 24, top: 24, bottom: MediaQuery.viewInsetsOf(context).bottom + 24),
        decoration: BoxDecoration(color: AppColors.card, borderRadius: BorderRadius.circular(28)),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(widget.type == null ? 'إضافة نوع' : 'تعديل النوع',
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
            const SizedBox(height: 20),
            TextField(
              controller: _nameCtrl,
              decoration: InputDecoration(labelText: 'اسم النوع *', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _descCtrl,
              decoration: InputDecoration(labelText: 'الوصف (اختياري)', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
            ),
            const SizedBox(height: 20),
            SizedBox(
              height: 50,
              child: ElevatedButton(
                onPressed: _saving ? null : _save,
                child: _saving ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('حفظ'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
