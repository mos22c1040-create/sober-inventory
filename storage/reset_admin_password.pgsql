-- إعادة تعيين كلمة مرور الأدمن في Supabase
-- نفّذ هذا في Supabase → SQL Editor إذا كان تسجيل الدخول يرفض البيانات الصحيحة.
-- بعد التنفيذ: البريد admin@example.com والرمز password

INSERT INTO users (username, email, password, role, status)
VALUES (
  'admin',
  'admin@example.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'admin',
  'active'
)
ON CONFLICT (email) DO UPDATE SET
  password = EXCLUDED.password,
  username = EXCLUDED.username,
  role     = EXCLUDED.role,
  status   = EXCLUDED.status;
