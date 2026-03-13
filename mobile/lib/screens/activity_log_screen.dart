import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';

class ActivityLogScreen extends StatefulWidget {
  const ActivityLogScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<ActivityLogScreen> createState() => _ActivityLogScreenState();
}

class _ActivityLogScreenState extends State<ActivityLogScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _items = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final list = await widget.api.getActivityLog(limit: 100);
      if (mounted) setState(() => _items = list);
    } catch (_) {} finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  IconData _iconForAction(String action) {
    if (action.startsWith('sale')) return Icons.receipt_rounded;
    if (action.startsWith('product')) return Icons.inventory_2_rounded;
    if (action.startsWith('user')) return Icons.person_rounded;
    if (action.startsWith('purchase')) return Icons.shopping_bag_rounded;
    if (action.startsWith('expense')) return Icons.money_off_rounded;
    if (action.startsWith('category')) return Icons.category_rounded;
    return Icons.history_rounded;
  }

  Color _colorForAction(String action) {
    if (action.contains('delete') || action.contains('cancel')) return AppColors.error;
    if (action.contains('create')) return AppColors.success;
    if (action.contains('update') || action.contains('password')) return AppColors.warning;
    return AppColors.primary;
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: const Text('سجل النشاط'),
          backgroundColor: AppColors.bg,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_rounded),
            onPressed: () => Navigator.pop(context),
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.refresh_rounded, color: AppColors.primary),
              onPressed: _load,
            ),
          ],
        ),
        body: _loading
            ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
            : RefreshIndicator(
                color: AppColors.primary,
                onRefresh: _load,
                child: _items.isEmpty
                    ? const Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.history_rounded, size: 56, color: AppColors.textHint),
                            SizedBox(height: 12),
                            Text('لا يوجد سجل نشاط', style: TextStyle(color: AppColors.textSecondary)),
                          ],
                        ),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                        itemCount: _items.length,
                        itemBuilder: (ctx, i) {
                          final e = _items[i];
                          final action = (e['action'] ?? '').toString();
                          final color = _colorForAction(action);
                          final ts = (e['created_at'] ?? '').toString();
                          final displayTs = ts.length >= 16 ? ts.substring(0, 16) : ts;

                          return Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: AppColors.card,
                              borderRadius: BorderRadius.circular(14),
                              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6)],
                            ),
                            child: Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: color.withValues(alpha: 0.1),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Icon(_iconForAction(action), color: color, size: 18),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        action,
                                        style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
                                      ),
                                      if ((e['description'] ?? '').toString().isNotEmpty)
                                        Text(
                                          (e['description'] ?? '').toString(),
                                          style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                    ],
                                  ),
                                ),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    Text(
                                      (e['username'] ?? '—').toString(),
                                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.primary),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      displayTs,
                                      style: const TextStyle(fontSize: 10, color: AppColors.textHint),
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
