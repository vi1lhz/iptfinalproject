<?php

namespace App\Models;

use Core\Database;
use Core\Model;

class Order extends Model
{
    protected static string $table = 'orders';
    protected static array $fillable = ['order_number', 'buyer_id', 'total', 'payment_method', 'payment_reference', 'status'];

    /**
     * Turns the buyer's cart into an order inside ONE database transaction:
     * create order → copy each cart row into order_items → reduce stock → empty cart.
     * If anything fails (e.g. an item sold out meanwhile) everything is rolled back.
     */
    public static function placeFromCart(int $buyerId, string $paymentMethod, ?string $paymentReference = null): array
    {
        return self::db()->transaction(function (Database $db) use ($buyerId, $paymentMethod, $paymentReference) {
            $cart = CartItem::forUser($buyerId);
            if (!$cart) {
                throw new \RuntimeException('Your cart is empty.');
            }

            $total = 0;
            foreach ($cart as $row) {
                if ($row['status'] !== 'active') {
                    throw new \RuntimeException("\"{$row['title']}\" is no longer available.");
                }
                if ((int) $row['seller_id'] === $buyerId) {
                    throw new \RuntimeException("You can't buy your own listing \"{$row['title']}\".");
                }
                if ($row['quantity'] > $row['stock']) {
                    throw new \RuntimeException("Only {$row['stock']} left of \"{$row['title']}\".");
                }
                $total += $row['price'] * $row['quantity'];
            }

            $now = date('Y-m-d H:i:s');
            $orderNumber = 'LV-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

            $orderId = $db->insert('orders', [
                'order_number'     => $orderNumber,
                'buyer_id'         => $buyerId,
                'total'            => round($total, 2),
                'payment_method'   => $paymentMethod,
                'payment_reference' => $paymentReference,
                'status'           => 'paid',
                'created_at'       => $now,
            ]);

            foreach ($cart as $row) {
                // Instant digital delivery: the code / file reference is attached right away.
                $db->insert('order_items', [
                    'order_id'          => $orderId,
                    'item_id'           => $row['id'],
                    'seller_id'         => $row['seller_id'],
                    'title'             => $row['title'],
                    'price'             => $row['price'],
                    'quantity'          => $row['quantity'],
                    'delivery_type'     => $row['delivery_type'],
                    'delivered_content' => $row['delivery_content'],
                    'status'            => 'delivered',
                    'created_at'        => $now,
                ]);

                // "stock >= ?" guard prevents overselling if two buyers check out at once.
                $updated = $db->query(
                    'UPDATE items SET stock = stock - ?, sales_count = sales_count + ? WHERE id = ? AND stock >= ?',
                    [$row['quantity'], $row['quantity'], $row['id'], $row['quantity']]
                )->rowCount();
                if ($updated === 0) {
                    throw new \RuntimeException("\"{$row['title']}\" just sold out.");
                }
                $db->query("UPDATE items SET status = 'sold_out' WHERE id = ? AND stock <= 0", [$row['id']]);
            }

            CartItem::clear($buyerId);

            return ['id' => $orderId, 'order_number' => $orderNumber, 'total' => $total];
        });
    }

    public static function forBuyer(int $buyerId): array
    {
        return self::db()->fetchAll(
            'SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
             FROM orders o WHERE o.buyer_id = ? ORDER BY o.created_at DESC, o.id DESC',
            [$buyerId]
        );
    }

    public static function findByNumber(string $number): ?array
    {
        return self::db()->fetch(
            'SELECT o.*, u.username AS buyer_name FROM orders o JOIN users u ON u.id = o.buyer_id WHERE o.order_number = ?',
            [$number]
        );
    }

    public static function allForAdmin(): array
    {
        return self::db()->fetchAll(
            'SELECT o.*, u.username AS buyer_name, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
             FROM orders o JOIN users u ON u.id = o.buyer_id ORDER BY o.created_at DESC, o.id DESC'
        );
    }

    public static function revenue(): float
    {
        return (float) self::db()->fetchValue("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status <> 'refunded'");
    }
}
