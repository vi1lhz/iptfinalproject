<section class="container page narrow">
    <h1>My orders</h1>
    <?php if (!$orders): ?>
        <div class="empty card">
            <?= icon('box', 36) ?>
            <h3>No orders yet</h3>
            <a href="<?= url('/browse') ?>" class="btn btn-primary">Start shopping</a>
        </div>
    <?php else: ?>
        <div class="card">
            <table class="table">
                <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?= e($order['order_number']) ?></strong></td>
                        <td class="muted"><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></td>
                        <td><?= (int) $order['item_count'] ?></td>
                        <td class="price"><?= money($order['total']) ?></td>
                        <td><?= badge($order['status']) ?></td>
                        <td><a href="<?= url('/orders/' . $order['order_number']) ?>" class="btn btn-sm btn-outline">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
