CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE TABLE IF NOT EXISTS users(id BIGSERIAL PRIMARY KEY,name VARCHAR(100) NOT NULL,username VARCHAR(50) UNIQUE NOT NULL,password VARCHAR(255) NOT NULL,role VARCHAR(30) NOT NULL DEFAULT 'Admin',must_change_password BOOLEAN NOT NULL DEFAULT FALSE,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
ALTER TABLE users ADD COLUMN IF NOT EXISTS must_change_password BOOLEAN NOT NULL DEFAULT FALSE;
CREATE TABLE IF NOT EXISTS products(id BIGSERIAL PRIMARY KEY,name VARCHAR(100) NOT NULL,unit VARCHAR(20) NOT NULL DEFAULT 'Tray',selling_price NUMERIC(12,2) DEFAULT 0,stock_quantity NUMERIC(12,2) DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(30) NOT NULL DEFAULT 'Eggs';
ALTER TABLE products ADD COLUMN IF NOT EXISTS package_size_kg NUMERIC(6,2) NULL;
ALTER TABLE products DROP CONSTRAINT IF EXISTS products_unit_check;
ALTER TABLE products ADD CONSTRAINT products_unit_check CHECK(unit IN ('Tray','Bag'));
UPDATE products SET category='Eggs', unit='Tray', package_size_kg=NULL WHERE category IS NULL OR category='';
CREATE TABLE IF NOT EXISTS customers(id BIGSERIAL PRIMARY KEY,name VARCHAR(100) NOT NULL,phone VARCHAR(30),address VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS suppliers(id BIGSERIAL PRIMARY KEY,name VARCHAR(100) NOT NULL,phone VARCHAR(30),address VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS sales(id BIGSERIAL PRIMARY KEY,sale_number VARCHAR(30) UNIQUE NOT NULL,customer_id BIGINT REFERENCES customers(id) ON DELETE SET NULL,sale_date DATE NOT NULL,payment_method VARCHAR(30) NOT NULL CHECK(payment_method IN ('Cash','Mobile Money','Bank')),total_amount NUMERIC(12,2) DEFAULT 0,created_by BIGINT NOT NULL REFERENCES users(id),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS sale_items(id BIGSERIAL PRIMARY KEY,sale_id BIGINT NOT NULL REFERENCES sales(id) ON DELETE CASCADE,product_id BIGINT NOT NULL REFERENCES products(id),quantity NUMERIC(12,2) NOT NULL,unit_price NUMERIC(12,2) NOT NULL,total NUMERIC(12,2) NOT NULL);
CREATE TABLE IF NOT EXISTS purchases(id BIGSERIAL PRIMARY KEY,supplier_id BIGINT REFERENCES suppliers(id) ON DELETE SET NULL,purchase_date DATE NOT NULL,total_amount NUMERIC(12,2) DEFAULT 0,created_by BIGINT NOT NULL REFERENCES users(id),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS purchase_items(id BIGSERIAL PRIMARY KEY,purchase_id BIGINT NOT NULL REFERENCES purchases(id) ON DELETE CASCADE,product_id BIGINT NOT NULL REFERENCES products(id),quantity NUMERIC(12,2) NOT NULL,unit_cost NUMERIC(12,2) NOT NULL,total NUMERIC(12,2) NOT NULL);
CREATE TABLE IF NOT EXISTS expenses(id BIGSERIAL PRIMARY KEY,expense_name VARCHAR(100) NOT NULL,amount NUMERIC(12,2) NOT NULL,expense_date DATE NOT NULL,description TEXT,created_by BIGINT NOT NULL REFERENCES users(id),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS stock_movements(id BIGSERIAL PRIMARY KEY,product_id BIGINT NOT NULL REFERENCES products(id),movement_type VARCHAR(20) NOT NULL CHECK(movement_type IN ('Purchase','Sale','Adjustment')),quantity NUMERIC(12,2) NOT NULL,reference_id BIGINT,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS app_settings(setting_key VARCHAR(100) PRIMARY KEY,setting_value TEXT NOT NULL);
CREATE TABLE IF NOT EXISTS login_slides(id BIGSERIAL PRIMARY KEY,filename VARCHAR(255) NOT NULL,mime_type VARCHAR(100) NOT NULL,image_data TEXT NOT NULL,sort_order INTEGER NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS notifications(id BIGSERIAL PRIMARY KEY,user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,title VARCHAR(150) NOT NULL,message TEXT NOT NULL,read_at TIMESTAMP NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

-- Performance indexes for common dashboard, report, sales and notification queries.
CREATE INDEX IF NOT EXISTS idx_sales_sale_date ON sales(sale_date);
CREATE INDEX IF NOT EXISTS idx_sales_created_by ON sales(created_by);
CREATE INDEX IF NOT EXISTS idx_sales_customer_id ON sales(customer_id);
CREATE INDEX IF NOT EXISTS idx_sale_items_sale_id ON sale_items(sale_id);
CREATE INDEX IF NOT EXISTS idx_sale_items_product_id ON sale_items(product_id);
CREATE INDEX IF NOT EXISTS idx_products_category_unit ON products(category,unit);
CREATE INDEX IF NOT EXISTS idx_products_stock_quantity ON products(stock_quantity);
CREATE INDEX IF NOT EXISTS idx_customers_name ON customers(name);
CREATE INDEX IF NOT EXISTS idx_expenses_expense_date ON expenses(expense_date);
CREATE INDEX IF NOT EXISTS idx_purchases_purchase_date ON purchases(purchase_date);
CREATE INDEX IF NOT EXISTS idx_notifications_user_created ON notifications(user_id,created_at DESC);
CREATE INDEX IF NOT EXISTS idx_notifications_user_unread ON notifications(user_id,read_at) WHERE read_at IS NULL;


INSERT INTO users(name,username,password,role) SELECT 'Administrator','admin',encode(digest('admin123','sha256'),'hex'),'Admin' WHERE NOT EXISTS(SELECT 1 FROM users WHERE username='admin');
INSERT INTO notifications(user_id,title,message) SELECT id,'Welcome to EDM Kienyeji Food Shop','Your administrator account is ready. You can manage sales, products and stock from the dashboard.' FROM users u WHERE u.username='admin' AND NOT EXISTS(SELECT 1 FROM notifications n WHERE n.user_id=u.id);

-- Online customer portal and delivery orders
CREATE TABLE IF NOT EXISTS customer_accounts(
  id BIGSERIAL PRIMARY KEY,
  customer_id BIGINT NOT NULL UNIQUE REFERENCES customers(id) ON DELETE CASCADE,
  username VARCHAR(80) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS orders(
  id BIGSERIAL PRIMARY KEY,
  order_number VARCHAR(40) UNIQUE NOT NULL,
  customer_id BIGINT NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
  status VARCHAR(30) NOT NULL DEFAULT 'Pending',
  delivery_address VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  payment_method VARCHAR(30) NOT NULL DEFAULT 'Cash on Delivery',
  notes TEXT,
  total_amount NUMERIC(12,2) NOT NULL DEFAULT 0,
  delivery_person VARCHAR(100),
  admin_note TEXT,
  stock_reserved BOOLEAN NOT NULL DEFAULT FALSE,
  sale_id BIGINT REFERENCES sales(id) ON DELETE SET NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  delivered_at TIMESTAMP NULL,
  cancellation_reason TEXT NULL,
  cancelled_at TIMESTAMP NULL,
  cancelled_by BIGINT REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items(
  id BIGSERIAL PRIMARY KEY,
  order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
  quantity NUMERIC(12,2) NOT NULL,
  unit_price NUMERIC(12,2) NOT NULL,
  total NUMERIC(12,2) NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_customer_accounts_username ON customer_accounts(username);
CREATE INDEX IF NOT EXISTS idx_orders_customer_id ON orders(customer_id);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id);

ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_person VARCHAR(100);
ALTER TABLE orders ADD COLUMN IF NOT EXISTS admin_note TEXT;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS stock_reserved BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS sale_id BIGINT REFERENCES sales(id) ON DELETE SET NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancellation_reason TEXT NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancelled_at TIMESTAMP NULL;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS cancelled_by BIGINT REFERENCES users(id) ON DELETE SET NULL;
