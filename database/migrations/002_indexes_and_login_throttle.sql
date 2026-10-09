-- 002 — Performance indexes for dashboard, POS, report and notification
-- queries, plus the table used for login brute-force protection.

CREATE INDEX IF NOT EXISTS idx_sales_sale_date              ON sales (sale_date);
CREATE INDEX IF NOT EXISTS idx_sales_customer_id            ON sales (customer_id);
CREATE INDEX IF NOT EXISTS idx_sales_created_by             ON sales (created_by);
CREATE INDEX IF NOT EXISTS idx_sale_items_sale_id           ON sale_items (sale_id);
CREATE INDEX IF NOT EXISTS idx_sale_items_product_id        ON sale_items (product_id);
CREATE INDEX IF NOT EXISTS idx_purchases_purchase_date      ON purchases (purchase_date);
CREATE INDEX IF NOT EXISTS idx_purchase_items_purchase_id   ON purchase_items (purchase_id);
CREATE INDEX IF NOT EXISTS idx_expenses_expense_date        ON expenses (expense_date);
CREATE INDEX IF NOT EXISTS idx_stock_movements_product_type ON stock_movements (product_id, movement_type);
CREATE INDEX IF NOT EXISTS idx_products_category            ON products (category);
CREATE INDEX IF NOT EXISTS idx_customers_name               ON customers (name);
CREATE INDEX IF NOT EXISTS idx_notifications_user_created   ON notifications (user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_notifications_user_unread    ON notifications (user_id) WHERE read_at IS NULL;

CREATE TABLE IF NOT EXISTS login_attempts (
    id           BIGSERIAL PRIMARY KEY,
    username     VARCHAR(50) NOT NULL,
    ip_address   VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP   NOT NULL DEFAULT LOCALTIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_login_attempts_username ON login_attempts (LOWER(username), attempted_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip       ON login_attempts (ip_address, attempted_at);
