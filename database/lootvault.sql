-- LootVault database dump (MySQL / MariaDB)
-- Generated 2026-11-16 17:48:19 by cli/migrate.php --export
-- Import in phpMyAdmin: Import tab → choose this file → Go

CREATE DATABASE IF NOT EXISTS `lootvault` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lootvault`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `games`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `migrations`;

CREATE TABLE migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(191) NOT NULL,
    ran_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 001_create_users_table.sql
-- Users: buyers, sellers and admins share one table.
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    bio VARCHAR(255) NULL,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_verified TINYINT NOT NULL DEFAULT 0,
    is_banned TINYINT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 002_create_categories_table.sql
-- Categories of digital goods (game accounts, in-game items, gift cards ...)
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(20) NOT NULL DEFAULT 'box',
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 003_create_games_table.sql
-- Games catalog, filled from the FreeToGame API (https://www.freetogame.com/api).
-- id is FreeToGame's own game id (not auto-increment) so re-syncing updates rows instead of duplicating them.
CREATE TABLE games (
    id INT UNSIGNED NOT NULL PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    thumbnail VARCHAR(255) NULL,
    short_description VARCHAR(500) NULL,
    description TEXT NULL,
    genre VARCHAR(60) NULL,
    platform VARCHAR(60) NULL,
    publisher VARCHAR(150) NULL,
    developer VARCHAR(150) NULL,
    release_date VARCHAR(20) NULL,
    game_url VARCHAR(255) NULL,
    profile_url VARCHAR(255) NULL,
    screenshots TEXT NULL,
    requirements TEXT NULL,
    popularity_rank INT NOT NULL DEFAULT 9999,
    synced_at DATETIME NOT NULL,
    details_synced_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_games_genre ON games(genre);

-- 004_create_items_table.sql
-- Items: digital listings posted by sellers.
-- delivery_type = 'code' → buyer receives delivery_content (a code, account login, licence key)
-- delivery_type = 'file' → buyer downloads file_path (kept outside /public)
CREATE TABLE items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(191) NOT NULL UNIQUE,
    game_id INT UNSIGNED NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_items_category ON items(category_id);
CREATE INDEX idx_items_seller ON items(seller_id);
CREATE INDEX idx_items_status ON items(status);
CREATE INDEX idx_items_game ON items(game_id);

-- 005_create_cart_items_table.sql
-- Shopping cart rows (one per user + item).
CREATE TABLE cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    UNIQUE (user_id, item_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 006_create_orders_table.sql
-- Orders placed by buyers.
CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(30) NOT NULL UNIQUE,
    buyer_id INT UNSIGNED NOT NULL,
    total DECIMAL(10,2) NOT NULL, -- Philippine pesos (₱)
    payment_method VARCHAR(30) NOT NULL DEFAULT 'gcash',
    payment_reference VARCHAR(50) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'paid',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_orders_buyer ON orders(buyer_id);

-- 007_create_order_items_table.sql
-- Line items of an order. Title, price and delivered content are copied ("snapshotted")
-- so the order stays correct even if the seller later edits or deletes the listing.
CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NULL,
    seller_id INT UNSIGNED NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_order_items_seller ON order_items(seller_id);

-- 008_create_reviews_table.sql
-- One review per purchased line item, left after the buyer confirms delivery.
CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT UNSIGNED NOT NULL UNIQUE,
    item_id INT UNSIGNED NULL,
    buyer_id INT UNSIGNED NOT NULL,
    seller_id INT UNSIGNED NOT NULL,
    rating INT NOT NULL,
    comment VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `ran_at`) VALUES
('1', '001_create_users_table', '2026-11-16 17:48:18'),
('2', '002_create_categories_table', '2026-11-16 17:48:18'),
('3', '003_create_games_table', '2026-11-16 17:48:18'),
('4', '004_create_items_table', '2026-11-16 17:48:18'),
('5', '005_create_cart_items_table', '2026-11-16 17:48:18'),
('6', '006_create_orders_table', '2026-11-16 17:48:18'),
('7', '007_create_order_items_table', '2026-11-16 17:48:18'),
('8', '008_create_reviews_table', '2026-11-16 17:48:18');

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `bio`, `balance`, `is_verified`, `is_banned`, `created_at`) VALUES
('1', 'admin', 'admin@lootvault.test', '$2y$10$0tMdE2sy2e0qtBOQEkBXa.hjg45HsXUAhJ2HYvQEJ51iMrvkC.z/a', 'admin', 'LootVault staff account.', '0', '1', '0', '2025-10-12 17:48:18'),
('2', 'NovaTrades', 'seller@lootvault.test', '$2y$10$0tMdE2sy2e0qtBOQEkBXa.hjg45HsXUAhJ2HYvQEJ51iMrvkC.z/a', 'user', 'Top-rated seller of accounts and top-ups since 2021. Fast delivery, 7-day support.', '3148.2', '1', '0', '2025-11-06 17:48:18'),
('3', 'ArcaneDeals', 'arcane@lootvault.test', '$2y$10$0tMdE2sy2e0qtBOQEkBXa.hjg45HsXUAhJ2HYvQEJ51iMrvkC.z/a', 'user', 'MMO veteran. Rare items and fresh accounts.', '585', '1', '0', '2025-12-01 17:48:18'),
('4', 'PixelPirate', 'pixel@lootvault.test', '$2y$10$0tMdE2sy2e0qtBOQEkBXa.hjg45HsXUAhJ2HYvQEJ51iMrvkC.z/a', 'user', 'Racing and shooter game items + stream overlays.', '378', '0', '0', '2025-12-26 17:48:18'),
('5', 'CozyCrafts', 'cozy@lootvault.test', '$2y$10$0tMdE2sy2e0qtBOQEkBXa.hjg45HsXUAhJ2HYvQEJ51iMrvkC.z/a', 'user', 'Cute pets, blocky worlds and pixel art.', '315', '0', '0', '2026-01-20 17:48:18'),
('6', 'VaultKeys', 'keys@lootvault.test', '$2y$10$0tMdE2sy2e0qtBOQEkBXa.hjg45HsXUAhJ2HYvQEJ51iMrvkC.z/a', 'user', 'Official digital gift cards and subscription codes.', '467.1', '1', '0', '2026-02-14 17:48:18'),
('7', 'LumenStudio', 'lumen@lootvault.test', '$2y$10$0tMdE2sy2e0qtBOQEkBXa.hjg45HsXUAhJ2HYvQEJ51iMrvkC.z/a', 'user', 'Indie art studio – UI kits, sprites and brushes.', '719.1', '1', '0', '2026-03-11 17:48:18'),
('8', 'ByteForge', 'byte@lootvault.test', '$2y$10$0tMdE2sy2e0qtBOQEkBXa.hjg45HsXUAhJ2HYvQEJ51iMrvkC.z/a', 'user', 'Game dev templates and scripts.', '1169.1', '0', '0', '2026-04-05 17:48:18'),
('9', 'SoundSmith', 'sound@lootvault.test', '$2y$10$0tMdE2sy2e0qtBOQEkBXa.hjg45HsXUAhJ2HYvQEJ51iMrvkC.z/a', 'user', 'Composer making royalty-free music for creators.', '674.1', '0', '0', '2026-04-30 17:48:18'),
('10', 'buyer', 'buyer@lootvault.test', '$2y$10$0tMdE2sy2e0qtBOQEkBXa.hjg45HsXUAhJ2HYvQEJ51iMrvkC.z/a', 'user', NULL, '0', '0', '0', '2026-05-25 17:48:18'),
('11', 'kaizen_ph', 'kaizen@lootvault.test', '$2y$10$0tMdE2sy2e0qtBOQEkBXa.hjg45HsXUAhJ2HYvQEJ51iMrvkC.z/a', 'user', NULL, '0', '0', '0', '2026-06-19 17:48:18'),
('12', 'mika_plays', 'mika@lootvault.test', '$2y$10$0tMdE2sy2e0qtBOQEkBXa.hjg45HsXUAhJ2HYvQEJ51iMrvkC.z/a', 'user', NULL, '0', '0', '0', '2026-07-14 17:48:18');

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `description`, `created_at`) VALUES
('1', 'Game Accounts', 'game-accounts', 'user', 'Levelled and rare accounts for popular games.', '2025-09-22 17:48:18'),
('2', 'In-game Items', 'in-game-items', 'sword', 'Weapons, skins, pets, passes and bundles.', '2025-09-22 17:48:18'),
('3', 'Gift Cards', 'gift-cards', 'gift', 'Store credit and subscription codes.', '2025-09-22 17:48:18'),
('4', 'Top-ups', 'top-ups', 'coin', 'In-game currency delivered by code.', '2025-09-22 17:48:18'),
('5', 'Digital Art', 'digital-art', 'palette', 'UI kits, sprites, brushes and overlays.', '2025-09-22 17:48:18'),
('6', 'Software', 'software', 'code', 'Bots, scripts and game templates.', '2025-09-22 17:48:18'),
('7', 'Music & Audio', 'music-audio', 'music', 'Royalty-free tracks and sound effects.', '2025-09-22 17:48:18');

INSERT INTO `games` (`id`, `title`, `thumbnail`, `short_description`, `description`, `genre`, `platform`, `publisher`, `developer`, `release_date`, `game_url`, `profile_url`, `screenshots`, `requirements`, `popularity_rank`, `synced_at`, `details_synced_at`) VALUES
('345', 'Forge of Empires', 'https://www.freetogame.com/g/345/thumbnail.jpg', 'A free to play 2D browser-based online strategy game, become the leader and raise your city.', NULL, 'Strategy', 'Web Browser', 'InnoGames', 'InnoGames', NULL, 'https://www.freetogame.com/open/forge-of-empires', 'https://www.freetogame.com/forge-of-empires', NULL, NULL, '8', '2000-01-01 00:00:00', NULL),
('347', 'Elvenar', 'https://www.freetogame.com/g/347/thumbnail.jpg', 'A browser-based city-building strategy game with elves and humans.', NULL, 'Strategy', 'Web Browser', 'InnoGames', 'InnoGames', NULL, 'https://www.freetogame.com/open/elvenar', 'https://www.freetogame.com/elvenar', NULL, NULL, '9', '2000-01-01 00:00:00', NULL),
('452', 'Call of Duty: Warzone', 'https://www.freetogame.com/g/452/thumbnail.jpg', 'A standalone free-to-play battle royale and modes accessible via Call of Duty: Modern Warfare.', 'Call of Duty: Warzone is both a standalone free-to-play battle royale and modes accessible via Call of Duty: Modern Warfare. Warzone features two modes — the general 150-player battle royale, and “Plunder”.', 'Shooter', 'PC (Windows)', 'Activision', 'Infinity Ward', '2020-03-10', 'https://www.freetogame.com/open/call-of-duty-warzone', 'https://www.freetogame.com/call-of-duty-warzone', '["https:\\/\\/www.freetogame.com\\/g\\/452\\/Call-of-Duty-Warzone-1.jpg","https:\\/\\/www.freetogame.com\\/g\\/452\\/Call-of-Duty-Warzone-2.jpg","https:\\/\\/www.freetogame.com\\/g\\/452\\/Call-of-Duty-Warzone-3.jpg"]', '{"os":"Windows 7 64-Bit (SP1) or Windows 10 64-Bit","processor":"Intel Core i3-4340 or AMD FX-6300","memory":"8GB RAM","graphics":"NVIDIA GeForce GTX 670 \\/ GeForce GTX 1650 or Radeon HD 7950","storage":"175GB HD space"}', '1', '2000-01-01 00:00:00', '2000-01-01 00:00:00'),
('455', 'Eternal Fury', 'https://www.freetogame.com/g/455/thumbnail.jpg', 'A free-to-play ARPG from R2 Games!', NULL, 'ARPG', 'Web Browser', 'R2 Games', 'R2 Games', NULL, 'https://www.freetogame.com/open/eternal-fury', 'https://www.freetogame.com/eternal-fury', NULL, NULL, '10', '2000-01-01 00:00:00', NULL),
('508', 'Enlisted', 'https://www.freetogame.com/g/508/thumbnail.jpg', 'Get ready to command your own World War II military squad in Gaijin and Darkflow Software''s MMO squad-based shooter Enlisted.', NULL, 'Shooter', 'PC (Windows)', 'Gaijin Entertainment', 'Darkflow Software', '2021-04-08', 'https://www.freetogame.com/open/enlisted', 'https://www.freetogame.com/enlisted', NULL, NULL, '4', '2000-01-01 00:00:00', NULL),
('516', 'PUBG: BATTLEGROUNDS', 'https://www.freetogame.com/g/516/thumbnail.jpg', 'Get into the action in one of the longest running battle royale games PUBG Battlegrounds.', NULL, 'Shooter', 'PC (Windows)', 'KRAFTON, Inc.', 'KRAFTON, Inc.', '2022-01-12', 'https://www.freetogame.com/open/pubg', 'https://www.freetogame.com/pubg', NULL, NULL, '3', '2000-01-01 00:00:00', NULL),
('540', 'Overwatch', 'https://www.freetogame.com/g/540/thumbnail.jpg', 'A hero-focused first-person team shooter from Blizzard Entertainment.', NULL, 'Shooter', 'PC (Windows)', 'Activision Blizzard', 'Blizzard Entertainment', '2022-10-04', 'https://www.freetogame.com/open/overwatch', 'https://www.freetogame.com/overwatch', NULL, NULL, '2', '2000-01-01 00:00:00', NULL),
('582', 'Tarisland', 'https://www.freetogame.com/g/582/thumbnail.jpg', 'A cross-platform MMORPG set in a vast fantasy world', NULL, 'MMORPG', 'PC (Windows)', 'Level Infinite', 'Level Infinite', '2024-06-21', 'https://www.freetogame.com/open/tarisland', 'https://www.freetogame.com/tarisland', NULL, NULL, '7', '2000-01-01 00:00:00', NULL),
('604', 'FragPunk', 'https://www.freetogame.com/g/604/thumbnail.jpg', 'A free-to-play 5v5 hero shooter that uses cards to modify matches.', NULL, 'Shooter', 'PC (Windows)', 'Bad Guitar Studio', 'Bad Guitar Studio', '2025-03-06', 'https://www.freetogame.com/open/fragpunk', 'https://www.freetogame.com/fragpunk', NULL, NULL, '6', '2000-01-01 00:00:00', NULL),
('644', 'World of Tanks: HEAT', 'https://www.freetogame.com/g/644/thumbnail.jpg', 'A free-to-play hero-based shooter set in the World of Tanks universe.', NULL, 'Shooter', 'PC (Windows)', 'Wargaming Group Limited', 'Wargaming Group Limited', '2026-05-26', 'https://www.freetogame.com/open/world-of-tanks-heat', 'https://www.freetogame.com/world-of-tanks-heat', NULL, NULL, '5', '2000-01-01 00:00:00', NULL);

INSERT INTO `items` (`id`, `seller_id`, `category_id`, `title`, `slug`, `game_id`, `description`, `price`, `stock`, `delivery_type`, `delivery_content`, `file_path`, `file_name`, `image`, `status`, `views`, `sales_count`, `created_at`, `updated_at`) VALUES
('1', '2', '1', 'Warzone Account – Level 155, 40+ Operator Skins', 'warzone-account-level-155-40-operator-skins-1', '452', 'Hand-levelled account, never shared or botted. You receive the login email and password plus the recovery details. Change the password right after purchase.

Includes:
- All listed heroes / skins
- Unbound email you can change
- Region: Global

After-sales support for 7 days via the order page.', '2499', '1', 'code', 'Login: player6305@mail.test
Password: Lv-258176!
Recovery code: 7468-1791

Please change the password after logging in.', NULL, NULL, 'seed-01.jpg', 'active', '3513', '1', '2026-10-13 16:48:18', NULL),
('2', '2', '1', 'Overwatch Account – Diamond Rank, 25 Legendary Skins', 'overwatch-account-diamond-rank-25-legendary-skins-2', '540', 'Hand-levelled account, never shared or botted. You receive the login email and password plus the recovery details. Change the password right after purchase.

Includes:
- All listed heroes / skins
- Unbound email you can change
- Region: Global

After-sales support for 7 days via the order page.', '3299', '1', 'code', 'Login: player2542@mail.test
Password: Lv-483452!
Recovery code: 1950-9313

Please change the password after logging in.', NULL, NULL, 'seed-02.jpg', 'active', '303', '0', '2026-11-11 15:48:18', NULL),
('3', '3', '1', 'Tarisland Account – Lv 60 Paladin + Rare Mount', 'tarisland-account-lv-60-paladin-rare-mount-3', '582', 'Hand-levelled account, never shared or botted. You receive the login email and password plus the recovery details. Change the password right after purchase.

Includes:
- All listed heroes / skins
- Unbound email you can change
- Region: Global

After-sales support for 7 days via the order page.', '1099', '3', 'code', 'Login: player8104@mail.test
Password: Lv-538485!
Recovery code: 2144-4943

Please change the password after logging in.', NULL, NULL, 'seed-03.jpg', 'active', '2407', '0', '2026-10-20 14:48:18', NULL),
('4', '4', '1', 'PUBG Account – 2018 OG, 80 Skins + Rare Parachutes', 'pubg-account-2018-og-80-skins-rare-parachutes-4', '516', 'Hand-levelled account, never shared or botted. You receive the login email and password plus the recovery details. Change the password right after purchase.

Includes:
- All listed heroes / skins
- Unbound email you can change
- Region: Global

After-sales support for 7 days via the order page.', '4999', '1', 'code', 'Login: player1968@mail.test
Password: Lv-967017!
Recovery code: 3028-4657

Please change the password after logging in.', NULL, NULL, 'seed-04.jpg', 'active', '2513', '0', '2026-10-10 13:48:18', NULL),
('5', '3', '2', 'Enlisted Premium Squad Bundle Code', 'enlisted-premium-squad-bundle-code-5', '508', 'Delivered as a redeemable in-game code. Redeem it in the game''s Store → Redeem Code screen. Works on all platforms where the game is available.

Codes are tested before listing. If anything is wrong, contact me before confirming the order.', '650', '15', 'code', 'CODE: 3DQC-JU4K-HVMG-NZGE', NULL, NULL, 'seed-05.jpg', 'active', '2685', '1', '2026-11-03 12:48:18', NULL),
('6', '4', '2', 'World of Tanks: HEAT – Hero Starter Pack Code', 'world-of-tanks-heat-hero-starter-pack-code-6', '644', 'Delivered as a redeemable in-game code. Redeem it in the game''s Store → Redeem Code screen. Works on all platforms where the game is available.

Codes are tested before listing. If anything is wrong, contact me before confirming the order.', '420', '40', 'code', 'CODE: 95W7-7ZVR-MRFV-9X6U', NULL, NULL, 'seed-06.jpg', 'active', '633', '1', '2026-10-15 11:48:18', NULL),
('7', '5', '2', 'FragPunk Card Bundle – 5 Rare Shard Cards', 'fragpunk-card-bundle-5-rare-shard-cards-7', '604', 'Delivered as a redeemable in-game code. Redeem it in the game''s Store → Redeem Code screen. Works on all platforms where the game is available.

Codes are tested before listing. If anything is wrong, contact me before confirming the order.', '350', '60', 'code', 'CODE: 4LXK-94CE-WXY9-7EFT', NULL, NULL, 'seed-07.jpg', 'active', '3005', '1', '2026-11-12 10:48:18', NULL),
('8', '2', '2', 'Overwatch Premium Battle Pass Code', 'overwatch-premium-battle-pass-code-8', '540', 'Delivered as a redeemable in-game code. Redeem it in the game''s Store → Redeem Code screen. Works on all platforms where the game is available.

Codes are tested before listing. If anything is wrong, contact me before confirming the order.', '549', '25', 'code', 'CODE: DV6U-2YB7-YLH9-DPUJ', NULL, NULL, 'seed-08.jpg', 'active', '1779', '0', '2026-10-22 09:48:18', NULL),
('9', '6', '3', '₱500 GameHub Store Gift Card', '500-gamehub-store-gift-card-9', NULL, 'Official digital code delivered instantly after payment. Redeem it on the store''s website or app. No expiry date. Region-free unless noted.

The code appears on your order page right after checkout.', '519', '50', 'code', 'CODE: 9FL6-3TJ5-T4Y2-QKFM', NULL, NULL, 'seed-09.jpg', 'active', '1100', '1', '2026-11-02 08:48:18', NULL),
('10', '6', '3', '₱1,000 GameHub Store Gift Card', '1-000-gamehub-store-gift-card-10', NULL, 'Official digital code delivered instantly after payment. Redeem it on the store''s website or app. No expiry date. Region-free unless noted.

The code appears on your order page right after checkout.', '1029', '50', 'code', 'CODE: A9MS-UAK4-ZWJD-7333', NULL, NULL, 'seed-10.jpg', 'active', '574', '1', '2026-10-17 07:48:18', NULL),
('11', '6', '3', 'PlayCloud 3-Month Subscription Code', 'playcloud-3-month-subscription-code-11', NULL, 'Official digital code delivered instantly after payment. Redeem it on the store''s website or app. No expiry date. Region-free unless noted.

The code appears on your order page right after checkout.', '1499', '20', 'code', 'CODE: 3DNE-P6LH-XDGA-KGZB', NULL, NULL, 'seed-11.jpg', 'active', '3731', '0', '2026-11-03 06:48:18', NULL),
('12', '2', '4', 'Warzone 2,400 COD Points Top-up', 'warzone-2-400-cod-points-top-up-12', '452', 'Top-up code for the global server. Redeem in-game under Shop → Top-up → Enter Code. Crystals arrive immediately.

Bulk orders welcome – stock refreshed daily.', '999', '99', 'code', 'CODE: 2KSY-Z8HH-9788-VFKG', NULL, NULL, 'seed-12.jpg', 'active', '3182', '1', '2026-10-31 05:48:18', NULL),
('13', '5', '4', 'Forge of Empires 1,000 Diamonds', 'forge-of-empires-1-000-diamonds-13', '345', 'Top-up code for the global server. Redeem in-game under Shop → Top-up → Enter Code. Crystals arrive immediately.

Bulk orders welcome – stock refreshed daily.', '499', '80', 'code', 'CODE: 8LBP-ZKBV-FSZL-YQXQ', NULL, NULL, 'seed-13.jpg', 'active', '3451', '1', '2026-11-01 04:48:18', NULL),
('14', '6', '4', 'Eternal Fury 5,000 Gold Top-up', 'eternal-fury-5-000-gold-top-up-14', '455', 'Top-up code for the global server. Redeem in-game under Shop → Top-up → Enter Code. Crystals arrive immediately.

Bulk orders welcome – stock refreshed daily.', '279', '70', 'code', 'CODE: 3QN9-YBBT-8SNY-6YZF', NULL, NULL, 'seed-14.jpg', 'active', '568', '0', '2026-11-02 03:48:18', NULL),
('15', '7', '5', 'Cyberpunk UI Kit – 300 Game HUD Elements (PNG + PSD)', 'cyberpunk-ui-kit-300-game-hud-elements-png-psd-15', NULL, 'High-quality asset pack for games, streams and design projects. Commercial licence included – use it in unlimited personal and commercial projects.

You get a ZIP file download right after purchase.', '799', '999', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'seed-15.bin', 'cyberpunk-ui-kit-300-game-hud-elements-p.zip', 'seed-15.jpg', 'active', '955', '1', '2026-10-26 02:48:19', NULL),
('16', '7', '5', 'Pixel Art Character Pack – 48 Animated Sprites', 'pixel-art-character-pack-48-animated-sprites-16', NULL, 'High-quality asset pack for games, streams and design projects. Commercial licence included – use it in unlimited personal and commercial projects.

You get a ZIP file download right after purchase.', '449', '999', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'seed-16.bin', 'pixel-art-character-pack-48-animated-spr.zip', 'seed-16.jpg', 'active', '2126', '0', '2026-10-08 01:48:19', NULL),
('17', '7', '5', 'Fantasy Map Brushes for Procreate & Photoshop', 'fantasy-map-brushes-for-procreate-photoshop-17', NULL, 'High-quality asset pack for games, streams and design projects. Commercial licence included – use it in unlimited personal and commercial projects.

You get a ZIP file download right after purchase.', '349', '999', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'seed-17.bin', 'fantasy-map-brushes-for-procreate-photos.zip', 'seed-17.jpg', 'active', '2113', '0', '2026-10-25 00:48:19', NULL),
('18', '4', '5', 'Stream Overlay Pack – Neon Theme (OBS Ready)', 'stream-overlay-pack-neon-theme-obs-ready-18', NULL, 'High-quality asset pack for games, streams and design projects. Commercial licence included – use it in unlimited personal and commercial projects.

You get a ZIP file download right after purchase.', '599', '999', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'seed-18.bin', 'stream-overlay-pack-neon-theme-obs-ready.zip', 'seed-18.jpg', 'active', '3568', '0', '2026-11-08 23:48:19', NULL),
('19', '8', '6', 'Discord Moderation Bot – Source Code (Node.js)', 'discord-moderation-bot-source-code-node-js-19', NULL, 'Clean, commented source code with a README and setup guide. Free updates for this version.

Download the ZIP right after purchase. Licence: use in your own projects, no reselling of the source.', '1299', '999', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'seed-19.bin', 'discord-moderation-bot-source-code-node-.zip', 'seed-19.jpg', 'active', '3354', '1', '2026-11-03 22:48:19', NULL),
('20', '8', '6', '2D Platformer Starter Template (Godot 4)', '2d-platformer-starter-template-godot-4-20', NULL, 'Clean, commented source code with a README and setup guide. Free updates for this version.

Download the ZIP right after purchase. Licence: use in your own projects, no reselling of the source.', '999', '999', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'seed-20.bin', '2d-platformer-starter-template-godot-4.zip', 'seed-20.jpg', 'active', '3791', '0', '2026-11-04 21:48:19', NULL),
('21', '8', '6', 'Inventory System Script for Unity', 'inventory-system-script-for-unity-21', NULL, 'Clean, commented source code with a README and setup guide. Free updates for this version.

Download the ZIP right after purchase. Licence: use in your own projects, no reselling of the source.', '649', '999', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'seed-21.bin', 'inventory-system-script-for-unity.zip', 'seed-21.jpg', 'active', '3382', '0', '2026-10-06 20:48:19', NULL),
('22', '9', '7', 'Epic Battle Music Pack – 12 Loopable Tracks', 'epic-battle-music-pack-12-loopable-tracks-22', NULL, 'Royalty-free audio pack in WAV and MP3. Safe for YouTube, Twitch and commercial games.

Instant ZIP download after purchase.', '749', '999', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'seed-22.bin', 'epic-battle-music-pack-12-loopable-track.zip', 'seed-22.jpg', 'active', '505', '1', '2026-10-21 19:48:19', NULL),
('23', '9', '7', 'Retro 8-bit SFX Library – 500 Sounds', 'retro-8-bit-sfx-library-500-sounds-23', NULL, 'Royalty-free audio pack in WAV and MP3. Safe for YouTube, Twitch and commercial games.

Instant ZIP download after purchase.', '379', '999', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'seed-23.bin', 'retro-8-bit-sfx-library-500-sounds.zip', 'seed-23.jpg', 'active', '1794', '0', '2026-11-10 18:48:19', NULL),
('24', '9', '7', 'Lo-fi Chill Beats for Streams – Royalty Free', 'lo-fi-chill-beats-for-streams-royalty-free-24', NULL, 'Royalty-free audio pack in WAV and MP3. Safe for YouTube, Twitch and commercial games.

Instant ZIP download after purchase.', '499', '999', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'seed-24.bin', 'lo-fi-chill-beats-for-streams-royalty-fr.zip', 'seed-24.jpg', 'active', '846', '0', '2026-11-07 17:48:19', NULL);

INSERT INTO `orders` (`id`, `order_number`, `buyer_id`, `total`, `payment_method`, `payment_reference`, `status`, `created_at`) VALUES
('1', 'LV-261104-01000', '10', '1518', 'gcash', 'GCASH-261104-CFCD2084', 'paid', '2026-11-04 14:48:19'),
('2', 'LV-261107-01001', '11', '2499', 'maya', 'MAYA-261107-C4CA4238', 'paid', '2026-11-07 14:48:19'),
('3', 'LV-261109-01002', '12', '1548', 'card', 'CARD-261109-C81E728D', 'paid', '2026-11-09 14:48:19'),
('4', 'LV-261111-01003', '11', '1070', 'gcash', 'GCASH-261111-ECCBC87E', 'paid', '2026-11-11 14:48:19'),
('5', 'LV-261113-01004', '12', '350', 'maya', 'MAYA-261113-A87FF679', 'paid', '2026-11-13 14:48:19'),
('6', 'LV-261114-01005', '10', '1299', 'card', 'CARD-261114-E4DA3B7F', 'paid', '2026-11-14 14:48:19'),
('7', 'LV-261115-01006', '11', '1528', 'gcash', 'GCASH-261115-1679091C', 'paid', '2026-11-15 14:48:19');

INSERT INTO `order_items` (`id`, `order_id`, `item_id`, `seller_id`, `title`, `price`, `quantity`, `delivery_type`, `delivered_content`, `status`, `confirmed_at`, `created_at`) VALUES
('1', '1', '12', '2', 'Warzone 2,400 COD Points Top-up', '999', '1', 'code', 'CODE: 2KSY-Z8HH-9788-VFKG', 'confirmed', '2026-11-04 14:48:19', '2026-11-04 14:48:19'),
('2', '1', '9', '6', '₱500 GameHub Store Gift Card', '519', '1', 'code', 'CODE: 9FL6-3TJ5-T4Y2-QKFM', 'confirmed', '2026-11-04 14:48:19', '2026-11-04 14:48:19'),
('3', '2', '1', '2', 'Warzone Account – Level 155, 40+ Operator Skins', '2499', '1', 'code', 'Login: player6305@mail.test
Password: Lv-258176!
Recovery code: 7468-1791

Please change the password after logging in.', 'confirmed', '2026-11-07 14:48:19', '2026-11-07 14:48:19'),
('4', '3', '15', '7', 'Cyberpunk UI Kit – 300 Game HUD Elements (PNG + PSD)', '799', '1', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'confirmed', '2026-11-09 14:48:19', '2026-11-09 14:48:19'),
('5', '3', '22', '9', 'Epic Battle Music Pack – 12 Loopable Tracks', '749', '1', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'confirmed', '2026-11-09 14:48:19', '2026-11-09 14:48:19'),
('6', '4', '5', '3', 'Enlisted Premium Squad Bundle Code', '650', '1', 'code', 'CODE: 3DQC-JU4K-HVMG-NZGE', 'confirmed', '2026-11-11 14:48:19', '2026-11-11 14:48:19'),
('7', '4', '6', '4', 'World of Tanks: HEAT – Hero Starter Pack Code', '420', '1', 'code', 'CODE: 95W7-7ZVR-MRFV-9X6U', 'confirmed', '2026-11-11 14:48:19', '2026-11-11 14:48:19'),
('8', '5', '7', '5', 'FragPunk Card Bundle – 5 Rare Shard Cards', '350', '1', 'code', 'CODE: 4LXK-94CE-WXY9-7EFT', 'confirmed', '2026-11-13 14:48:19', '2026-11-13 14:48:19'),
('9', '6', '19', '8', 'Discord Moderation Bot – Source Code (Node.js)', '1299', '1', 'file', 'Thank you for your purchase! Download the ZIP above. Need help? Message me on the order page.', 'confirmed', '2026-11-14 14:48:19', '2026-11-14 14:48:19'),
('10', '7', '13', '5', 'Forge of Empires 1,000 Diamonds', '499', '1', 'code', 'CODE: 8LBP-ZKBV-FSZL-YQXQ', 'delivered', NULL, '2026-11-15 14:48:19'),
('11', '7', '10', '6', '₱1,000 GameHub Store Gift Card', '1029', '1', 'code', 'CODE: A9MS-UAK4-ZWJD-7333', 'delivered', NULL, '2026-11-15 14:48:19');

INSERT INTO `reviews` (`id`, `order_item_id`, `item_id`, `buyer_id`, `seller_id`, `rating`, `comment`, `created_at`) VALUES
('1', '1', '12', '10', '2', '5', 'Code worked instantly, thanks!', '2026-11-04 14:48:19'),
('2', '2', '9', '10', '6', '4', 'Fast, legit gift card.', '2026-11-04 14:48:19'),
('3', '3', '1', '11', '2', '5', 'Account exactly as described. Changed password with no issues.', '2026-11-07 14:48:19'),
('4', '4', '15', '12', '7', '5', 'Beautiful UI kit, saved me weeks.', '2026-11-09 14:48:19'),
('5', '5', '22', '12', '9', '4', 'Great tracks for my stream.', '2026-11-09 14:48:19'),
('6', '6', '5', '11', '3', '4', 'Sword bundle redeemed fine.', '2026-11-11 14:48:19'),
('7', '7', '6', '11', '4', '5', 'Cars are awesome.', '2026-11-11 14:48:19'),
('8', '8', '7', '12', '5', '5', 'Got a legendary pet from the eggs!', '2026-11-13 14:48:19'),
('9', '9', '19', '10', '8', '4', 'Bot works, README was clear.', '2026-11-14 14:48:19');

SET FOREIGN_KEY_CHECKS = 1;
