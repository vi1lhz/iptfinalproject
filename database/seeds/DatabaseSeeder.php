<?php

namespace Database\Seeds;

use App\Services\GameCatalog;
use Core\Application;
use Core\Database;

/**
 * Fills the database with demo data: games (FreeToGame), users, categories, 24 listings, orders and reviews.
 * Run with:  php cli/migrate.php --fresh --seed
 */
class DatabaseSeeder
{
    public function __construct(private Database $db)
    {
    }

    public function run(): array
    {
        // Games first: the bundled snapshot guarantees the ids used below exist,
        // then a live sync adds/updates the full catalog when the API is reachable.
        $catalog = new GameCatalog($this->db);
        $catalog->importSnapshot();
        $sync = $catalog->sync(true);

        return $this->db->transaction(function () use ($sync) {
            $users = $this->users();
            $categories = $this->categories();
            $items = $this->items($users, $categories);
            $orders = $this->orders($users, $items);
            return ['users' => count($users), 'categories' => count($categories), 'items' => count($items), 'orders' => $orders,
                    'games' => $sync['count'], 'games_source' => $sync['source']];
        });
    }

    private function ago(int $days, int $hours = 0): string
    {
        return date('Y-m-d H:i:s', time() - $days * 86400 - $hours * 3600);
    }

    private function users(): array
    {
        $password = password_hash('password', PASSWORD_DEFAULT);
        $rows = [
            ['admin',       'admin@lootvault.test',  'admin', 1, 'LootVault staff account.'],
            ['NovaTrades',  'seller@lootvault.test', 'user',  1, 'Top-rated seller of accounts and top-ups since 2021. Fast delivery, 7-day support.'],
            ['ArcaneDeals', 'arcane@lootvault.test', 'user',  1, 'MMO veteran. Rare items and fresh accounts.'],
            ['PixelPirate', 'pixel@lootvault.test',  'user',  0, 'Racing and shooter game items + stream overlays.'],
            ['CozyCrafts',  'cozy@lootvault.test',   'user',  0, 'Cute pets, blocky worlds and pixel art.'],
            ['VaultKeys',   'keys@lootvault.test',   'user',  1, 'Official digital gift cards and subscription codes.'],
            ['LumenStudio', 'lumen@lootvault.test',  'user',  1, 'Indie art studio – UI kits, sprites and brushes.'],
            ['ByteForge',   'byte@lootvault.test',   'user',  0, 'Game dev templates and scripts.'],
            ['SoundSmith',  'sound@lootvault.test',  'user',  0, 'Composer making royalty-free music for creators.'],
            ['buyer',       'buyer@lootvault.test',  'user',  0, null],
            ['kaizen_ph',   'kaizen@lootvault.test', 'user',  0, null],
            ['mika_plays',  'mika@lootvault.test',   'user',  0, null],
        ];

        $ids = [];
        foreach ($rows as $i => [$username, $email, $role, $verified, $bio]) {
            $ids[$username] = $this->db->insert('users', [
                'username'    => $username,
                'email'       => $email,
                'password'    => $password,
                'role'        => $role,
                'bio'         => $bio,
                'balance'     => 0,
                'is_verified' => $verified,
                'is_banned'   => 0,
                'created_at'  => $this->ago(400 - $i * 25),
            ]);
        }
        return $ids;
    }

    private function categories(): array
    {
        $rows = [
            ['Game Accounts',   'game-accounts', 'user',    'Levelled and rare accounts for popular games.'],
            ['In-game Items',   'in-game-items', 'sword',   'Weapons, skins, pets, passes and bundles.'],
            ['Gift Cards',      'gift-cards',    'gift',    'Store credit and subscription codes.'],
            ['Top-ups',         'top-ups',       'coin',    'In-game currency delivered by code.'],
            ['Digital Art',     'digital-art',   'palette', 'UI kits, sprites, brushes and overlays.'],
            ['Software',        'software',      'code',    'Bots, scripts and game templates.'],
            ['Music & Audio',   'music-audio',   'music',   'Royalty-free tracks and sound effects.'],
        ];
        $ids = [];
        foreach ($rows as [$name, $slug, $icon, $description]) {
            $ids[$slug] = $this->db->insert('categories', [
                'name' => $name, 'slug' => $slug, 'icon' => $icon, 'description' => $description, 'created_at' => $this->ago(420),
            ]);
        }
        return $ids;
    }

    private function items(array $users, array $categories): array
    {
        $data = json_decode(file_get_contents(__DIR__ . '/items.json'), true);
        $filesDir = Application::$root . '/storage/files';
        $ids = [];

        foreach ($data as $row) {
            $filePath = $fileName = null;
            if ($row['delivery_type'] === 'file') {
                [$filePath, $fileName] = $this->makeDemoFile($filesDir, $row);
            }

            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $row['title']), '-'));
            $ids[$row['n']] = [
                'id' => $this->db->insert('items', [
                    'seller_id'        => $users[$row['seller']],
                    'category_id'      => $categories[$row['category']],
                    'title'            => $row['title'],
                    'slug'             => substr($slug, 0, 80) . '-' . $row['n'],
                    'game_id'          => $row['game_id'],
                    'description'      => $row['description'],
                    'price'            => $row['price'],
                    'stock'            => $row['stock'],
                    'delivery_type'    => $row['delivery_type'],
                    'delivery_content' => $row['delivery_content'],
                    'file_path'        => $filePath,
                    'file_name'        => $fileName,
                    'image'            => $row['image'],
                    'status'           => 'active',
                    'views'            => $row['views'],
                    'sales_count'      => $row['sales'],
                    'created_at'       => $this->ago($row['days_ago'], $row['n']),
                ]),
            ] + $row;
        }
        return $ids;
    }

    /** Creates a small ZIP so "file" listings have something real to download. */
    private function makeDemoFile(string $dir, array $row): array
    {
        $base = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $row['title']), '-'));
        $stored = 'seed-' . $row['n'] . '.bin';
        $readme = "LootVault demo download\n=======================\n\nItem: {$row['title']}\nSeller: {$row['seller']}\n\nThis is sample content generated by the database seeder.\n";

        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();
            $zip->open("$dir/$stored", \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFromString('README.txt', $readme);
            $zip->addFromString('LICENSE.txt', "Commercial licence – demo.\n");
            $zip->close();
            return [$stored, substr($base, 0, 40) . '.zip'];
        }

        file_put_contents("$dir/$stored", $readme);
        return [$stored, substr($base, 0, 40) . '.txt'];
    }

    /** A few completed orders + reviews so ratings and dashboards have data. */
    private function orders(array $users, array $items): int
    {
        $plan = [
            // buyer, [item numbers], days ago, confirmed?, [[rating, comment] per line item]
            ['buyer',      [12, 9],  12, true,  [[5, 'Code worked instantly, thanks!'], [4, 'Fast, legit gift card.']]],
            ['kaizen_ph',  [1],      9,  true,  [[5, 'Account exactly as described. Changed password with no issues.']]],
            ['mika_plays', [15, 22], 7,  true,  [[5, 'Beautiful UI kit, saved me weeks.'], [4, 'Great tracks for my stream.']]],
            ['kaizen_ph',  [5, 6],   5,  true,  [[4, 'Sword bundle redeemed fine.'], [5, 'Cars are awesome.']]],
            ['mika_plays', [7],      3,  true,  [[5, 'Got a legendary pet from the eggs!']]],
            ['buyer',      [19],     2,  true,  [[4, 'Bot works, README was clear.']]],
            ['kaizen_ph',  [13, 10], 1,  false, []],
        ];

        $fee = 0.10;
        $count = 0;
        foreach ($plan as $p => [$buyer, $itemNumbers, $days, $confirmed, $reviews]) {
            $lines = array_map(fn ($n) => $items[$n], $itemNumbers);
            $total = array_sum(array_column($lines, 'price'));
            $created = $this->ago($days, 3);

            $orderId = $this->db->insert('orders', [
                'order_number'     => sprintf('LV-%s-%05d', date('ymd', strtotime($created)), 1000 + $p),
                'buyer_id'         => $users[$buyer],
                'total'            => $total,
                'payment_method'   => ['gcash', 'maya', 'card'][$p % 3],
                'payment_reference' => strtoupper(['gcash', 'maya', 'card'][$p % 3]) . '-' . date('ymd', strtotime($created)) . '-' . strtoupper(substr(md5((string) $p), 0, 8)),
                'status'           => 'paid',
                'created_at'       => $created,
            ]);

            foreach ($lines as $i => $line) {
                $sellerId = $users[$line['seller']];
                $lineId = $this->db->insert('order_items', [
                    'order_id'          => $orderId,
                    'item_id'           => $line['id'],
                    'seller_id'         => $sellerId,
                    'title'             => $line['title'],
                    'price'             => $line['price'],
                    'quantity'          => 1,
                    'delivery_type'     => $line['delivery_type'],
                    'delivered_content' => $line['delivery_content'],
                    'status'            => $confirmed ? 'confirmed' : 'delivered',
                    'confirmed_at'      => $confirmed ? $created : null,
                    'created_at'        => $created,
                ]);

                if ($confirmed) {
                    $this->db->query('UPDATE users SET balance = balance + ? WHERE id = ?', [round($line['price'] * (1 - $fee), 2), $sellerId]);
                }
                if (isset($reviews[$i])) {
                    $this->db->insert('reviews', [
                        'order_item_id' => $lineId,
                        'item_id'       => $line['id'],
                        'buyer_id'      => $users[$buyer],
                        'seller_id'     => $sellerId,
                        'rating'        => $reviews[$i][0],
                        'comment'       => $reviews[$i][1],
                        'created_at'    => $created,
                    ]);
                }
            }
            $count++;
        }

        // Keep "sold" counters consistent with the orders that actually exist.
        $this->db->query('UPDATE items SET sales_count = (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_items.item_id = items.id)');
        return $count;
    }
}
