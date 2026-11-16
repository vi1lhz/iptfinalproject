<section class="container page narrow">
    <div class="crumbs"><a href="<?= url('/orders') ?>">My orders</a> / <span><?= e($order['order_number']) ?></span></div>
    <div class="page-head">
        <div>
            <h1>Order <?= e($order['order_number']) ?></h1>
            <p class="muted">Placed <?= date('F j, Y g:i A', strtotime($order['created_at'])) ?> · <?= e(payment_label($order['payment_method'])) ?> · <?= badge($order['status']) ?></p>
        </div>
        <div class="order-total">
            <small class="muted">Total paid</small>
            <span class="price big"><?= money($order['total']) ?></span>
            <?php if ($order['payment_reference']): ?><small class="muted">Ref: <?= e($order['payment_reference']) ?></small><?php endif; ?>
        </div>
    </div>

    <?php foreach ($lines as $line): ?>
        <div class="card pad order-line">
            <div class="order-line-head">
                <img src="<?= e(item_image($line['image'])) ?>" alt="">
                <div>
                    <strong><?= e($line['title']) ?></strong>
                    <div class="muted small">Seller: <?= e($line['seller_name']) ?> · Qty <?= (int) $line['quantity'] ?> · <?= money($line['price'] * $line['quantity']) ?></div>
                </div>
                <?= badge($line['status']) ?>
            </div>

            <div class="delivery">
                <div class="delivery-label"><?= icon('zap', 14) ?> Your delivery</div>
                <?php if ($line['delivery_type'] === 'file'): ?>
                    <a href="<?= url('/orders/items/' . $line['id'] . '/download') ?>" class="btn btn-outline"><?= icon('download', 16) ?> Download <?= e($line['file_name'] ?? 'file') ?></a>
                <?php endif; ?>
                <?php if ($line['delivered_content']): ?>
                    <pre class="code-box" data-copy><?= e($line['delivered_content']) ?></pre>
                <?php endif; ?>
            </div>

            <?php if ($line['status'] === 'delivered'): ?>
                <form action="<?= url('/orders/items/' . $line['id'] . '/confirm') ?>" method="post" class="confirm-box">
                    <?= csrf_field() ?>
                    <span class="muted small">Checked it and everything works? Confirming releases payment to the seller.</span>
                    <button class="btn btn-primary btn-sm"><?= icon('check', 16) ?> Confirm received</button>
                </form>
            <?php elseif ($line['my_rating']): ?>
                <div class="muted small">You rated this <?= stars($line['my_rating'], false) ?></div>
            <?php else: ?>
                <form action="<?= url('/orders/items/' . $line['id'] . '/review') ?>" method="post" class="review-form">
                    <?= csrf_field() ?>
                    <strong>Leave a review</strong>
                    <div class="star-input">
                        <?php for ($s = 5; $s >= 1; $s--): ?>
                            <input type="radio" id="r<?= $line['id'] ?>-<?= $s ?>" name="rating" value="<?= $s ?>" <?= $s === 5 ? 'checked' : '' ?>>
                            <label for="r<?= $line['id'] ?>-<?= $s ?>">★</label>
                        <?php endfor; ?>
                    </div>
                    <input type="text" name="comment" maxlength="500" placeholder="How was your experience? (optional)">
                    <button class="btn btn-outline btn-sm">Post review</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
