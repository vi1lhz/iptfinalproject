<?php

namespace App\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Response;

class CheckoutController extends Controller
{
    public function show(Request $request): Response
    {
        $rows = CartItem::forUser(Auth::id());
        if (!$rows) {
            $this->flash('info', 'Your cart is empty.');
            return $this->redirect('/browse');
        }

        $total = array_sum(array_map(fn ($r) => $r['price'] * $r['quantity'], $rows));

        return $this->view('cart/checkout', [
            'title'    => 'Checkout',
            'rows'     => $rows,
            'total'    => $total,
        ]);
    }

    public function place(Request $request): Response
    {
        $data = $this->validate($request, [
            'payment_method' => 'required|in:gcash,maya,card',
            'account_name'   => 'required|min:2|max:80',
            'agree'          => 'required',
        ]);

        try {
            // NOTE: payment is SIMULATED (school project – no real money moves). A real
            // gateway such as PayMongo would be called here before the order is created.
            $reference = strtoupper($data['payment_method']) . '-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $order = Order::placeFromCart(Auth::id(), $data['payment_method'], $reference);
        } catch (\RuntimeException $e) {
            $this->flash('error', $e->getMessage());
            return $this->redirect('/cart');
        }

        $this->flash('success', 'Payment received (simulated)! Your items have been delivered instantly.');
        return $this->redirect('/orders/' . $order['order_number']);
    }
}
