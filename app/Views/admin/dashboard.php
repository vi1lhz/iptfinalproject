<section class="container page">
    <div class="page-head"><div><h1>Admin panel</h1><p class="muted">Marketplace overview.</p></div></div>
    <?= \Core\View::partial('admin/_nav', ['active' => 'dashboard']) ?>

    <div class="stat-grid">
        <div class="stat card"><span class="muted">Users</span><strong><?= number_format($stats['users']) ?></strong></div>
        <div class="stat card"><span class="muted">Active listings</span><strong><?= number_format($stats['items']) ?></strong></div>
        <div class="stat card"><span class="muted">Orders</span><strong><?= number_format($stats['orders']) ?></strong></div>
        <div class="stat card"><span class="muted">Sales volume</span><strong class="price"><?= money($stats['revenue']) ?></strong><small class="muted">Platform fees: <?= money($stats['fees']) ?></small></div>
    </div>

    <div class="admin-grid">
        <div class="card">
            <div class="card-head"><h3>Recent orders</h3><a href="<?= url('/admin/orders') ?>" class="link">All orders →</a></div>
            <table class="table">
                <thead><tr><th>Order</th><th>Buyer</th><th>Total</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td><a href="<?= url('/orders/' . $o['order_number']) ?>"><?= e($o['order_number']) ?></a></td>
                        <td><?= e($o['buyer_name']) ?></td>
                        <td class="price"><?= money($o['total']) ?></td>
                        <td class="muted"><?= time_ago($o['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recentOrders): ?><tr><td colspan="4" class="muted">No orders yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card pad">
            <h3><?= icon('globe', 18) ?> FreeToGame API</h3>
            <p class="muted small"><?= (int) $gameCount ?> games cached · last sync: <?= $gamesSynced && $gamesSynced > '2001' ? e(time_ago($gamesSynced)) : '<span class="rate-fallback">offline sample only</span>' ?></p>
            <table class="table compact">
                <thead><tr><th>Game</th><th>Listings</th></tr></thead>
                <tbody>
                <?php foreach ($topGames as $g): ?>
                    <tr><td><a href="<?= url('/games/' . $g['id']) ?>"><?= e($g['title']) ?></a></td><td><?= (int) $g['listing_count'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <form action="<?= url('/admin/games/sync') ?>" method="post">
                <?= csrf_field() ?>
                <button class="btn btn-outline btn-sm btn-block">Sync games from API now</button>
            </form>
            <p class="muted small">Endpoint: <code><?= e(\Core\Env::get('FREETOGAME_API_URL', 'https://www.freetogame.com/api')) ?>/games</code></p>
        </div>
    </div>
</section>
