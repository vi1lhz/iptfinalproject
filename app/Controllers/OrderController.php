<?php

namespace App\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use Core\Application;
use Core\Auth;
use Core\Controller;
use Core\HttpException;
use Core\Request;
use Core\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('orders/index', ['title' => 'My orders', 'orders' => Order::forBuyer(Auth::id())]);
    }

    public function show(Request $request, string $number): Response
    {
        $order = Order::findByNumber($number);

        // Ownership check: buyers can only see their own orders (admins can see all).
        if (!$order || ((int) $order['buyer_id'] !== Auth::id() && !Auth::hasRole('admin'))) {
            throw new HttpException(404, 'Order not found.');
        }

        return $this->view('orders/show', [
            'title' => 'Order ' . $order['order_number'],
            'order' => $order,
            'lines' => OrderItem::forOrder((int) $order['id']),
        ]);
    }

    /** Buyer confirms the item works → seller gets paid. */
    public function confirm(Request $request, string $id): Response
    {
        $line = $this->ownedLine((int) $id);
        OrderItem::confirm($line, (float) config('app.platform_fee', 0.10));

        $this->flash('success', 'Thanks for confirming! The seller has been paid. You can now leave a review.');
        return $this->redirect('/orders/' . $line['order_number']);
    }

    public function review(Request $request, string $id): Response
    {
        $line = $this->ownedLine((int) $id);
        if ($line['status'] !== 'confirmed') {
            $this->flash('error', 'Please confirm delivery before leaving a review.');
            return $this->redirect('/orders/' . $line['order_number']);
        }
        if (Review::findBy('order_item_id', $line['id'])) {
            $this->flash('error', 'You already reviewed this item.');
            return $this->redirect('/orders/' . $line['order_number']);
        }

        $data = $this->validate($request, ['rating' => 'required|integer|min:1|max:5', 'comment' => 'max:500']);

        Review::create([
            'order_item_id' => $line['id'],
            'item_id'       => $line['item_id'],
            'buyer_id'      => Auth::id(),
            'seller_id'     => $line['seller_id'],
            'rating'        => (int) $data['rating'],
            'comment'       => $data['comment'] ?? null,
        ]);

        $this->flash('success', 'Review posted. Thank you!');
        return $this->redirect('/orders/' . $line['order_number']);
    }

    /** Protected download: only the buyer of this line item can fetch the file. */
    public function download(Request $request, string $id): Response
    {
        $line = $this->ownedLine((int) $id);

        if ($line['delivery_type'] !== 'file' || empty($line['file_path'])) {
            throw new HttpException(404, 'No file attached to this item.');
        }

        $path = Application::$root . '/storage/files/' . basename($line['file_path']);
        if (!is_file($path)) {
            throw new HttpException(404, 'The file is missing. Please contact the seller.');
        }

        return Response::download($path, $line['file_name'] ?: basename($path));
    }

    private function ownedLine(int $id): array
    {
        $line = OrderItem::findForBuyer($id, Auth::id());
        if (!$line) {
            throw new HttpException(404, 'Order item not found.');
        }
        return $line;
    }
}
