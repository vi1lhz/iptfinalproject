<section class="container page">
    <div class="page-head"><div><h1>Categories</h1></div></div>
    <?= \Core\View::partial('admin/_nav', ['active' => 'categories']) ?>
    <div class="admin-grid">
        <div class="card">
            <table class="table">
                <thead><tr><th>Name</th><th>Slug</th><th>Listings</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td class="cell-item"><span class="cat-icon sm"><?= icon($cat['icon'], 16) ?></span> <?= e($cat['name']) ?></td>
                        <td class="muted"><?= e($cat['slug']) ?></td>
                        <td><?= (int) $cat['item_count'] ?></td>
                        <td>
                            <form action="<?= url('/admin/categories/' . $cat['id'] . '/delete') ?>" method="post" data-confirm="Delete <?= e($cat['name']) ?>?">
                                <?= csrf_field() ?><button class="icon-btn danger"><?= icon('trash', 16) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form action="<?= url('/admin/categories') ?>" method="post" class="card pad form">
            <?= csrf_field() ?>
            <h3>Add category</h3>
            <label>Name<input type="text" name="name" value="<?= e(old('name')) ?>" required>
                <?php if ($err = error('name')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?></label>
            <label>Slug<input type="text" name="slug" value="<?= e(old('slug')) ?>" placeholder="e.g. mobile-games" required>
                <?php if ($err = error('slug')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?></label>
            <label>Icon
                <select name="icon"><?php foreach (['user', 'sword', 'gift', 'coin', 'palette', 'code', 'music', 'box'] as $ic): ?><option><?= $ic ?></option><?php endforeach; ?></select>
            </label>
            <label>Description<input type="text" name="description" value="<?= e(old('description')) ?>"></label>
            <button class="btn btn-primary">Add category</button>
        </form>
    </div>
</section>
