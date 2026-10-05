# Customer Login Redirect Fix v28

Customer authentication redirects now use absolute `/shop` routes instead of relative `index.php` / `shop.php` redirects. This prevents an invalid customer password from resolving to the root Admin login (`/index.php`) when the public route is internally rewritten to `customer_portal/`.

Customer login failure stays on `/shop` and shows the existing customer error message. Admin authentication is unchanged.
