# Admin password recovery

Use this if the administrator password is lost. It never deletes sales, stock,
products, customers or any other business data.

1. Set the environment variable `ADMIN_RECOVERY_CODE` to a long private secret:
   - **Render:** web service → *Environment* → add `ADMIN_RECOVERY_CODE`.
   - **Docker:** put `ADMIN_RECOVERY_CODE=...` in `.env`, then run `docker compose up -d`.
2. Open **`/admin-recovery`**, enter the code and choose a temporary password
   (8+ characters with uppercase, lowercase, a number and a special character).
3. Sign in as **admin** with that temporary password. You'll be asked to set a new one.
4. Remove `ADMIN_RECOVERY_CODE` again. The page returns *404* when it's not set.

Recovery works once. To allow it again, reset the flag in the database:

```sql
UPDATE system_recovery SET admin_recovery_used = FALSE WHERE id = 1;
```

Wrong codes count towards the login lockout (20 attempts per IP address in 15 minutes).
