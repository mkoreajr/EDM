-- 004 — Keep the product name on every sale, online-order and purchase line.
--
-- Products can now be renamed (e.g. when the rice source changes). Each line
-- stores the name the product had when the record was made, so receipts,
-- reports and order history never change after a rename.
--
-- Existing lines are stamped with the product's name at the time this
-- migration runs. Only columns are added; no existing data is changed.

ALTER TABLE sale_items     ADD COLUMN IF NOT EXISTS product_name VARCHAR(100);
ALTER TABLE order_items    ADD COLUMN IF NOT EXISTS product_name VARCHAR(100);
ALTER TABLE purchase_items ADD COLUMN IF NOT EXISTS product_name VARCHAR(100);

UPDATE sale_items si     SET product_name = p.name FROM products p WHERE p.id = si.product_id AND si.product_name IS NULL;
UPDATE order_items oi    SET product_name = p.name FROM products p WHERE p.id = oi.product_id AND oi.product_name IS NULL;
UPDATE purchase_items pi SET product_name = p.name FROM products p WHERE p.id = pi.product_id AND pi.product_name IS NULL;
