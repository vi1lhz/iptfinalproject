<?php

namespace App\Models;

use Core\Model;

/** A game from the FreeToGame catalog (see App\Services\GameCatalog). */
class Game extends Model
{
    protected static string $table = 'games';

    /**
     * Games with the number of active LootVault listings for each.
     * $filters: q, genre, platform, with_listings (bool), limit
     */
    public static function browse(array $filters = []): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(g.title LIKE ? OR g.publisher LIKE ?)';
            array_push($params, '%' . $filters['q'] . '%', '%' . $filters['q'] . '%');
        }
        if (!empty($filters['genre'])) {
            $where[] = 'g.genre = ?';
            $params[] = $filters['genre'];
        }
        if (!empty($filters['platform'])) {
            $where[] = 'g.platform LIKE ?';
            $params[] = $filters['platform'] === 'browser' ? '%Browser%' : '%Windows%';
        }

        $having = !empty($filters['with_listings']) ? 'WHERE listing_count > 0' : '';
        $limit = isset($filters['limit']) ? 'LIMIT ' . (int) $filters['limit'] : '';
        $whereSql = implode(' AND ', $where);

        return self::db()->fetchAll(
            "SELECT * FROM (
                SELECT g.*, (SELECT COUNT(*) FROM items i WHERE i.game_id = g.id AND i.status = 'active') AS listing_count
                FROM games g WHERE $whereSql
             ) t $having
             ORDER BY listing_count DESC, popularity_rank ASC, title ASC $limit",
            $params
        );
    }

    public static function genres(): array
    {
        return array_column(
            self::db()->fetchAll("SELECT genre, COUNT(*) AS n FROM games WHERE genre IS NOT NULL AND genre <> '' GROUP BY genre ORDER BY n DESC, genre ASC"),
            'n',
            'genre'
        );
    }

    /** Short list for the seller's "Game" dropdown. */
    public static function options(): array
    {
        return self::db()->fetchAll('SELECT id, title, genre FROM games ORDER BY title ASC');
    }
}
