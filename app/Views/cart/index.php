<section class="container page narrow">
    <h1>Your cart</h1>

    <?php if (!$rows): ?>
        <div class="empty card">
            <?= icon('cart', 36) ?>
            <h3>Your cart is empty</h3>
            <p class="muted">Find something you like in the marketplace.</p>
            <a href="<?= url('/browse') ?>" class="btn btn-primary">Browse items</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="card">
                <?php foreach ($rows as $row): ?>
                    <div class="cart-row">
                        <img src="<?= e(item_image($row['image'])) ?>" alt="">
                        <div class="cart-info">
                            <a href="<?= url('/items/' . $row['slug']) ?>"><strong><?= e($row['title']) ?></strong></a>
                            <div class="muted small">Seller: <?= e($row['seller_name']) ?> · <?= $row['delivery_type'] === 'code' ? 'Instant code' : 'File download' ?></div>
                            <?php if ($row['status'] !== 'active' || $row['quantity'] > $row['stock']): ?>
                                <div class="field-error">Only <?= (int) $row['stock'] ?> available – please adjust.</div>
                            <?php endif; ?>
                        </div>
                        <form action="<?= url('/cart/update/' . $row['cart_id']) ?>" method="post" class="qty-form">
                            <?= csrf_field() ?>
                            <input type="number" name="quantity" value="<?= (int) $row['quantity'] ?>" min="1" max="<?= max(1, (int) $row['stock']) ?>" data-autosubmit>
                        </form>
                        <div class="cart-price"><?= money($row['price'] * $row['quantity']) ?></div>
                        <form action="<?= url('/cart/remove/' . $row['cart_id']) ?>" method="post">
                            <?= csrf_field() ?>
                            <button class="icon-btn danger" aria-label="Remove"><?= icon('trash', 18) ?></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <aside class="card pad summary">
                <h3>Order summary</h3>
                <div class="sum-row"><span>Items</span><span><?= array_sum(array_column($rows, 'quantity')) ?></span></div>
                <div class="sum-row"><span>Service fee</span><span>Free</span></div>
                <div class="sum-row total"><span>Total</span><span class="price"><?= money($total) ?></span></div>
                <a href="<?= url('/checkout') ?>" class="btn btn-primary btn-block">Proceed to checkout</a>
                <p class="muted small center"><?= icon('shield', 14) ?> Protected by LootVault escrow</p>
            </aside>
        </div>
    <?php endif; ?>
</section>
