# Customer Portal Authentication — Restored & Improved

This version restores the Customer Portal login experience while keeping the existing database/authentication architecture.

## Customer flow
- `/shop` → Customer Login when signed out.
- Existing customer credentials are checked against `customer_accounts` using the existing backend authentication logic.
- Successful login → `customer_portal/shop.php` (customer home/dashboard).
- Customer-only pages are protected by `customer_portal/auth.php` and redirect unauthenticated visitors back to the Customer Login.
- Session timeout redirects to the Customer Login.
- Registration creates the existing `customers` + `customer_accounts` records and then signs the customer in.

## Password controls
- Login password has a Show/Hide eye button.
- Registration Password has its own Show/Hide eye button.
- Registration Confirm Password has a separate Show/Hide eye button.
- Eye buttons use `type="button"`, so they never submit or refresh the form.

## Validation
- Required fields are checked server-side.
- Username format is validated.
- Password strength is validated using the existing policy.
- Password and Confirm Password must match.
- Existing usernames are rejected with a friendly message.
- Invalid login credentials show a friendly error without exposing account details.

## Data safety
No existing customer, order, cart, product, or customer-account records are deleted or migrated. The existing `customer_accounts` schema and SHA-256 credential compatibility are preserved so existing customer passwords continue to work.

## Admin safety
Admin login files and the admin authentication flow were not changed. The Customer Login UI only follows the same visual language (Inter typography, green palette, card proportions, spacing, and button treatment).

## Render deployment
Use the same Render/Docker setup from the previous routing-fixed version. Upload/commit the changed project files and deploy with a fresh build if needed.
