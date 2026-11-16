<section class="container page">
    <div class="page-head"><div><h1>Orders</h1><p class="muted"><?= count($orders) ?> total</p></div></div>
    <?= \Core\View::partial('admin/_nav', ['active' => 'orders']) ?>
    <div class="card">
        <table class="table">
            <thead><tr><th>Order</th><th>Buyer</th><th>Items</th><th>Total</th><th>Payment</th><th>Reference</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><a href="<?= url('/orders/' . $o['order_number']) ?>"><?= e($o['order_number']) ?></a></td>
                    <td><?= e($o['buyer_name']) ?></td>
                    <td><?= (int) $o['item_count'] ?></td>
                    <td class="price"><?= money($o['total']) ?></td>
                    <td><?= e(payment_label($o['payment_method'])) ?></td>
                    <td class="muted small"><?= e($o['payment_reference'] ?? '–') ?></td>
                    <td class="muted"><?= date('M j, Y g:i A', strtotime($o['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?><tr><td colspan="7" class="muted">No orders yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
