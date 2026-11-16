<section class="container page">
    <div class="page-head">
        <div>
            <h1>Seller dashboard</h1>
            <p class="muted">Manage your listings and track your sales.</p>
        </div>
        <a href="<?= url('/sell/new') ?>" class="btn btn-primary"><?= icon('plus', 16) ?> New listing</a>
    </div>

    <div class="stat-grid">
        <div class="stat card"><span class="muted">Available balance</span><strong class="price"><?= money($user['balance']) ?></strong><small class="muted">After <?= (int) (config('app.platform_fee') * 100) ?>% platform fee</small></div>
        <div class="stat card"><span class="muted">Awaiting buyer confirmation</span><strong><?= money($totals['pending']) ?></strong><small class="muted">Released when buyers confirm</small></div>
        <div class="stat card"><span class="muted">Total sales</span><strong><?= (int) $stats['sales'] ?></strong><small class="muted"><?= money($totals['gross']) ?> gross</small></div>
        <div class="stat card"><span class="muted">Rating</span><strong><?= $stats['rating'] ?: '–' ?> ★</strong><small class="muted"><?= (int) $stats['reviews'] ?> reviews</small></div>
    </div>

    <div class="card">
        <div class="card-head"><h3>My listings (<?= count($items) ?>)</h3></div>
        <?php if (!$items): ?>
            <div class="empty"><p class="muted">You haven't listed anything yet.</p><a href="<?= url('/sell/new') ?>" class="btn btn-primary">List your first item</a></div>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Item</th><th>Price</th><th>Stock</th><th>Sold</th><th>Views</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="cell-item"><img src="<?= e(item_image($item['image'])) ?>" alt=""><a href="<?= url('/items/' . $item['slug']) ?>"><?= e(str_limit($item['title'], 45)) ?></a></td>
                        <td class="price"><?= money($item['price']) ?></td>
                        <td><?= (int) $item['stock'] ?></td>
                        <td><?= (int) $item['sales_count'] ?></td>
                        <td><?= (int) $item['views'] ?></td>
                        <td><?= badge($item['status']) ?></td>
                        <td class="actions">
                            <a href="<?= url('/sell/' . $item['id'] . '/edit') ?>" class="icon-btn" title="Edit"><?= icon('edit', 16) ?></a>
                            <form action="<?= url('/sell/' . $item['id'] . '/delete') ?>" method="post" data-confirm="Remove this listing?">
                                <?= csrf_field() ?>
                                <button class="icon-btn danger" title="Remove"><?= icon('trash', 16) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card mt">
        <div class="card-head"><h3>Recent sales</h3></div>
        <?php if (!$sales): ?>
            <p class="muted pad">No sales yet.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Order</th><th>Item</th><th>Buyer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?= e($sale['order_number']) ?></td>
                        <td><?= e(str_limit($sale['title'], 40)) ?> × <?= (int) $sale['quantity'] ?></td>
                        <td><?= e($sale['buyer_name']) ?></td>
                        <td class="price"><?= money($sale['price'] * $sale['quantity']) ?></td>
                        <td><?= badge($sale['status']) ?></td>
                        <td class="muted"><?= time_ago($sale['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
