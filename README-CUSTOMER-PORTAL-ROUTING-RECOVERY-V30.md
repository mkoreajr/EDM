# V30 — Customer Portal Routing Recovery

This build restores robust /shop routing by configuring Apache's vhost directly, in addition to .htaccess.

- /shop -> customer_portal/index.php
- /shop/register.php -> customer_portal/register.php
- /shop/login.php -> customer_portal/login.php
- /shop/assets/... -> customer_portal/assets/...
- /admin -> admin_entry/index.php

The browser URL remains /shop. Customer authentication and existing database data are preserved.

Deploy the full ZIP and use Render Manual Deploy -> Clear build cache & deploy.
