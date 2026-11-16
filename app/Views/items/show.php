<?php
$isOwner = $authUser && (int) $authUser['id'] === (int) $item['seller_id'];
$available = $item['status'] === 'active' && $item['stock'] > 0;
?>
<section class="container page">
    <div class="crumbs">
        <a href="<?= url('/') ?>">Home</a> /
        <a href="<?= url('/browse?category=' . $item['category_slug']) ?>"><?= e($item['category_name']) ?></a> /
        <span><?= e(str_limit($item['title'], 40)) ?></span>
    </div>

    <div class="product">
        <div class="product-media card">
            <img src="<?= e(item_image($item['image'])) ?>" alt="<?= e($item['title']) ?>">
        </div>

        <div class="product-info">
            <?php if ($item['game']): ?><a href="<?= url('/games/' . $item['game_id']) ?>" class="eyebrow"><?= e($item['game']) ?></a><?php endif; ?>
            <h1><?= e($item['title']) ?></h1>
            <div class="product-meta">
                <?= stars($item['avg_rating']) ?>
                <span class="muted">(<?= (int) $item['review_count'] ?> reviews)</span>
                <span class="muted"><?= icon('eye', 14) ?> <?= number_format($item['views']) ?> views</span>
                <span class="muted"><?= icon('cart', 14) ?> <?= number_format($item['sales_count']) ?> sold</span>
            </div>

            <div class="buy-box card">
                <div class="buy-price">
                    <span class="price big"><?= money($item['price']) ?></span>
                </div>
                <ul class="buy-facts">
                    <li><?= icon($item['delivery_type'] === 'code' ? 'zap' : 'download', 16) ?>
                        <?= $item['delivery_type'] === 'code' ? 'Instant delivery – details shown right after payment' : 'Downloadable file – available right after payment' ?></li>
                    <li><?= icon('box', 16) ?> <?= $available ? (int) $item['stock'] . ' in stock' : 'Out of stock' ?></li>
                    <li><?= icon('shield', 16) ?> Payment held until you confirm it works</li>
                </ul>

                <?php if ($isOwner): ?>
                    <a href="<?= url('/sell/' . $item['id'] . '/edit') ?>" class="btn btn-outline btn-block"><?= icon('edit', 16) ?> Edit your listing</a>
                <?php elseif (!$available): ?>
                    <button class="btn btn-block" disabled>Not available</button>
                <?php else: ?>
                    <form action="<?= url('/cart/add/' . $item['id']) ?>" method="post" class="buy-form">
                        <?= csrf_field() ?>
                        <label class="qty">Qty
                            <input type="number" name="quantity" value="1" min="1" max="<?= (int) $item['stock'] ?>">
                        </label>
                        <button class="btn btn-outline" name="add" value="1"><?= icon('cart', 16) ?> Add to cart</button>
                        <button class="btn btn-primary" name="buy_now" value="1"><?= icon('zap', 16) ?> Buy now</button>
                    </form>
                <?php endif; ?>
            </div>

            <a class="seller-box card" href="<?= url('/sellers/' . $item['seller_name']) ?>">
                <?= avatar($item['seller_name'], 'lg') ?>
                <div>
                    <strong><?= e($item['seller_name']) ?>
                        <?php if ($item['seller_verified']): ?><span class="verified" title="Verified seller"><?= icon('check', 11) ?></span><?php endif; ?>
                    </strong>
                    <div class="muted small">
                        <?= stars($sellerStats['rating']) ?> · <?= (int) $sellerStats['sales'] ?> sales · <?= (int) $sellerStats['listings'] ?> listings
                    </div>
                </div>
                <span class="link">View profile →</span>
            </a>
        </div>
    </div>

    <div class="product-lower">
        <div class="card pad">
            <h2>Description</h2>
            <div class="description"><?= nl2br(e($item['description'])) ?></div>
        </div>

        <div class="side-stack">
        <?php if ($gameInfo): ?>
            <a href="<?= url('/games/' . $gameInfo['id']) ?>" class="card game-mini">
                <img src="<?= e($gameInfo['thumbnail']) ?>" alt="" data-fallback="<?= asset('assets/img/placeholder.svg') ?>">
                <div>
                    <small class="muted">About the game</small>
                    <strong><?= e($gameInfo['title']) ?></strong>
                    <p class="muted small"><?= e(str_limit((string) $gameInfo['short_description'], 110)) ?></p>
                    <div class="tags"><span class="tag"><?= e($gameInfo['genre']) ?></span><span class="tag"><?= e($gameInfo['platform']) ?></span><span class="tag"><?= e($gameInfo['publisher']) ?></span></div>
                    <span class="link small">Game details &amp; more listings →</span>
                </div>
            </a>
        <?php endif; ?>
        <div class="card pad">
            <h2>Reviews</h2>
            <?php if (!$reviews): ?>
                <p class="muted">No reviews yet. Buyers can review after confirming delivery.</p>
            <?php endif; ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review">
                    <?= avatar($review['username'], 'sm') ?>
                    <div>
                        <div><strong><?= e($review['username']) ?></strong> <?= stars($review['rating'], false) ?> <span class="muted small"><?= time_ago($review['created_at']) ?></span></div>
                        <?php if ($review['comment']): ?><p><?= e($review['comment']) ?></p><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        </div>
    </div>

    <?php if ($related): ?>
        <div class="section-head"><h2>Similar items</h2></div>
        <div class="item-grid">
            <?php foreach ($related as $r): ?>
                <?= \Core\View::partial('partials/item-card', ['item' => $r]) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
