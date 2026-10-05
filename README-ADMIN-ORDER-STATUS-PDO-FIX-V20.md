# MSINDA Food Shop — Admin Order Status PDO Fix v20

## Root-cause fix
The Admin Order status transition handler now uses the native PostgreSQL PDO connection (`$pdo`) for the critical transaction instead of the legacy MySQLi-compatible wrapper.

This fixes the Confirm / Out for Delivery / Delivered save path where the browser could receive an unexpected response from a failing database write.

## Workflow
- Pending -> Confirmed
- Confirmed -> Out for Delivery
- Out for Delivery -> Delivered
- Cancellation remains supported according to the existing rules.

## Confirmed behavior
- Stock is reserved exactly once when an order becomes Confirmed.
- Confirmation is transactional; stock and order status roll back together on failure.
- Cancellation releases previously reserved stock when applicable.
- Delivered creates the sale once and records sale items/movements without deducting stock a second time.
- The backend always returns JSON for AJAX status updates, including database errors.
- The frontend only reports success after the database update commits.

## Files changed
- `online_orders.php`
- `online_orders_v5.php` (kept consistent with the active implementation)

No customer accounts, orders, cart data, or admin authentication were deleted or reset.
