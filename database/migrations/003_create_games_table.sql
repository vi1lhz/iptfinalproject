-- Games catalog, filled from the FreeToGame API (https://www.freetogame.com/api).
-- id is FreeToGame's own game id (not auto-increment) so re-syncing updates rows instead of duplicating them.
CREATE TABLE games (
    id {FK} NOT NULL PRIMARY KEY,
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
){ENGINE};

CREATE INDEX idx_games_genre ON games(genre);
