<?php

namespace App\Models;

use Core\Model;

class OrderItem extends Model
{
    protected static string $table = 'order_items';
    protected static array $fillable = ['status', 'confirmed_at'];

    public static function forOrder(int $orderId): array
    {
        return self::db()->fetchAll(
            'SELECT oi.*, u.username AS seller_name, i.slug, i.image, i.file_name,
                    (SELECT r.rating FROM reviews r WHERE r.order_item_id = oi.id) AS my_rating
             FROM order_items oi
             JOIN users u ON u.id = oi.seller_id
             LEFT JOIN items i ON i.id = oi.item_id
             WHERE oi.order_id = ? ORDER BY oi.id',
            [$orderId]
        );
    }

    /** Line item + its order, only if it belongs to the given buyer (ownership check). */
    public static function findForBuyer(int $id, int $buyerId): ?array
    {
        return self::db()->fetch(
            'SELECT oi.*, o.buyer_id, o.order_number, i.file_path, i.file_name
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             LEFT JOIN items i ON i.id = oi.item_id
             WHERE oi.id = ? AND o.buyer_id = ?',
            [$id, $buyerId]
        );
    }

    /**
     * Buyer confirms the item works → it is marked confirmed and the seller is paid
     * (price × qty minus the platform fee). Funds are held until then, like an escrow.
     */
    public static function confirm(array $line, float $feeRate): void
    {
        self::db()->transaction(function () use ($line, $feeRate) {
            $changed = self::db()->query(
                "UPDATE order_items SET status = 'confirmed', confirmed_at = ? WHERE id = ? AND status = 'delivered'",
                [date('Y-m-d H:i:s'), $line['id']]
            )->rowCount();

            if ($changed) {
                $earning = round($line['price'] * $line['quantity'] * (1 - $feeRate), 2);
                User::addBalance((int) $line['seller_id'], $earning);
            }
        });
    }

    /** Recent sales for the seller dashboard. */
    public static function salesFor(int $sellerId, int $limit = 20): array
    {
        $limit = (int) $limit;
        return self::db()->fetchAll(
            "SELECT oi.*, o.order_number, u.username AS buyer_name
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             JOIN users u ON u.id = o.buyer_id
             WHERE oi.seller_id = ? ORDER BY oi.created_at DESC, oi.id DESC LIMIT $limit",
            [$sellerId]
        );
    }

    public static function sellerTotals(int $sellerId): array
    {
        $db = self::db();
        return [
            'gross'   => (float) $db->fetchValue('SELECT COALESCE(SUM(price * quantity), 0) FROM order_items WHERE seller_id = ?', [$sellerId]),
            'pending' => (float) $db->fetchValue("SELECT COALESCE(SUM(price * quantity), 0) FROM order_items WHERE seller_id = ? AND status = 'delivered'", [$sellerId]),
            'orders'  => (int) $db->fetchValue('SELECT COUNT(*) FROM order_items WHERE seller_id = ?', [$sellerId]),
        ];
    }
}
