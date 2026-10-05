# MSINDA Food Shop Portal URLs

- Customer portal: `/shop`
- Admin portal: `/admin`

Customer authentication uses `portal_customer_id` and is separate from the admin `user_id` / `role` session values.
Admin pages continue to enforce `is_admin()` through `auth.php`, so a customer account cannot open the admin dashboard.

The original `/portal` customer route remains available for backward compatibility.
