-- Users: buyers, sellers and admins share one table.
CREATE TABLE users (
    id {PK},
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    bio VARCHAR(255) NULL,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_verified TINYINT NOT NULL DEFAULT 0,
    is_banned TINYINT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL
){ENGINE};
