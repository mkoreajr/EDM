# Changelog

## 2.1.0 — Online orders & customer portal

Brings over the features from the v1 "online-orders-view-mobile-tablet-v47" build
that were missing from 2.0.0.

### Added
- **Customer portal at `/shop`**: register, sign in (show/hide password), browse Eggs / Flour / Rice
  with search and category filters, cart, checkout (Cash on Delivery, Mobile Money, Bank),
  My Orders, and order details with a delivery progress timeline. Cancelled orders show the reason.
  Customers use their own session cookie (30-minute idle sign-out), separate from staff logins.
- **Admin → Online Orders** (`/online-orders`): status summary cards, filter pills with counts,
  search by order / customer / phone / address, date range, **View** details (full-screen on
  tablet & mobile) and **Confirm** panel. Status changes save without reloading the page.
- Order workflow Pending → Confirmed → Out for Delivery → Delivered, or Cancelled before delivery,
  enforced on the server. Confirming reserves stock; cancelling releases it; delivering records the
  sale once (it then appears in Reports and Inventory). Cancelling asks for a reason.
- Sidebar **Online Orders** link with a pending-orders badge; admins get a notification for each new order.
- Migration `003_online_orders.sql` (`customer_accounts`, `orders`, `order_items`). It also upgrades
  databases that already ran the v1 customer portal, keeping their orders.
- `/admin` entry URL; "Customer Portal → Order Online" link on the staff login page.
- Staff are signed out after 2 minutes of inactivity (`ADMIN_IDLE_TIMEOUT`, 0 turns it off).
  Typing in a form counts as activity.
- SN (serial number) column on Recent Sales, Products and Customers lists.
- Show/hide password buttons on Change Password.
- Old v1 portal and order URLs (`/portal`, `/shop/*.php`, `/online_orders.php`, `/admin_entry`) redirect.

### Changed
- Customer passwords use bcrypt like staff passwords; v1 SHA-256 customer passwords still work and are upgraded at sign-in.
- The shop lists every Eggs / Flour / Rice product (all bag sizes), not one product per category.
- Login slideshow waits for the next picture to load before switching (no blank flash).
- Customers with online orders can't be deleted (friendly message instead of an error).
- **New round MSINDA emblem logo everywhere**: sidebar, staff and customer login pages, receipts and the PDF sales report.
- **Inter font across the whole system**, hosted inside the project (`frontend/assets/fonts`, SIL Open Font License),
  so no request goes to Google and the security headers are unchanged. Arial remains the fallback.
- Staff login support text is now "ICT Support : +255 682 657 202" (tap-to-call link).
- Online Orders header and filters now fit beside the sidebar on 1366px laptop screens.

## 2.0.0 — AgizaPoa

Full restructure of the v1.x egg-sales system into a containerised application.
All existing features, pages and the look of the system are kept.

### Architecture
- Split into `backend/` (PHP-FPM, MVC), `frontend/` (static assets), `nginx/`, `database/`.
- Single front controller with clean URLs (`/sales`, `/reports` …). Old `*.php` links redirect automatically.
- Versioned SQL migrations replace the run-everything-on-boot `init_db.php`.
- Docker Compose stack (PostgreSQL 16, PHP 8.3-FPM, Nginx) plus a single-container image for Render.
- The mysqli compatibility shim is replaced by a small PDO data layer with native prepared statements.
- Inline CSS/JS moved into `frontend/assets`; three duplicated pagination stylesheets merged into one component.

### Security
- bcrypt password hashing with automatic upgrade of old SHA-256 hashes at login.
- CSRF tokens on every form. Delete actions changed from GET links to confirmed POST forms
  (before, clicking a delete link deleted the record immediately).
- Login and recovery brute-force throttling.
- Session hardening and HTTP security headers (CSP, frame, referrer, HSTS).
- The default `admin123` account must change its password at first login (new databases).
- Users can't delete the last administrator; the user list reflects role changes and deletions immediately.

### Fixes
- Sales made between midnight and 03:00 were dated the previous day (server ran in UTC). The app and database session now use `Africa/Dar_es_Salaam`.
- Saving a purchase without a supplier failed (it inserted `supplier_id = 0`).
- Adding the same product twice in one sale could sell more than the available stock.
- Deleting a product that had sales showed a server error. It now explains why it can't be deleted.
- Quantities of exactly 1 showed as "1 trays".
- Receipt footer now uses the saved receipt setting; POS preselects the default payment method from Settings.
- The "Low Stock Alert" setting now actually turns dashboard stock alerts on/off.
- Report dates are validated; report pagination no longer lists every page number.
- Notification history is paginated.

### Performance
- Logo 1.79 MB → 130 KB. Login slides and assets served by Nginx with gzip and one-year caching.
- Settings page loads slide previews by URL instead of embedding full base64 images in the HTML.
- Dashboard: one query instead of eight; stock-alert queries no longer run on every page. Inventory and purchase history: joins instead of per-row sub-queries.
- New database indexes for sales, sale items, purchases, expenses, stock movements and notifications.
- OPcache enabled; PHP-FPM pool tuned for small servers.
