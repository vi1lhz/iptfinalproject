-- One review per purchased line item, left after the buyer confirms delivery.
CREATE TABLE reviews (
    id {PK},
    order_item_id {FK} NOT NULL UNIQUE,
    item_id {FK} NULL,
    buyer_id {FK} NOT NULL,
    seller_id {FK} NOT NULL,
    rating INT NOT NULL,
    comment VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
){ENGINE};
