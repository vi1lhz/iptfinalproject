<section class="container page narrow">
    <h1>Checkout</h1>
    <form action="<?= url('/checkout') ?>" method="post" class="cart-layout">
        <?= csrf_field() ?>
        <div class="card pad">
            <h3>Payment method</h3>
            <div class="sim-note"><?= icon('shield', 16) ?> <span><strong>Simulated payment.</strong> This is a school project, so no real money is charged. Choose any method below.</span></div>
            <div class="pay-options">
                <?php foreach (['gcash' => ['GCash', 'Pay with your GCash wallet'], 'maya' => ['Maya', 'Pay with your Maya wallet'], 'card' => ['Credit / debit card', 'Visa, Mastercard, JCB']] as $value => [$name, $desc]): ?>
                    <label class="pay-option">
                        <input type="radio" name="payment_method" value="<?= $value ?>" <?= old('payment_method', 'gcash') === $value ? 'checked' : '' ?>>
                        <span class="pay-logo pay-<?= $value ?>"><?= $value === 'card' ? icon('lock', 16) : e(strtoupper(substr($name, 0, 1))) ?></span>
                        <span><strong><?= $name ?></strong><small class="muted"><?= $desc ?></small></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php if ($err = error('payment_method')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>

            <label class="form-label mt">Account / cardholder name
                <input type="text" name="account_name" value="<?= e(old('account_name', $authUser['username'] ?? '')) ?>" placeholder="e.g. Juan Dela Cruz">
                <?php if ($err = error('account_name')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>

            <h3 class="mt">Items</h3>
            <?php foreach ($rows as $row): ?>
                <div class="sum-row"><span><?= e($row['title']) ?> × <?= (int) $row['quantity'] ?></span><span><?= money($row['price'] * $row['quantity']) ?></span></div>
            <?php endforeach; ?>

            <label class="check mt">
                <input type="checkbox" name="agree" value="1">
                I understand digital items are delivered instantly and I will confirm once I've checked them.
            </label>
            <?php if (error('agree')): ?><small class="field-error">Please tick the box to continue.</small><?php endif; ?>
        </div>

        <aside class="card pad summary">
            <h3>Total</h3>
            <div class="sum-row"><span>Subtotal</span><span><?= money($total) ?></span></div>
            <div class="sum-row"><span>Service fee</span><span>₱0.00</span></div>
            <div class="sum-row total"><span>To pay</span><span class="price"><?= money($total) ?></span></div>
            <button class="btn btn-primary btn-block"><?= icon('lock', 16) ?> Pay <?= money($total) ?></button>
            <p class="muted small center"><?= icon('shield', 14) ?> Seller is paid only after you confirm.</p>
        </aside>
    </form>
</section>
