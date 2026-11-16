<?php /** @var array $item */ ?>
<a href="<?= url('/items/' . $item['slug']) ?>" class="card item-card">
    <div class="item-thumb">
        <img src="<?= e(item_image($item['image'])) ?>" alt="" loading="lazy">
        <span class="item-cat"><?= e($item['category_name']) ?></span>
        <?php if ($item['delivery_type'] === 'code'): ?>
            <span class="item-instant" title="Instant delivery"><?= icon('zap', 12) ?> Instant</span>
        <?php else: ?>
            <span class="item-instant" title="Downloadable file"><?= icon('download', 12) ?> Download</span>
        <?php endif; ?>
    </div>
    <div class="item-body">
        <?php if (!empty($item['game'])): ?><div class="item-game"><?= e($item['game']) ?></div><?php endif; ?>
        <h3 class="item-title"><?= e($item['title']) ?></h3>
        <div class="item-seller">
            <?= avatar($item['seller_name'], 'xs') ?>
            <span><?= e($item['seller_name']) ?></span>
            <?php if (!empty($item['seller_verified'])): ?><span class="verified" title="Verified seller"><?= icon('check', 11) ?></span><?php endif; ?>
            <?php if (!empty($item['avg_rating'])): ?>
                <span class="item-rating">★ <?= number_format((float) $item['avg_rating'], 1) ?></span>
            <?php endif; ?>
        </div>
        <div class="item-foot">
            <span class="price"><?= money($item['price']) ?></span>
            <span class="muted small"><?= (int) $item['stock'] ?> in stock</span>
        </div>
    </div>
</a>
