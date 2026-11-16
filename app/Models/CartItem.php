<?php

namespace App\Models;

use Core\Model;

class CartItem extends Model
{
    protected static string $table = 'cart_items';
    protected static array $fillable = ['user_id', 'item_id', 'quantity'];

    /** Cart rows joined with the live item data (price, stock, seller). */
    public static function forUser(int $userId): array
    {
        return self::db()->fetchAll(
            'SELECT ci.id AS cart_id, ci.quantity, i.*, u.username AS seller_name
             FROM cart_items ci
             JOIN items i ON i.id = ci.item_id
             JOIN users u ON u.id = i.seller_id
             WHERE ci.user_id = ?
             ORDER BY ci.id',
            [$userId]
        );
    }

    public static function add(int $userId, int $itemId, int $quantity = 1): void
    {
        $existing = self::db()->fetch('SELECT * FROM cart_items WHERE user_id = ? AND item_id = ?', [$userId, $itemId]);

        if ($existing) {
            self::update((int) $existing['id'], ['quantity' => $existing['quantity'] + $quantity]);
        } else {
            self::create(['user_id' => $userId, 'item_id' => $itemId, 'quantity' => $quantity]);
        }
    }

    public static function countFor(int $userId): int
    {
        return (int) self::db()->fetchValue('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?', [$userId]);
    }

    public static function clear(int $userId): void
    {
        self::db()->delete('cart_items', 'user_id = ?', [$userId]);
    }

    public static function removeFor(int $userId, int $cartId): void
    {
        self::db()->delete('cart_items', 'id = ? AND user_id = ?', [$cartId, $userId]);
    }
}
