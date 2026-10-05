# MSINDA Food Shop — Admin Order View & Confirm v18

## What changed
- **View** is now independent from the order-processing controls.
- Clicking **View** opens a complete, database-backed order detail panel.
- The detail panel shows every order item with product name, quantity, unit price and line total.
- It also shows customer, phone, delivery address, order date, payment method, customer note, admin note and order total.
- The former **Manage** button is now **Confirm**.
- Confirm opens the existing order-processing workflow and uses the existing backend status API/database transaction.
- Status workflow remains: `Pending → Confirmed → Out for Delivery → Delivered`, with `Cancelled` available according to the existing transition rules.
- After a successful status change, the View status and status selector are updated immediately without a page refresh.
- Existing inventory reservation, sales creation, customer records, cart data and order records are preserved.

## Deployment
Deploy this version as a complete project. On Render use **Manual Deploy → Clear build cache & deploy**.
