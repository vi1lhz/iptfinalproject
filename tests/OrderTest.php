<?php

namespace Tests;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Core\Database;

/** Checkout business rules: stock, transactions, escrow-style seller payouts. */
class OrderTest extends TestCase
{
    private Database $db;
    private int $seller;
    private int $buyer;
    private int $item;

    public function setUp(): void
    {
        $this->db = $this->freshDatabase();
        $now = date('Y-m-d H:i:s');
        $this->seller = User::register(['username' => 'seller', 'email' => 's@mail.test', 'password' => 'secret123']);
        $this->buyer = User::register(['username' => 'buyer', 'email' => 'b@mail.test', 'password' => 'secret123']);
        $cat = $this->db->insert('categories', ['name' => 'Top-ups', 'slug' => 'top-ups', 'icon' => 'coin', 'created_at' => $now]);
        $this->item = $this->db->insert('items', [
            'seller_id' => $this->seller, 'category_id' => $cat, 'title' => '1000 Diamonds', 'slug' => 'diamonds',
            'description' => 'Top-up code', 'price' => 499, 'stock' => 3, 'delivery_type' => 'code',
            'delivery_content' => 'CODE: ABCD-1234', 'status' => 'active', 'created_at' => $now,
        ]);
    }

    public function testCheckoutCreatesOrderAndDeliversCode(): void
    {
        CartItem::add($this->buyer, $this->item, 2);
        $order = Order::placeFromCart($this->buyer, 'gcash', 'GCASH-TEST');

        $this->assertEquals(998, $order['total']);
        $lines = OrderItem::forOrder($order['id']);
        $this->assertCount(1, $lines);
        $this->assertSame('CODE: ABCD-1234', $lines[0]['delivered_content']);
        $this->assertSame('delivered', $lines[0]['status']);
        $this->assertSame(0, CartItem::countFor($this->buyer), 'Cart should be emptied.');
    }

    public function testCheckoutReducesStockAndMarksSoldOut(): void
    {
        CartItem::add($this->buyer, $this->item, 3);
        Order::placeFromCart($this->buyer, 'maya');
        $item = $this->db->fetch('SELECT stock, sales_count, status FROM items WHERE id = ?', [$this->item]);
        $this->assertEquals(0, $item['stock']);
        $this->assertEquals(3, $item['sales_count']);
        $this->assertSame('sold_out', $item['status']);
    }

    public function testCannotBuyMoreThanStock(): void
    {
        CartItem::add($this->buyer, $this->item, 5);
        $this->assertThrows(\RuntimeException::class, fn () => Order::placeFromCart($this->buyer, 'card'));
        $this->assertSame(0, Order::count(), 'Nothing should be saved when checkout fails.');
        $this->assertEquals(3, $this->db->fetchValue('SELECT stock FROM items WHERE id = ?', [$this->item]));
    }

    public function testCannotBuyOwnListing(): void
    {
        CartItem::add($this->seller, $this->item, 1);
        $e = $this->assertThrows(\RuntimeException::class, fn () => Order::placeFromCart($this->seller, 'gcash'));
        $this->assertContains("can't buy your own", $e->getMessage());
    }

    public function testEmptyCartIsRejected(): void
    {
        $this->assertThrows(\RuntimeException::class, fn () => Order::placeFromCart($this->buyer, 'gcash'));
    }

    public function testConfirmPaysSellerOnceMinusFee(): void
    {
        CartItem::add($this->buyer, $this->item, 2);
        $order = Order::placeFromCart($this->buyer, 'gcash');
        $line = OrderItem::findForBuyer((int) OrderItem::forOrder($order['id'])[0]['id'], $this->buyer);

        OrderItem::confirm($line, 0.10);
        OrderItem::confirm($line, 0.10); // second click must not pay again

        $this->assertEquals(898.20, User::find($this->seller)['balance']); // 998 × 90 %
    }

    public function testBuyerCannotAccessSomeoneElsesLine(): void
    {
        CartItem::add($this->buyer, $this->item, 1);
        $order = Order::placeFromCart($this->buyer, 'gcash');
        $lineId = (int) OrderItem::forOrder($order['id'])[0]['id'];
        $this->assertNull(OrderItem::findForBuyer($lineId, $this->seller));
    }

    public function testCartAddMergesQuantities(): void
    {
        CartItem::add($this->buyer, $this->item, 1);
        CartItem::add($this->buyer, $this->item, 1);
        $this->assertSame(2, CartItem::countFor($this->buyer));
        $this->assertCount(1, CartItem::forUser($this->buyer));
    }
}
