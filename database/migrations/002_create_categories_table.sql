-- Categories of digital goods (game accounts, in-game items, gift cards ...)
CREATE TABLE categories (
    id {PK},
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(20) NOT NULL DEFAULT 'box',
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL
){ENGINE};
