# AgizaPoa — MSINDA Food Shop System

Point-of-sale, stock and reporting system for **MSINDA / EDM Kienyeji Food Shop**
(eggs by tray, rice and flour by bag). Built for speed on cheap hosting and easy day-to-day changes.

| Layer     | Technology                                                    |
|-----------|---------------------------------------------------------------|
| Backend   | PHP 8.3 (FPM + OPcache), small MVC framework, PDO             |
| Frontend  | Server-rendered HTML, plain CSS & JavaScript (no build step)  |
| Web       | Nginx 1.27 — static files, gzip, long-term caching            |
| Database  | PostgreSQL 16 with versioned SQL migrations                   |
| Runtime   | Docker Compose (local / VPS) · single container on Render     |

---

## Project structure

```
AgizaPoa/
├── backend/                     PHP application (served by PHP-FPM)
│   ├── public/index.php         Front controller — every request starts here
│   ├── src/
│   │   ├── bootstrap.php        Autoloader, timezone, error handling
│   │   ├── helpers.php          e(), money(), url(), asset(), view(), flash() …
│   │   ├── routes.php           All URLs and who may open them
│   │   ├── Core/                Router, DB, Auth, Session, Csrf, View
│   │   ├── Controllers/         One controller per feature (Sales, Products, …)
│   │   └── Services/            Password, Settings, Notifications, PDF, Migrator …
│   ├── views/                   HTML templates (layouts/, partials/, one folder per feature)
│   ├── resources/               Server-side files (PDF report logo)
│   ├── bin/migrate.php          Applies database migrations
│   ├── docker/                  PHP / PHP-FPM config and container start scripts
│   └── Dockerfile
├── frontend/
│   ├── assets/css/              app.css (dashboard), orders.css, portal.css (customer shop), login.css, …
│   ├── assets/js/               app.js (shared), orders.js, portal.js, pos.js, products.js, login.js, …
│   ├── assets/img/              Logo and login slideshow pictures
│   └── robots.txt
├── nginx/
│   ├── templates/default.conf.template
│   └── Dockerfile
├── database/
│   └── migrations/              001_initial_schema.sql, 002_… (applied in order, once each)
├── docs/                        Extra guides
├── docker-compose.yml           db + backend + nginx (+ optional adminer)
├── docker-compose.dev.yml       Live-edit overrides for development
├── render.yaml                  Render Blueprint (one container + PostgreSQL)
└── .env.example                 Configuration template
```

**How a request flows:** Browser → **Nginx** → `/assets/*` served straight from `frontend/`
(cached for a year) · everything else → **PHP-FPM** → `public/index.php` → `Router` →
`Controller` → `views/` → HTML.

---

## Run it with Docker

Requirements: [Docker Desktop](https://www.docker.com/products/docker-desktop/).

```powershell
copy .env.example .env          # then open .env and set DB_PASSWORD
docker compose up -d --build
```

Open **http://localhost:8080** and sign in with **admin / admin123**. You'll be asked to set a new password straight away.

| Address                          | Who                                                        |
|----------------------------------|------------------------------------------------------------|
| `http://localhost:8080/`         | Staff login (also `/admin`)                                 |
| `http://localhost:8080/shop`     | Customer portal: customers register, order and track delivery |

| Task                              | Command                                                   |
|-----------------------------------|-----------------------------------------------------------|
| Stop                              | `docker compose down`                                     |
| View logs                         | `docker compose logs -f backend nginx`                    |
| Database UI (Adminer on :8081)    | `docker compose --profile tools up -d adminer`            |
| Open a SQL shell                  | `docker compose exec db psql -U agizapoa agizapoa`        |
| Back up the database              | `docker compose exec db pg_dump -U agizapoa agizapoa > backup.sql` |
| Delete everything incl. data      | `docker compose down -v` ⚠️                               |

### Development mode (edit and refresh)

```powershell
docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build
```

PHP files, views, CSS and JS are mounted from your folder, so changes show up when you refresh the page.
Detailed error pages are on, and PostgreSQL is reachable on `localhost:5432`.

---

## Deploy to Render

1. Push this repository to GitHub.
2. Render → **New → Blueprint** → choose the repository. `render.yaml` creates the web service and a PostgreSQL database.
3. Migrations run automatically on every deploy. The health check is `/health`.

**Keeping your existing data from the old Render service:** open the new web service →
*Environment* → set `DATABASE_URL` to your **old database's Internal Database URL**.
The migrations are written to run safely against the v1 schema. They only add what's missing.

---

## Everyday changes

| I want to…                         | Edit                                                                   |
|------------------------------------|------------------------------------------------------------------------|
| Change how a page looks            | `backend/views/<feature>/*.php` and `frontend/assets/css/app.css`      |
| Change what a page does            | `backend/src/Controllers/<Feature>Controller.php`                      |
| Add a page                         | Add a controller method → a route in `backend/src/routes.php` → a view |
| Change the database                | Add `database/migrations/004_describe_change.sql` (never edit old ones) |
| Change sidebar / top bar / footer  | `backend/views/layouts/app.php`                                        |
| Change the customer shop           | `backend/views/shop/*`, `layouts/shop.php`, `frontend/assets/css/portal.css` |
| Change the order workflow rules    | `backend/src/Services/OrderWorkflow.php`                               |
| Change the staff idle sign-out     | `ADMIN_IDLE_TIMEOUT` in `.env` / Render (seconds, 0 = off)             |

Conventions that keep the app secure:

- Every `<form method="post">` must include `<?= csrf_field() ?>`. The router rejects POSTs without it.
- Always print user data with `<?= e($value) ?>`.
- Query with parameters: `DB::all('SELECT … WHERE id = ?', [$id])`. Never put variables into SQL strings.
- Mark admin-only routes with `$admin` in `routes.php`.

---

## Security features

- Passwords hashed with **bcrypt** (`password_hash`). Old SHA-256 hashes still work and are upgraded automatically at the next login.
- **CSRF protection** on every form; deletes are POST-only and ask for confirmation.
- **Login throttling:** 5 failed attempts per username / 20 per IP address → locked out for 15 minutes.
- Hardened sessions (HttpOnly, SameSite, Secure on HTTPS, regenerated at login).
- Security headers (Content-Security-Policy, X-Frame-Options, HSTS on HTTPS, …).
- New default admin must change `admin123` at first login.
- Role checks for Admin vs Cashier on the server, per route.
- One-time admin recovery: see [docs/ADMIN-RECOVERY.md](docs/ADMIN-RECOVERY.md).

## Performance features

- Nginx serves CSS/JS/images directly with gzip and one-year immutable caching (cache-busted per deploy).
- OPcache keeps compiled PHP in memory; PHP-FPM process pool.
- Logo reduced from 1.79 MB to 130 KB; settings page no longer embeds images as base64.
- Dashboard uses one query instead of eight, and the stock-alert queries no longer run on every page; inventory and purchases use joins instead of per-row sub-queries.
- Database indexes on every column used for filtering, joining and sorting.

---

## Roles

| Page                                                   | Admin | Cashier |
|--------------------------------------------------------|:-----:|:-------:|
| Home, Sales (POS), Receipts, Customers, Reports        |   ✓   |    ✓    |
| Notifications, Change Password                         |   ✓   |    ✓    |
| Products, Inventory, Purchases, Suppliers, Expenses    |   ✓   |         |
| Online Orders                                          |   ✓   |         |
| Report export (Excel/PDF), Settings, Change Name       |   ✓   |         |

Customers only ever see the `/shop` portal. Their accounts are separate from staff accounts.

See [CHANGELOG.md](CHANGELOG.md) for everything that changed in v2.
