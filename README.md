# Sober Inventory

A PHP MVC inventory management system with authentication, products, categories, purchases, reports, user management, and barcode scanning (USB scanner + camera, optional mobile-to-PC bridge).

---

## Features

- **Auth** — Login/logout, sessions, CSRF protection, bcrypt passwords, role-based access (admin/cashier)
- **Dashboard** — Today’s sales, invoice count, products, low-stock alerts
- **Products** — CRUD, SKU/barcode, categories, stock, low-stock threshold
- **Categories** — CRUD for product categories
- **Purchases** — Stock-in (restock) with supplier and line items
- **Reports** — Sales by day (last 30 days), top products
- **Users** — Admin-only user management (create, edit, delete, change password)
- **Barcode** — Search by barcode/SKU; camera scan (Android Chrome); optional “phone → PC” bridge when both use same app URL

---

## Requirements

- **PHP** 7.4+ with PDO enabled
- **Database (choose one):**
  - MySQL 5.7+ / MariaDB
  - PostgreSQL (recommended for Railway/Supabase)
- Web server (Apache/Nginx) or PHP built-in server

---

## Setup (local)

### 1. Clone the repository

```bash
git clone https://github.com/mos22c1040-create/sober-inventory.git
cd sober-inventory
```

### 2. Database

#### Option A — MySQL (local)

```bash
mysql -u root -p -e "CREATE DATABASE inventory_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p inventory_pos < storage/schema.sql
```

#### Option B — PostgreSQL (Supabase/Railway)

Use a PostgreSQL connection string in `.env`:

```env
DATABASE_URL=postgresql://USER:PASSWORD@HOST:PORT/postgres
```

> Keep `DB_*` values empty when using `DATABASE_URL`.

**activity_log patch:** if you see an `activity_log` table error, run:

```bash
php storage/run_patch_activity_log.php
```

### 3. Environment

Copy the example env file and set your database credentials:

```bash
cp .env.example .env
```

Edit `.env` (choose one mode only):

```env
# --- MySQL mode ---
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_pos
DB_USERNAME=root
DB_PASSWORD=your_password

# --- PostgreSQL mode (Supabase/Railway) ---
# DATABASE_URL=postgresql://USER:PASSWORD@HOST:PORT/postgres

APP_ENV=development
SESSION_DRIVER=file
```

### 4. Web server

**Option A — PHP built-in server (development):**

Run from the **project root** (so that all routes like `/dashboard`, `/products` work):

```bash
php -S localhost:8000 -t public public/index.php
```

Then open: **http://localhost:8000**

**ويندوز — تشغيل سريع:** يمكنك النقر المزدوج على `run.bat` في جذر المشروع لبدء السيرفر.

**Option B — Apache/Nginx:**  
Point the document root to the **`public/`** directory of the project.

### 5. Admin user

If the `users` table is empty, create an admin (default password: `password`):

```sql
INSERT INTO users (username, email, password, role, status)
VALUES (
  'admin',
  'admin@example.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'admin',
  'active'
);
```

Change the password after first login.

---

## Deploy (Railway + Supabase)

1. Push your latest code to `main`.
2. In Railway, connect the GitHub repo.
3. Set environment variables in Railway:
   - `APP_ENV=production`
   - `DATABASE_URL=postgresql://...` (from Supabase or Railway Postgres)
   - `SESSION_DRIVER=file` (or `database` for serverless flows)
4. Redeploy and verify the app health using the checklist below.

### Post-deploy Smoke Test

Use this checklist after every deploy:
- Open `/login` and sign in with admin.
- Open `/dashboard` and confirm cards/charts load.
- Open `/products` and test barcode search.
- Open `/sales/create`, complete a sale, and check `/sales/receipt?id=...`.
- Verify role access: cashier cannot access admin pages.

If you want a reusable checklist file, see `docs/DEPLOY_CHECKLIST.md`.

---

## Project structure

```
├── app/
│   ├── Controllers/   # Auth, Dashboard, Product, Category, Purchase, Report, User
│   ├── Core/          # Router, Database (PDO singleton)
│   ├── Helpers/       # AuthHelper, Security
│   └── Models/        # Category, Product, Sale, Purchase, User
├── config/            # DB and app settings
├── public/            # Document root — index.php, .htaccess
├── storage/           # schema.sql, patches, logs, barcode bridge data
├── views/             # Layouts, auth, dashboard, products, categories, etc.
├── .env.example
├── .gitignore
└── README.md
```

---

## Security

- Prepared statements (no SQL injection)
- Output escaped (XSS)
- CSRF tokens on forms and API calls
- Secure sessions (HttpOnly, SameSite)
- Bcrypt password hashing
- Role-based access (admin vs cashier)

---

## Push to a new private repository

From your local clone:

```bash
# Add the new remote (replace with your repo URL)
git remote add origin https://github.com/YOUR_USERNAME/YOUR_PRIVATE_REPO.git

# Or if you already have 'origin' and want to replace it:
git remote set-url origin https://github.com/YOUR_USERNAME/YOUR_PRIVATE_REPO.git

# Push and set upstream
git branch -M main
git push -u origin main
```

To create a **new** private repo on GitHub and push this code into it:

1. On GitHub: **New repository** → name it → set to **Private** → do **not** add README or .gitignore.
2. Locally (in your project folder):

```bash
git remote add origin https://github.com/YOUR_USERNAME/YOUR_NEW_PRIVATE_REPO.git
git branch -M main
git push -u origin main
```

Use a [Personal Access Token](https://github.com/settings/tokens) or SSH if prompted for credentials.

---

## License

Use and modify as needed for your project.
