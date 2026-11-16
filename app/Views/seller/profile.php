<section class="container page">
    <div class="profile-head card pad">
        <?= avatar($seller['username'], 'xl') ?>
        <div>
            <h1><?= e($seller['username']) ?>
                <?php if ($seller['is_verified']): ?><span class="verified big" title="Verified seller"><?= icon('check', 14) ?></span><?php endif; ?>
            </h1>
            <p class="muted"><?= e($seller['bio'] ?: 'No bio yet.') ?></p>
            <p class="muted small">Member since <?= date('F Y', strtotime($seller['created_at'])) ?></p>
        </div>
        <div class="profile-stats">
            <div><strong><?= $stats['rating'] ?: '–' ?></strong><span>Rating</span></div>
            <div><strong><?= (int) $stats['sales'] ?></strong><span>Sales</span></div>
            <div><strong><?= (int) $stats['listings'] ?></strong><span>Listings</span></div>
        </div>
    </div>

    <div class="section-head"><h2>Listings</h2></div>
    <?php if (!$items): ?>
        <p class="muted">No active listings.</p>
    <?php else: ?>
        <div class="item-grid">
            <?php foreach ($items as $item): ?>
                <?= \Core\View::partial('partials/item-card', ['item' => $item]) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($reviews): ?>
        <div class="section-head"><h2>Recent reviews</h2></div>
        <div class="card pad">
            <?php foreach ($reviews as $review): ?>
                <div class="review">
                    <?= avatar($review['username'], 'sm') ?>
                    <div>
                        <div><strong><?= e($review['username']) ?></strong> <?= stars($review['rating'], false) ?> <span class="muted small">on <?= e($review['title']) ?> · <?= time_ago($review['created_at']) ?></span></div>
                        <?php if ($review['comment']): ?><p><?= e($review['comment']) ?></p><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
