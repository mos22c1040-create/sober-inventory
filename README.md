# Inventory POS (Sober)

A simple PHP MVC Inventory & Point of Sale application with authentication, products, categories, POS, purchases, and reports.

## Features

- **Auth**: Login / Logout with session, CSRF protection, password hashing (bcrypt)
- **Dashboard**: Today’s sales, invoice count, product count, low-stock alert, recent sales
- **Products**: CRUD with category, SKU, price, cost, quantity, low-stock threshold
- **Categories**: CRUD for product categories
- **POS**: Point of Sale – search products, add to cart, complete sale (cash/card/mixed)
- **Purchases**: Record stock-in (restock) with supplier and line items
- **Reports**: Sales by day (last 30 days), top products by quantity/revenue

## Setup

1. **PHP**  
   PHP 7.4+ with PDO MySQL.

2. **Database**  
   Create a MySQL database (e.g. `inventory_pos`) and run the schema:

   ```bash
   mysql -u root -p inventory_pos < storage/schema.sql
   ```

3. **Environment**  
   Copy `.env` (or create it) and set:

   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=inventory_pos
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

4. **Web server**  
   Point document root to `public/` (or use PHP built-in server):

   ```bash
   cd public && php -S localhost:8000
   ```

   Then open: `http://localhost:8000`

5. **Admin user**  
   If the `users` table is empty, insert one (password here is `admin123`):

   ```sql
   INSERT INTO users (username, email, password, role, status)
   VALUES ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');
   ```

## Security (aligned with skills)

- **OWASP**: Prepared statements (no SQL injection), output escaped (XSS), CSRF on forms, secure sessions (HttpOnly, SameSite), bcrypt passwords.
- **Input**: Validation and sanitization in controllers; optional use of `Security::sanitizeString()` for display.

## Project structure

- `app/Controllers/` – Auth, Dashboard, Product, Category, Pos, Purchase, Report
- `app/Models/` – Category, Product, Sale, Purchase
- `app/Core/` – Router, Database (PDO singleton)
- `app/Helpers/` – AuthHelper, Security
- `config/` – DB and app settings
- `views/` – Layouts (header, sidebar, footer), auth, dashboard, products, categories, pos, purchases, reports
- `public/index.php` – Entry point and route definitions
- `storage/schema.sql` – Database schema

## Skills used

Implementation follows patterns from the project’s **skills**:

- **workflow-patterns**: Structured tasks (schema → models → controllers → views), clear steps.
- **web-security-testing**: Secure auth, CSRF, prepared statements, output encoding, session handling.
