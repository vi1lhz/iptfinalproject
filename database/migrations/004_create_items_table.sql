-- Items: digital listings posted by sellers.
-- delivery_type = 'code' → buyer receives delivery_content (a code, account login, licence key)
-- delivery_type = 'file' → buyer downloads file_path (kept outside /public)
CREATE TABLE items (
    id {PK},
    seller_id {FK} NOT NULL,
    category_id {FK} NOT NULL,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(191) NOT NULL UNIQUE,
    game_id {FK} NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL, -- Philippine pesos (₱)
    stock INT NOT NULL DEFAULT 1,
    delivery_type VARCHAR(10) NOT NULL DEFAULT 'code',
    delivery_content TEXT NULL,
    file_path VARCHAR(255) NULL,
    file_name VARCHAR(255) NULL,
    image VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    views INT NOT NULL DEFAULT 0,
    sales_count INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL
){ENGINE};

CREATE INDEX idx_items_category ON items(category_id);
CREATE INDEX idx_items_seller ON items(seller_id);
CREATE INDEX idx_items_status ON items(status);
CREATE INDEX idx_items_game ON items(game_id);
