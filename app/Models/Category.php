<?php

namespace App\Models;

use Core\Model;

class Category extends Model
{
    protected static string $table = 'categories';
    protected static array $fillable = ['name', 'slug', 'icon', 'description'];

    /** Categories with the number of active listings in each. */
    public static function withCounts(): array
    {
        return self::db()->fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM items i WHERE i.category_id = c.id AND i.status = 'active') AS item_count
             FROM categories c ORDER BY c.id"
        );
    }
}
