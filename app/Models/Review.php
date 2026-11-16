<?php

namespace App\Models;

use Core\Model;

class Review extends Model
{
    protected static string $table = 'reviews';
    protected static array $fillable = ['order_item_id', 'item_id', 'buyer_id', 'seller_id', 'rating', 'comment'];

    public static function forItem(int $itemId, int $limit = 10): array
    {
        $limit = (int) $limit;
        return self::db()->fetchAll(
            "SELECT r.*, u.username FROM reviews r JOIN users u ON u.id = r.buyer_id
             WHERE r.item_id = ? ORDER BY r.created_at DESC LIMIT $limit",
            [$itemId]
        );
    }

    public static function forSeller(int $sellerId, int $limit = 10): array
    {
        $limit = (int) $limit;
        return self::db()->fetchAll(
            "SELECT r.*, u.username, oi.title FROM reviews r
             JOIN users u ON u.id = r.buyer_id
             JOIN order_items oi ON oi.id = r.order_item_id
             WHERE r.seller_id = ? ORDER BY r.created_at DESC LIMIT $limit",
            [$sellerId]
        );
    }
}
