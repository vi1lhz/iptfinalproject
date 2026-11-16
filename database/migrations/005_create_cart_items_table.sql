-- Shopping cart rows (one per user + item).
CREATE TABLE cart_items (
    id {PK},
    user_id {FK} NOT NULL,
    item_id {FK} NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    UNIQUE (user_id, item_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
){ENGINE};
