<section class="container page">
    <div class="page-head"><div><h1>Listings</h1><p class="muted"><?= count($items) ?> total</p></div></div>
    <?= \Core\View::partial('admin/_nav', ['active' => 'items']) ?>
    <div class="card">
        <table class="table">
            <thead><tr><th>Item</th><th>Seller</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Moderate</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td class="cell-item"><img src="<?= e(item_image($item['image'])) ?>" alt=""><a href="<?= url('/items/' . $item['slug']) ?>"><?= e(str_limit($item['title'], 40)) ?></a></td>
                    <td><?= e($item['seller_name']) ?></td>
                    <td class="muted"><?= e($item['category_name']) ?></td>
                    <td><?= money($item['price']) ?></td>
                    <td><?= (int) $item['stock'] ?></td>
                    <td><?= badge($item['status']) ?></td>
                    <td>
                        <form action="<?= url('/admin/items/' . $item['id'] . '/status') ?>" method="post" class="inline-form">
                            <?= csrf_field() ?>
                            <select name="status" data-autosubmit>
                                <?php foreach (['active', 'paused', 'removed'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $item['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
