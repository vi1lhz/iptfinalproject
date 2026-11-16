<?php

namespace App\Models;

use Core\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static array $fillable = ['username', 'email', 'password', 'role', 'bio', 'balance', 'is_verified', 'is_banned'];

    public static function register(array $data): int
    {
        return self::create([
            'username' => $data['username'],
            'email'    => strtolower($data['email']),
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role'     => 'user',
        ]);
    }

    /** Public seller profile numbers: listings, sales, average rating. */
    public static function sellerStats(int $userId): array
    {
        $db = self::db();
        return [
            'listings' => (int) $db->fetchValue("SELECT COUNT(*) FROM items WHERE seller_id = ? AND status = 'active'", [$userId]),
            'sales'    => (int) $db->fetchValue('SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE seller_id = ?', [$userId]),
            'rating'   => round((float) $db->fetchValue('SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE seller_id = ?', [$userId]), 1),
            'reviews'  => (int) $db->fetchValue('SELECT COUNT(*) FROM reviews WHERE seller_id = ?', [$userId]),
        ];
    }

    public static function addBalance(int $userId, float $amount): void
    {
        self::db()->query('UPDATE users SET balance = balance + ? WHERE id = ?', [$amount, $userId]);
    }
}
