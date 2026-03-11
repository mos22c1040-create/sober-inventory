# Deploy Checklist (Sober)

Use this checklist after each push/deploy to production.

## 1) Environment

- `APP_ENV=production`
- `DATABASE_URL` is set and valid
- `SESSION_DRIVER` is set (`file` or `database`)
- No secrets committed in git (`.env` stays local)

## 2) Core Routes

- `/login` loads and accepts valid credentials
- `/dashboard` loads without PHP warnings/errors
- `/products` list and search work
- `/sales` list opens
- `/sales/create` opens and can submit invoice

## 3) Sales Flow

- Add product by search/barcode
- Complete sale successfully
- Receipt opens at `/sales/receipt?id=<sale_id>`
- Stock is updated after sale

## 4) Permissions

- Admin can access: users, reports, settings, purchases
- Cashier is blocked from admin-only pages
- Unauthorized access returns 403 page

## 5) UX Quick Checks

- Sidebar and header render correctly on desktop/mobile
- No broken layout in login, dashboard, products, sales
- Buttons and forms have visible focus states
- No horizontal overflow on mobile

## 6) Logs & Health

- No fatal errors in app logs
- No DB connection errors
- Response time is acceptable on dashboard and products

## 7) Rollback Readiness

- Last stable commit hash is known
- Ability to redeploy previous commit is confirmed
