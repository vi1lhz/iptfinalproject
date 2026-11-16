<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\Game;
use App\Services\GameCatalog;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Core\Controller;
use Core\Request;
use Core\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        GameCatalog::instance()->sync();   // cached for 24 h – only calls the API when stale

        return $this->view('home/index', [
            'title'      => 'Buy & sell digital game items',
            'categories' => Category::withCounts(),
            'topItems'   => Item::top(8),
            'latest'     => Item::latest(8),
            'games'      => Game::browse(['limit' => 8]),
            'stats'      => [
                'items'  => Item::count(['status' => 'active']),
                'users'  => User::count(),
                'orders' => Order::count(),
            ],
        ]);
    }
}
