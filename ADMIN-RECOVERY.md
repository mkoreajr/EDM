# Admin password recovery

If the administrator password is forgotten, set a temporary Render environment variable:

`ADMIN_RECOVERY_CODE=<a strong private code>`

Then open `/admin-recovery.php`, enter that recovery code and choose a temporary password of at least 8 characters.

The recovery is one-time for that code and does not delete sales, stock, products, customers or other business data.

After signing in, change the temporary password. If you want another recovery later, use a new recovery code and reset the `system_recovery.admin_recovery_used` state only through a controlled database/admin procedure.
