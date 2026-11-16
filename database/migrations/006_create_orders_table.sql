-- Orders placed by buyers.
CREATE TABLE orders (
    id {PK},
    order_number VARCHAR(30) NOT NULL UNIQUE,
    buyer_id {FK} NOT NULL,
    total DECIMAL(10,2) NOT NULL, -- Philippine pesos (₱)
    payment_method VARCHAR(30) NOT NULL DEFAULT 'gcash',
    payment_reference VARCHAR(50) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'paid',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
){ENGINE};

CREATE INDEX idx_orders_buyer ON orders(buyer_id);
