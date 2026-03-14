import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../utils/api_parse.dart';

class ExpensesScreen extends StatefulWidget {
  const ExpensesScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<ExpensesScreen> createState() => _ExpensesScreenState();
}

class _ExpensesScreenState extends State<ExpensesScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _items = [];
  double _monthlyTotal = 0;
  String? _error;
  String _csrfToken = '';

  static const _categories = [
    'إيجار', 'رواتب', 'فواتير', 'صيانة', 'نقل', 'تسويق', 'أخرى'
  ];

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
    setState(() { _loading = true; _error = null; });
    try {
      final r = await widget.api.getExpenses();
      if (mounted) {
        setState(() {
          _items = (r['data'] as List? ?? []).map((e) => Map<String, dynamic>.from(e as Map)).toList();
          _monthlyTotal = toDouble(r['monthly_total']);
        });
      }
    } catch (_) {
      if (mounted) setState(() => _error = 'فشل تحميل المصروفات');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _delete(int id) async {
    try {
      final r = await widget.api.deleteExpense(id: id, csrfToken: _csrfToken);
      if (r['success'] == true) {
        _showSnack('تم الحذف');
        _load();
      } else {
        _showSnack((r['error'] ?? 'فشل الحذف').toString(), isError: true);
      }
    } catch (_) {
      _showSnack('تعذر الاتصال', isError: true);
    }
  }

  void _showSnack(String msg, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: isError ? AppColors.error : AppColors.success,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin: const EdgeInsets.all(16),
    ));
  }

  void _showForm([Map<String, dynamic>? expense]) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ExpenseForm(
        api: widget.api,
        csrfToken: _csrfToken,
        categories: _categories,
        expense: expense,
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
          title: const Text('المصروفات'),
          backgroundColor: AppColors.bg,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_rounded),
            onPressed: () => Navigator.pop(context),
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.add_circle_rounded, color: AppColors.primary, size: 28),
              onPressed: () => _showForm(),
            ),
          ],
        ),
        body: RefreshIndicator(
          color: AppColors.primary,
          onRefresh: _load,
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
                  child: Container(
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [AppColors.error, Color(0xFFB91C1C)],
                      ),
                      borderRadius: BorderRadius.circular(18),
                      boxShadow: [
                        BoxShadow(
                          color: AppColors.error.withValues(alpha: 0.3),
                          blurRadius: 16,
                          offset: const Offset(0, 6),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.money_off_rounded, color: Colors.white, size: 36),
                        const SizedBox(width: 16),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('مصروفات الشهر', style: TextStyle(color: Colors.white70, fontSize: 12)),
                            Text(
                              '${_monthlyTotal.toStringAsFixed(0)} د.ع',
                              style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              if (_loading)
                const SliverFillRemaining(
                  child: Center(child: CircularProgressIndicator(color: AppColors.primary)),
                )
              else
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (ctx, i) {
                        final e = _items[i];
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
                                  color: AppColors.errorBg,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: const Icon(Icons.receipt_outlined, color: AppColors.error, size: 20),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      (e['category'] ?? '—').toString(),
                                      style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      '${e['description'] ?? ''}  |  ${e['expense_date'] ?? ''}',
                                      style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
                                    ),
                                  ],
                                ),
                              ),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(
                                    '${toDouble(e['amount']).toStringAsFixed(0)} د.ع',
                                    style: const TextStyle(fontWeight: FontWeight.w800, color: AppColors.error, fontSize: 14),
                                  ),
                                  const SizedBox(height: 6),
                                  Row(
                                    children: [
                                      GestureDetector(
                                        onTap: () => _showForm(e),
                                        child: Container(
                                          padding: const EdgeInsets.all(5),
                                          decoration: BoxDecoration(color: AppColors.primary.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(8)),
                                          child: const Icon(Icons.edit_rounded, color: AppColors.primary, size: 14),
                                        ),
                                      ),
                                      const SizedBox(width: 6),
                                      GestureDetector(
                                        onTap: () => _delete(toInt(e['id'])),
                                        child: Container(
                                          padding: const EdgeInsets.all(5),
                                          decoration: BoxDecoration(color: AppColors.errorBg, borderRadius: BorderRadius.circular(8)),
                                          child: const Icon(Icons.delete_rounded, color: AppColors.error, size: 14),
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ],
                          ),
                        );
                      },
                      childCount: _items.length,
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ExpenseForm extends StatefulWidget {
  const _ExpenseForm({
    required this.api,
    required this.csrfToken,
    required this.categories,
    required this.onSaved,
    this.expense,
  });

  final ApiClient api;
  final String csrfToken;
  final List<String> categories;
  final Map<String, dynamic>? expense;
  final VoidCallback onSaved;

  @override
  State<_ExpenseForm> createState() => _ExpenseFormState();
}

class _ExpenseFormState extends State<_ExpenseForm> {
  final _amountCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  late String _selectedCategory;
  String _date = '';
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final e = widget.expense;
    _selectedCategory = e != null ? (e['category'] ?? widget.categories.first).toString() : widget.categories.first;
    _amountCtrl.text = e != null ? (e['amount'] ?? '').toString() : '';
    _descCtrl.text = e != null ? (e['description'] ?? '').toString() : '';
    _date = e != null ? (e['expense_date'] ?? _today()).toString() : _today();
  }

  String _today() => DateTime.now().toIso8601String().substring(0, 10);

  @override
  void dispose() {
    _amountCtrl.dispose();
    _descCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final amount = double.tryParse(_amountCtrl.text.trim()) ?? 0;
    if (amount <= 0) return;
    setState(() => _saving = true);
    try {
      final isEdit = widget.expense != null;
      final Map<String, dynamic> r;
      if (isEdit) {
        r = await widget.api.updateExpense(
          id: toInt(widget.expense!['id']),
          amount: amount,
          category: _selectedCategory,
          description: _descCtrl.text.trim(),
          expenseDate: _date,
          csrfToken: widget.csrfToken,
        );
      } else {
        r = await widget.api.createExpense(
          amount: amount,
          category: _selectedCategory,
          description: _descCtrl.text.trim(),
          expenseDate: _date,
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
        padding: EdgeInsets.only(
          left: 24, right: 24, top: 24,
          bottom: MediaQuery.viewInsetsOf(context).bottom + 24,
        ),
        decoration: BoxDecoration(
          color: AppColors.card,
          borderRadius: BorderRadius.circular(28),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              widget.expense == null ? 'إضافة مصروف' : 'تعديل مصروف',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 20),
            DropdownButtonFormField<String>(
              value: _selectedCategory,
              decoration: InputDecoration(
                labelText: 'التصنيف',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
              items: widget.categories
                  .map((c) => DropdownMenuItem(value: c, child: Text(c)))
                  .toList(),
              onChanged: (v) => setState(() => _selectedCategory = v!),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _amountCtrl,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                labelText: 'المبلغ (د.ع)',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _descCtrl,
              decoration: InputDecoration(
                labelText: 'الوصف',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
            const SizedBox(height: 12),
            GestureDetector(
              onTap: () async {
                final d = await showDatePicker(
                  context: context,
                  initialDate: DateTime.parse(_date),
                  firstDate: DateTime(2020),
                  lastDate: DateTime.now(),
                );
                if (d != null) setState(() => _date = d.toIso8601String().substring(0, 10));
              },
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                decoration: BoxDecoration(
                  border: Border.all(color: AppColors.outline),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.calendar_today_rounded, color: AppColors.primary, size: 18),
                    const SizedBox(width: 10),
                    Text(_date, style: const TextStyle(fontWeight: FontWeight.w500)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),
            SizedBox(
              height: 50,
              child: ElevatedButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Text('حفظ'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
