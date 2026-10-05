# Render routing fix

This version keeps `/shop` and `/admin` as public routes while the physical folders remain `customer_portal/` and `admin_entry/`.

The Dockerfile now enables Apache `AllowOverride All`, so the root `.htaccess` rewrite rules are actually honored in the PHP Apache image.

Public routes:
- `/shop` -> `customer_portal/index.php`
- `/admin` -> `admin_entry/index.php`

Do not create physical `shop/` or `admin/` directories; doing so can trigger Render's internal-port DirectorySlash redirect.
