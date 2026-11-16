<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\Game;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use Core\Controller;
use Core\HttpException;
use Core\Request;
use Core\Response;

class ItemController extends Controller
{
    /** /browse?q=&category=&sort=&min=&max=&page= */
    public function index(Request $request): Response
    {
        $filters = $request->only(['q', 'category', 'game', 'sort', 'min', 'max', 'page']);
        $result = Item::browse($filters);

        $activeCategory = !empty($filters['category']) ? Category::findBy('slug', $filters['category']) : null;
        $activeGame = !empty($filters['game']) ? Game::find((int) $filters['game']) : null;

        return $this->view('items/index', [
            'title'          => $activeGame ? $activeGame['title'] . ' listings' : ($activeCategory['name'] ?? (!empty($filters['q']) ? 'Search: ' . $filters['q'] : 'Browse marketplace')),
            'result'         => $result,
            'filters'        => $filters,
            'categories'     => Category::withCounts(),
            'activeCategory' => $activeCategory,
            'activeGame'     => $activeGame,
        ]);
    }

    public function category(Request $request, string $slug): Response
    {
        return $this->redirect('/browse?category=' . urlencode($slug));
    }

    public function show(Request $request, string $slug): Response
    {
        $item = Item::findDetailed($slug);
        if (!$item || ($item['status'] === 'removed')) {
            throw new HttpException(404, 'This listing does not exist or was removed.');
        }

        Item::incrementViews((int) $item['id']);

        return $this->view('items/show', [
            'title'       => $item['title'],
            'item'        => $item,
            'reviews'     => Review::forItem((int) $item['id']),
            'related'     => Item::related($item),
            'sellerStats' => User::sellerStats((int) $item['seller_id']),
            'gameInfo'    => $item['game_id'] ? Game::find((int) $item['game_id']) : null,
        ]);
    }

    /** GET /api/items?q=... – the marketplace's own JSON API (built with Response::json). */
    public function apiIndex(Request $request): Response
    {
        $result = Item::browse($request->only(['q', 'category', 'game', 'sort', 'min', 'max', 'page']));

        return $this->json([
            'page'  => $result['page'],
            'pages' => $result['pages'],
            'total' => $result['total'],
            'data'  => array_map(fn ($i) => [
                'id'        => (int) $i['id'],
                'title'     => $i['title'],
                'game'      => $i['game'],
                'category'  => $i['category_name'],
                'price_php' => (float) $i['price'],
                'stock'     => (int) $i['stock'],
                'seller'    => $i['seller_name'],
                'url'       => url('/items/' . $i['slug']),
            ], $result['items']),
        ]);
    }
}
