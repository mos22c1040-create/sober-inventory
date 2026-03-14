import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../utils/api_parse.dart';

class CategoriesScreen extends StatefulWidget {
  const CategoriesScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<CategoriesScreen> createState() => _CategoriesScreenState();
}

class _CategoriesScreenState extends State<CategoriesScreen> {
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
      final list = await widget.api.getCategories();
      if (mounted) setState(() => _items = list);
    } catch (_) {} finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _delete(int id) async {
    try {
      await widget.api.deleteCategory(id: id, csrfToken: _csrfToken);
      _load();
    } catch (_) {}
  }

  void _showForm([Map<String, dynamic>? cat]) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _CategoryForm(
        api: widget.api,
        csrfToken: _csrfToken,
        category: cat,
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
          title: const Text('التصنيفات'),
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
                    ? const Center(child: Text('لا توجد تصنيفات', style: TextStyle(color: AppColors.textSecondary)))
                    : ListView.builder(
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                        itemCount: _items.length,
                        itemBuilder: (ctx, i) {
                          final c = _items[i];
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
                                    color: AppColors.warningBg,
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: const Icon(Icons.category_rounded, color: AppColors.accent, size: 20),
                                ),
                                const SizedBox(width: 14),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text((c['name'] ?? '').toString(), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                                      if ((c['description'] ?? '').toString().isNotEmpty)
                                        Text((c['description'] ?? '').toString(), style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
                                    ],
                                  ),
                                ),
                                Row(
                                  children: [
                                    IconButton(
                                      icon: const Icon(Icons.edit_rounded, color: AppColors.primary, size: 20),
                                      onPressed: () => _showForm(c),
                                    ),
                                    IconButton(
                                      icon: const Icon(Icons.delete_rounded, color: AppColors.error, size: 20),
                                      onPressed: () => _delete(toInt(c['id'])),
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

class _CategoryForm extends StatefulWidget {
  const _CategoryForm({
    required this.api,
    required this.csrfToken,
    required this.onSaved,
    this.category,
  });

  final ApiClient api;
  final String csrfToken;
  final Map<String, dynamic>? category;
  final VoidCallback onSaved;

  @override
  State<_CategoryForm> createState() => _CategoryFormState();
}

class _CategoryFormState extends State<_CategoryForm> {
  final _nameCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _nameCtrl.text = (widget.category?['name'] ?? '').toString();
    _descCtrl.text = (widget.category?['description'] ?? '').toString();
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
      if (widget.category != null) {
        r = await widget.api.updateCategory(
          id: toInt(widget.category!['id']),
          name: _nameCtrl.text.trim(),
          description: _descCtrl.text.trim(),
          csrfToken: widget.csrfToken,
        );
      } else {
        r = await widget.api.createCategory(
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
            Text(widget.category == null ? 'إضافة تصنيف' : 'تعديل التصنيف',
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
            const SizedBox(height: 20),
            TextField(
              controller: _nameCtrl,
              decoration: InputDecoration(labelText: 'اسم التصنيف *', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
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
