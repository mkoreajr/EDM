# Customer Auth Typography Fix — v15

Fixed the Customer Portal Login/Registration typography so it remains a clean modern sans-serif even when Google Fonts/Inter cannot be loaded.

The auth pages now use this fallback stack:
`Inter, Segoe UI, Roboto, Helvetica, Arial, sans-serif`

The authentication logic, database queries, customer records, orders, cart, and Admin Login were not changed by this typography-only fix.
