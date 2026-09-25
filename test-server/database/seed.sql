INSERT IGNORE INTO customers (id, name, email, country, created_at) VALUES
    (1, 'Ada Lovelace', 'ada@example.test', 'GB', '2026-01-10 10:00:00'),
    (2, 'Grace Hopper', 'grace@example.test', 'US', '2026-01-11 10:00:00'),
    (3, 'Katherine Johnson', 'katherine@example.test', 'US', '2026-01-12 10:00:00'),
    (4, 'Carl Friedrich Gauss', 'carl@example.test', 'DE', '2026-01-13 10:00:00');

INSERT IGNORE INTO products (id, sku, name, category, price, active) VALUES
    (1, 'BOOK-001', 'Algorithms', 'Books', 49.90, 1),
    (2, 'BOOK-002', 'Computing History', 'Books', 39.90, 1),
    (3, 'MUG-001', 'MCP Test Mug', 'Accessories', 12.50, 1),
    (4, 'OLD-001', 'Legacy Product', 'Clearance', 5.00, 0);

INSERT IGNORE INTO orders (id, customer_id, status, ordered_at, total_amount) VALUES
    (1, 1, 'paid', '2026-02-01 09:00:00', 99.80),
    (2, 2, 'paid', '2026-02-02 11:00:00', 52.40),
    (3, 3, 'shipped', '2026-02-03 12:00:00', 39.90),
    (4, 4, 'paid', '2026-02-04 13:00:00', 62.40);

INSERT IGNORE INTO order_items (id, order_id, product_id, quantity, unit_price) VALUES
    (1, 1, 1, 2, 49.90),
    (2, 2, 3, 1, 12.50),
    (3, 2, 1, 1, 39.90),
    (4, 3, 2, 1, 39.90),
    (5, 4, 1, 1, 49.90),
    (6, 4, 3, 1, 12.50);
