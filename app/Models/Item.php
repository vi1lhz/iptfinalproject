<?php

namespace App\Models;

use Core\Model;

class Item extends Model
{
    protected static string $table = 'items';
    protected static array $fillable = [
        'seller_id', 'category_id', 'title', 'slug', 'game_id', 'description', 'price', 'stock',
        'delivery_type', 'delivery_content', 'file_path', 'file_name', 'image', 'status', 'updated_at',
    ];

    /** Common SELECT with seller name, category and average rating. */
    private const BASE_SELECT = "
        SELECT i.*, u.username AS seller_name, u.is_verified AS seller_verified,
               c.name AS category_name, c.slug AS category_slug,
               g.title AS game, g.thumbnail AS game_thumbnail, g.genre AS game_genre,
               (SELECT AVG(r.rating) FROM reviews r WHERE r.item_id = i.id) AS avg_rating,
               (SELECT COUNT(*) FROM reviews r WHERE r.item_id = i.id) AS review_count
        FROM items i
        JOIN users u ON u.id = i.seller_id
        JOIN categories c ON c.id = i.category_id
        LEFT JOIN games g ON g.id = i.game_id";

    /**
     * Browse / search with filters. Returns ['items' => [...], 'total' => n].
     * $filters: q, category (slug), sort, min, max, page, per_page
     */
    public static function browse(array $filters): array
    {
        $where = ["i.status = 'active'", 'u.is_banned = 0'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(i.title LIKE ? OR g.title LIKE ? OR c.name LIKE ?)';
            $term = '%' . $filters['q'] . '%';
            array_push($params, $term, $term, $term);
        }
        if (!empty($filters['category'])) {
            $where[] = 'c.slug = ?';
            $params[] = $filters['category'];
        }
        if (isset($filters['min']) && is_numeric($filters['min'])) {
            $where[] = 'i.price >= ?';
            $params[] = (float) $filters['min'];
        }
        if (isset($filters['max']) && is_numeric($filters['max'])) {
            $where[] = 'i.price <= ?';
            $params[] = (float) $filters['max'];
        }
        if (!empty($filters['game'])) {
            $where[] = 'i.game_id = ?';
            $params[] = (int) $filters['game'];
        }
        if (!empty($filters['seller_id'])) {
            $where[] = 'i.seller_id = ?';
            $params[] = (int) $filters['seller_id'];
        }

        $order = match ($filters['sort'] ?? 'newest') {
            'price_asc'  => 'i.price ASC',
            'price_desc' => 'i.price DESC',
            'popular'    => 'i.sales_count DESC, i.views DESC',
            default      => 'i.created_at DESC, i.id DESC',
        };

        $perPage = max(1, (int) ($filters['per_page'] ?? 12));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $whereSql = implode(' AND ', $where);

        $total = (int) self::db()->fetchValue(
            "SELECT COUNT(*) FROM items i JOIN users u ON u.id = i.seller_id JOIN categories c ON c.id = i.category_id LEFT JOIN games g ON g.id = i.game_id WHERE $whereSql",
            $params
        );

        // LIMIT/OFFSET are cast to int and inlined (portable across MySQL and SQLite).
        $items = self::db()->fetchAll(self::BASE_SELECT . " WHERE $whereSql ORDER BY $order LIMIT $perPage OFFSET $offset", $params);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => (int) ceil($total / $perPage)];
    }

    public static function findDetailed(string $slug): ?array
    {
        return self::db()->fetch(self::BASE_SELECT . ' WHERE i.slug = ?', [$slug]);
    }

    public static function findWithSeller(int $id): ?array
    {
        return self::db()->fetch(self::BASE_SELECT . ' WHERE i.id = ?', [$id]);
    }

    public static function top(int $limit = 8): array
    {
        $limit = (int) $limit;
        return self::db()->fetchAll(self::BASE_SELECT . " WHERE i.status = 'active' AND u.is_banned = 0 ORDER BY i.sales_count DESC, i.views DESC LIMIT $limit");
    }

    public static function latest(int $limit = 8): array
    {
        $limit = (int) $limit;
        return self::db()->fetchAll(self::BASE_SELECT . " WHERE i.status = 'active' AND u.is_banned = 0 ORDER BY i.created_at DESC, i.id DESC LIMIT $limit");
    }

    public static function related(array $item, int $limit = 4): array
    {
        $limit = (int) $limit;
        return self::db()->fetchAll(
            self::BASE_SELECT . " WHERE i.status = 'active' AND i.category_id = ? AND i.id <> ? ORDER BY i.sales_count DESC LIMIT $limit",
            [$item['category_id'], $item['id']]
        );
    }

    public static function forSeller(int $sellerId): array
    {
        return self::db()->fetchAll(self::BASE_SELECT . " WHERE i.seller_id = ? AND i.status <> 'removed' ORDER BY i.created_at DESC, i.id DESC", [$sellerId]);
    }

    public static function allForAdmin(): array
    {
        return self::db()->fetchAll(self::BASE_SELECT . ' ORDER BY i.created_at DESC, i.id DESC');
    }

    public static function incrementViews(int $id): void
    {
        self::db()->query('UPDATE items SET views = views + 1 WHERE id = ?', [$id]);
    }

    /** "Starter Account #1" → "starter-account-1-x7k2" (unique, URL-safe) */
    public static function makeSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $title), '-'));
        return substr($slug ?: 'item', 0, 80) . '-' . substr(bin2hex(random_bytes(3)), 0, 5);
    }
}
