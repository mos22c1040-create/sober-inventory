import 'package:flutter/material.dart';
import '../api/api_client.dart';
import '../theme/app_theme.dart';
import '../utils/api_parse.dart';

class UsersScreen extends StatefulWidget {
  const UsersScreen({super.key, required this.api});

  final ApiClient api;

  @override
  State<UsersScreen> createState() => _UsersScreenState();
}

class _UsersScreenState extends State<UsersScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _users = [];
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
      final list = await widget.api.getUsers();
      if (mounted) setState(() => _users = list);
    } catch (_) {} finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _delete(int id, String name) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          title: const Text('حذف المستخدم'),
          content: Text('هل تريد حذف "$name"؟'),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('إلغاء')),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
              child: const Text('حذف'),
            ),
          ],
        ),
      ),
    );
    if (ok != true) return;
    try {
      final r = await widget.api.deleteUser(id: id, csrfToken: _csrfToken);
      if (r['success'] == true) {
        _showSnack('تم حذف المستخدم');
        _load();
      } else {
        _showSnack((r['error'] ?? 'فشل الحذف').toString(), isError: true);
      }
    } catch (_) {}
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

  void _showAddUser() {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _UserForm(
        api: widget.api,
        csrfToken: _csrfToken,
        onSaved: () { Navigator.pop(context); _load(); },
      ),
    );
  }

  String _roleLabel(String role) {
    switch (role) {
      case 'admin': return 'مدير';
      case 'cashier': return 'كاشير';
      default: return role;
    }
  }

  Color _roleColor(String role) => role == 'admin' ? AppColors.primary : AppColors.success;

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: AppColors.bg,
        appBar: AppBar(
          title: const Text('المستخدمين'),
          backgroundColor: AppColors.bg,
          leading: IconButton(icon: const Icon(Icons.arrow_back_ios_rounded), onPressed: () => Navigator.pop(context)),
          actions: [
            IconButton(
              icon: const Icon(Icons.person_add_rounded, color: AppColors.primary, size: 26),
              onPressed: _showAddUser,
            ),
          ],
        ),
        body: _loading
            ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
            : RefreshIndicator(
                color: AppColors.primary,
                onRefresh: _load,
                child: ListView.builder(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                  itemCount: _users.length,
                  itemBuilder: (ctx, i) {
                    final u = _users[i];
                    final name = (u['username'] ?? '').toString();
                    final role = (u['role'] ?? '').toString();
                    final status = (u['status'] ?? '').toString();
                    final isActive = status == 'active';

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
                            width: 46,
                            height: 46,
                            decoration: BoxDecoration(
                              gradient: LinearGradient(colors: [AppColors.primary, AppColors.primaryDark]),
                              shape: BoxShape.circle,
                            ),
                            child: Center(
                              child: Text(
                                name.isNotEmpty ? name[0].toUpperCase() : '?',
                                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 18),
                              ),
                            ),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                                Text((u['email'] ?? '').toString(), style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
                              ],
                            ),
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: _roleColor(role).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  _roleLabel(role),
                                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: _roleColor(role)),
                                ),
                              ),
                              const SizedBox(height: 4),
                              Row(
                                children: [
                                  Container(
                                    width: 6,
                                    height: 6,
                                    decoration: BoxDecoration(
                                      color: isActive ? AppColors.success : AppColors.error,
                                      shape: BoxShape.circle,
                                    ),
                                  ),
                                  const SizedBox(width: 4),
                                  Text(
                                    isActive ? 'نشط' : 'معطل',
                                    style: TextStyle(fontSize: 10, color: isActive ? AppColors.success : AppColors.error),
                                  ),
                                  const SizedBox(width: 8),
                                  GestureDetector(
                                    onTap: () => _delete(toInt(u['id']), name),
                                    child: Container(
                                      padding: const EdgeInsets.all(4),
                                      decoration: BoxDecoration(color: AppColors.errorBg, borderRadius: BorderRadius.circular(6)),
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
                ),
              ),
      ),
    );
  }
}

class _UserForm extends StatefulWidget {
  const _UserForm({required this.api, required this.csrfToken, required this.onSaved});
  final ApiClient api;
  final String csrfToken;
  final VoidCallback onSaved;
  @override
  State<_UserForm> createState() => _UserFormState();
}

class _UserFormState extends State<_UserForm> {
  final _usernameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  String _role = 'cashier';
  bool _saving = false;

  @override
  void dispose() {
    _usernameCtrl.dispose();
    _emailCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (_usernameCtrl.text.trim().isEmpty || _emailCtrl.text.trim().isEmpty || _passCtrl.text.length < 8) return;
    setState(() => _saving = true);
    try {
      final r = await widget.api.createUser(
        username: _usernameCtrl.text.trim(),
        email: _emailCtrl.text.trim(),
        password: _passCtrl.text,
        role: _role,
        csrfToken: widget.csrfToken,
      );
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
            const Text('إضافة مستخدم', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
            const SizedBox(height: 20),
            TextField(controller: _usernameCtrl,
              decoration: InputDecoration(labelText: 'اسم المستخدم *', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)))),
            const SizedBox(height: 12),
            TextField(controller: _emailCtrl, keyboardType: TextInputType.emailAddress,
              decoration: InputDecoration(labelText: 'البريد الإلكتروني *', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)))),
            const SizedBox(height: 12),
            TextField(controller: _passCtrl, obscureText: true,
              decoration: InputDecoration(labelText: 'كلمة المرور (8+ أحرف) *', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)))),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              value: _role,
              decoration: InputDecoration(labelText: 'الصلاحية', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
              items: const [
                DropdownMenuItem(value: 'cashier', child: Text('كاشير')),
                DropdownMenuItem(value: 'admin', child: Text('مدير')),
              ],
              onChanged: (v) => setState(() => _role = v!),
            ),
            const SizedBox(height: 20),
            SizedBox(
              height: 50,
              child: ElevatedButton(
                onPressed: _saving ? null : _save,
                child: _saving ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Text('إنشاء الحساب'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
