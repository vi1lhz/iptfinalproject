<?php
$editing = $item !== null;
$val = fn (string $key, $default = '') => old($key, $item[$key] ?? $default);
$deliveryType = $val('delivery_type', 'code');
?>
<section class="container page narrow">
    <div class="crumbs"><a href="<?= url('/sell') ?>">Seller dashboard</a> / <span><?= $editing ? 'Edit listing' : 'New listing' ?></span></div>
    <h1><?= $editing ? 'Edit listing' : 'List a new item' ?></h1>

    <form action="<?= url($editing ? '/sell/' . $item['id'] : '/sell') ?>" method="post" enctype="multipart/form-data" class="form card pad">
        <?= csrf_field() ?>

        <div class="form-grid">
            <label class="span-2">Title
                <input type="text" name="title" value="<?= e($val('title')) ?>" placeholder="e.g. Level 150 account – 40 operator skins" required>
                <?php if ($err = error('title')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>

            <label>Category
                <select name="category_id" required>
                    <option value="">Choose…</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= (string) $val('category_id') === (string) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($err = error('category_id')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>

            <label>Game <span class="muted">(from the FreeToGame catalog – optional)</span>
                <select name="game_id">
                    <option value="">Not game-specific</option>
                    <?php foreach ($games as $g): ?>
                        <option value="<?= (int) $g['id'] ?>" <?= (string) $val('game_id', $_GET['game'] ?? '') === (string) $g['id'] ? 'selected' : '' ?>><?= e($g['title']) ?><?= $g['genre'] ? ' (' . e($g['genre']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($err = error('game_id')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>

            <label>Price (₱)
                <input type="number" name="price" step="0.01" min="20" value="<?= e($val('price')) ?>" placeholder="e.g. 499" required>
                <?php if ($err = error('price')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>

            <label>Stock
                <input type="number" name="stock" min="0" value="<?= e($val('stock', 1)) ?>" required>
                <?php if ($err = error('stock')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>

            <label class="span-2">Description
                <textarea name="description" rows="6" required placeholder="What's included, level, region, how delivery works…"><?= e($val('description')) ?></textarea>
                <?php if ($err = error('description')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>

            <label class="span-2">Cover image <span class="muted">(JPG/PNG/WEBP, max 5 MB<?= $editing ? ', leave empty to keep current' : '' ?>)</span>
                <input type="file" name="image" accept="image/*">
                <?php if ($err = error('image')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>

            <fieldset class="span-2 delivery-choice">
                <legend>How is it delivered?</legend>
                <label class="pay-option"><input type="radio" name="delivery_type" value="code" <?= $deliveryType === 'code' ? 'checked' : '' ?> data-toggle-delivery>
                    <span><strong><?= icon('zap', 16) ?> Code / account details</strong><small class="muted">Gift card codes, licence keys, account logins</small></span></label>
                <label class="pay-option"><input type="radio" name="delivery_type" value="file" <?= $deliveryType === 'file' ? 'checked' : '' ?> data-toggle-delivery>
                    <span><strong><?= icon('download', 16) ?> File download</strong><small class="muted">Art packs, music, templates, mods, e-books</small></span></label>
            </fieldset>

            <label class="span-2" data-delivery="code">What the buyer receives
                <textarea name="delivery_content" rows="4" placeholder="Shown ONLY to the buyer after payment, e.g. CODE: XXXX-XXXX-XXXX"><?= e($val('delivery_content')) ?></textarea>
                <small class="muted"><?= icon('lock', 12) ?> Kept private until purchase.</small>
                <?php if ($err = error('delivery_content')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>

            <label class="span-2" data-delivery="file">Digital file <span class="muted">(max <?= (int) config('app.max_upload_mb') ?> MB<?= !empty($item['file_name']) ? ' · current: ' . e($item['file_name']) : '' ?>)</span>
                <input type="file" name="digital_file">
                <small class="muted"><?= icon('lock', 12) ?> Stored outside the public folder – only buyers can download it.</small>
                <?php if ($err = error('digital_file')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>

            <?php if ($editing): ?>
                <label>Status
                    <select name="status">
                        <option value="active" <?= $val('status') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="paused" <?= $val('status') === 'paused' ? 'selected' : '' ?>>Paused (hidden)</option>
                    </select>
                </label>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <a href="<?= url('/sell') ?>" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary"><?= $editing ? 'Save changes' : 'Publish listing' ?></button>
        </div>
    </form>
</section>
