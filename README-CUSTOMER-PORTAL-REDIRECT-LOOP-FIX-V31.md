# Customer Portal Redirect Loop Fix v31

## Root cause
After a successful customer login, `/shop` was rewritten internally to `customer_portal/index.php`. The index then redirected an already-authenticated customer back to `/shop`, creating an infinite redirect loop (`ERR_TOO_MANY_REDIRECTS`).

## Fix
The customer portal entry point now:
- shows the login page when unauthenticated;
- internally renders `shop.php` when authenticated;
- keeps `/shop` in the browser URL;
- avoids redirecting `/shop` to itself.

The duplicate `portal/index.php` entry point was fixed the same way.

No database/authentication model changes were made.
