-- Line items of an order. Title, price and delivered content are copied ("snapshotted")
-- so the order stays correct even if the seller later edits or deletes the listing.
CREATE TABLE order_items (
    id {PK},
    order_id {FK} NOT NULL,
    item_id {FK} NULL,
    seller_id {FK} NOT NULL,
    title VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    delivery_type VARCHAR(10) NOT NULL,
    delivered_content TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'delivered',
    confirmed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
){ENGINE};

CREATE INDEX idx_order_items_seller ON order_items(seller_id);
