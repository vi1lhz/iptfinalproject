<?php

namespace App\Controllers;

use App\Models\CartItem;
use App\Models\Item;
use Core\Auth;
use Core\Controller;
use Core\HttpException;
use Core\Request;
use Core\Response;

class CartController extends Controller
{
    public function index(Request $request): Response
    {
        $rows = CartItem::forUser(Auth::id());
        $total = array_sum(array_map(fn ($r) => $r['price'] * $r['quantity'], $rows));

        return $this->view('cart/index', ['title' => 'Your cart', 'rows' => $rows, 'total' => $total]);
    }

    public function add(Request $request, string $id): Response
    {
        $item = Item::find((int) $id);
        if (!$item || $item['status'] !== 'active') {
            throw new HttpException(404, 'Item not available.');
        }
        if ((int) $item['seller_id'] === Auth::id()) {
            $this->flash('error', "You can't buy your own listing.");
            return $this->back();
        }

        $quantity = max(1, min((int) $request->input('quantity', 1), (int) $item['stock']));
        CartItem::add(Auth::id(), (int) $item['id'], $quantity);

        $this->flash('success', "\"{$item['title']}\" was added to your cart.");
        return $request->input('buy_now') ? $this->redirect('/checkout') : $this->back();
    }

    public function update(Request $request, string $id): Response
    {
        $row = CartItem::find((int) $id);
        if (!$row || (int) $row['user_id'] !== Auth::id()) {
            throw new HttpException(404, 'Cart item not found.');
        }

        $item = Item::find((int) $row['item_id']);
        $quantity = max(1, min((int) $request->input('quantity', 1), (int) $item['stock']));
        CartItem::update((int) $id, ['quantity' => $quantity]);

        return $this->redirect('/cart');
    }

    public function remove(Request $request, string $id): Response
    {
        CartItem::removeFor(Auth::id(), (int) $id);
        $this->flash('info', 'Item removed from cart.');
        return $this->redirect('/cart');
    }
}
