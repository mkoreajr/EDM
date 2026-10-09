-- 003 — Customer portal (/shop) accounts and online delivery orders.
--
-- Workflow: Pending → Confirmed → Out for Delivery → Delivered, or Cancelled
-- before delivery. Stock is reserved when an order is Confirmed, released if
-- it is Cancelled, and a sale is recorded once when it is Delivered.

CREATE TABLE IF NOT EXISTS customer_accounts (
    id            BIGSERIAL PRIMARY KEY,
    customer_id   BIGINT       NOT NULL UNIQUE REFERENCES customers (id) ON DELETE CASCADE,
    username      VARCHAR(80)  NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP    NULL
);

CREATE TABLE IF NOT EXISTS orders (
    id                  BIGSERIAL PRIMARY KEY,
    order_number        VARCHAR(40)   NOT NULL UNIQUE,
    customer_id         BIGINT        NOT NULL REFERENCES customers (id) ON DELETE RESTRICT,
    status              VARCHAR(30)   NOT NULL DEFAULT 'Pending',
    delivery_address    VARCHAR(255)  NOT NULL,
    phone               VARCHAR(30)   NOT NULL,
    payment_method      VARCHAR(30)   NOT NULL DEFAULT 'Cash on Delivery',
    notes               TEXT,
    total_amount        NUMERIC(12,2) NOT NULL DEFAULT 0,
    delivery_person     VARCHAR(100),
    admin_note          TEXT,
    stock_reserved      BOOLEAN       NOT NULL DEFAULT FALSE,
    sale_id             BIGINT        REFERENCES sales (id) ON DELETE SET NULL,
    created_at          TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    delivered_at        TIMESTAMP     NULL,
    cancellation_reason TEXT          NULL,
    cancelled_at        TIMESTAMP     NULL,
    cancelled_by        BIGINT        REFERENCES users (id) ON DELETE SET NULL
);

-- Databases that already ran the v1 customer portal have an older orders table:
-- bring it up to date without touching existing rows.
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_person     VARCHAR(100);
ALTER TABLE orders ADD COLUMN IF NOT EXISTS admin_note          TEXT;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS stock_reserved      BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS sale_id             BIGINT REFERENCES sales (id) ON DELETE SET NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivered_at        TIMESTAMP NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancellation_reason TEXT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancelled_at        TIMESTAMP NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancelled_by        BIGINT REFERENCES users (id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS order_items (
    id         BIGSERIAL PRIMARY KEY,
    order_id   BIGINT        NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
    product_id BIGINT        NOT NULL REFERENCES products (id) ON DELETE RESTRICT,
    quantity   NUMERIC(12,2) NOT NULL,
    unit_price NUMERIC(12,2) NOT NULL,
    total      NUMERIC(12,2) NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_orders_customer_id    ON orders (customer_id);
CREATE INDEX IF NOT EXISTS idx_orders_status         ON orders (status);
CREATE INDEX IF NOT EXISTS idx_orders_created_at     ON orders (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_order_items_order_id  ON order_items (order_id);
CREATE INDEX IF NOT EXISTS idx_order_items_product   ON order_items (product_id);
