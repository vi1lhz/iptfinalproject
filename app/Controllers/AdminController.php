<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Models\Game;
use App\Services\GameCatalog;
use Core\Auth;
use Core\Controller;
use Core\HttpException;
use Core\Request;
use Core\Response;

class AdminController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $catalog = GameCatalog::instance();

        return $this->view('admin/dashboard', [
            'title'  => 'Admin dashboard',
            'stats'  => [
                'users'    => User::count(),
                'items'    => Item::count(['status' => 'active']),
                'orders'   => Order::count(),
                'revenue'  => Order::revenue(),
                'fees'     => Order::revenue() * (float) config('app.platform_fee'),
            ],
            'recentOrders' => array_slice(Order::allForAdmin(), 0, 8),
            'gameCount'    => Game::count(),
            'gamesSynced'  => $catalog->lastSynced(),
            'topGames'     => Game::browse(['with_listings' => true, 'limit' => 6]),
        ], 'layouts/main');
    }

    public function users(Request $request): Response
    {
        return $this->view('admin/users', ['title' => 'Manage users', 'users' => User::all()]);
    }

    public function toggleBan(Request $request, string $id): Response
    {
        $user = $this->findUser((int) $id);
        if ((int) $user['id'] === Auth::id()) {
            $this->flash('error', "You can't ban yourself.");
            return $this->redirect('/admin/users');
        }
        User::update((int) $user['id'], ['is_banned' => $user['is_banned'] ? 0 : 1]);
        $this->flash('success', $user['username'] . ($user['is_banned'] ? ' was unbanned.' : ' was banned.'));
        return $this->redirect('/admin/users');
    }

    public function toggleVerify(Request $request, string $id): Response
    {
        $user = $this->findUser((int) $id);
        User::update((int) $user['id'], ['is_verified' => $user['is_verified'] ? 0 : 1]);
        $this->flash('success', 'Verification updated for ' . $user['username'] . '.');
        return $this->redirect('/admin/users');
    }

    public function items(Request $request): Response
    {
        return $this->view('admin/items', ['title' => 'Manage listings', 'items' => Item::allForAdmin()]);
    }

    public function itemStatus(Request $request, string $id): Response
    {
        $data = $this->validate($request, ['status' => 'required|in:active,paused,removed']);
        Item::update((int) $id, ['status' => $data['status'], 'updated_at' => date('Y-m-d H:i:s')]);
        $this->flash('success', 'Listing status changed to ' . $data['status'] . '.');
        return $this->redirect('/admin/items');
    }

    public function categories(Request $request): Response
    {
        return $this->view('admin/categories', ['title' => 'Manage categories', 'categories' => Category::withCounts()]);
    }

    public function storeCategory(Request $request): Response
    {
        $data = $this->validate($request, [
            'name'        => 'required|min:2|max:100',
            'slug'        => 'required|alpha_dash|max:100|unique:categories,slug',
            'icon'        => 'required|in:user,sword,gift,coin,palette,code,music,box',
            'description' => 'max:255',
        ]);
        Category::create($data);
        $this->flash('success', 'Category added.');
        return $this->redirect('/admin/categories');
    }

    public function deleteCategory(Request $request, string $id): Response
    {
        if (Item::count(['category_id' => (int) $id]) > 0) {
            $this->flash('error', 'This category still has listings. Move or remove them first.');
            return $this->redirect('/admin/categories');
        }
        Category::delete((int) $id);
        $this->flash('info', 'Category deleted.');
        return $this->redirect('/admin/categories');
    }

    public function orders(Request $request): Response
    {
        return $this->view('admin/orders', ['title' => 'All orders', 'orders' => Order::allForAdmin()]);
    }

    /** Forces a fresh call to the FreeToGame API (ignores the 24 h cache). */
    public function syncGames(Request $request): Response
    {
        $result = GameCatalog::instance()->sync(true);

        $result['source'] === 'api'
            ? $this->flash('success', "Synced {$result['count']} games from the FreeToGame API.")
            : $this->flash('error', "Couldn't reach the FreeToGame API ({$result['error']}). Using {$result['count']} cached games.");

        return $this->redirect('/admin');
    }

    private function findUser(int $id): array
    {
        $user = User::find($id);
        if (!$user) {
            throw new HttpException(404, 'User not found.');
        }
        return $user;
    }
}
