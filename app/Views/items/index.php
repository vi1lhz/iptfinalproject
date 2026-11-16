<?php
$q = $filters['q'] ?? '';
$sort = $filters['sort'] ?? 'newest';
/** Keeps current filters when changing one of them. */
$link = function (array $changes) use ($filters) {
    $params = array_filter(array_merge($filters, $changes), fn ($v) => $v !== null && $v !== '');
    return url('/browse' . ($params ? '?' . http_build_query($params) : ''));
};
?>
<section class="container page">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="<?= url('/') ?>">Home</a> / <span><?= e($activeGame['title'] ?? $activeCategory['name'] ?? 'Browse') ?></span></div>
            <h1><?= e($title) ?></h1>
            <p class="muted"><?= number_format($result['total']) ?> listing<?= $result['total'] == 1 ? '' : 's' ?> found<?= $activeCategory ? ' · ' . e($activeCategory['description']) : '' ?></p>
        </div>
    </div>

    <div class="browse">
        <aside class="filters card">
            <form method="get" action="<?= url('/browse') ?>">
                <?php if ($q): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
                <?php if ($activeGame): ?>
                    <div class="active-game">
                        <img src="<?= e($activeGame['thumbnail']) ?>" alt="" data-fallback="<?= asset('assets/img/placeholder.svg') ?>">
                        <span><?= e($activeGame['title']) ?></span>
                        <a href="<?= $link(['game' => null, 'page' => null]) ?>" aria-label="Clear game filter">×</a>
                    </div>
                    <input type="hidden" name="game" value="<?= (int) $activeGame['id'] ?>">
                <?php endif; ?>
                <h4>Category</h4>
                <label class="radio"><input type="radio" name="category" value="" <?= empty($filters['category']) ? 'checked' : '' ?>> All categories</label>
                <?php foreach ($categories as $cat): ?>
                    <label class="radio">
                        <input type="radio" name="category" value="<?= e($cat['slug']) ?>" <?= ($filters['category'] ?? '') === $cat['slug'] ? 'checked' : '' ?>>
                        <?= e($cat['name']) ?> <span class="muted">(<?= (int) $cat['item_count'] ?>)</span>
                    </label>
                <?php endforeach; ?>

                <h4>Price (₱)</h4>
                <div class="price-range">
                    <input type="number" name="min" min="0" step="1" placeholder="Min" value="<?= e($filters['min'] ?? '') ?>">
                    <span>–</span>
                    <input type="number" name="max" min="0" step="1" placeholder="Max" value="<?= e($filters['max'] ?? '') ?>">
                </div>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
                <button class="btn btn-primary btn-block">Apply filters</button>
                <a href="<?= url('/browse') ?>" class="btn btn-ghost btn-block">Reset</a>
            </form>
        </aside>

        <div>
            <div class="toolbar">
                <div class="sort-tabs">
                    <?php foreach (['newest' => 'Newest', 'popular' => 'Popular', 'price_asc' => 'Price ↑', 'price_desc' => 'Price ↓'] as $key => $label): ?>
                        <a href="<?= $link(['sort' => $key, 'page' => null]) ?>" class="<?= $sort === $key ? 'active' : '' ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                </div>
                <span class="muted small">All prices in Philippine pesos (₱)</span>
            </div>

            <?php if (!$result['items']): ?>
                <div class="empty card">
                    <?= icon('search', 36) ?>
                    <h3>No listings found</h3>
                    <p class="muted">Try a different search or remove some filters.</p>
                </div>
            <?php else: ?>
                <div class="item-grid">
                    <?php foreach ($result['items'] as $item): ?>
                        <?= \Core\View::partial('partials/item-card', ['item' => $item]) ?>
                    <?php endforeach; ?>
                </div>

                <?php if ($result['pages'] > 1): ?>
                    <nav class="pagination">
                        <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                            <a href="<?= $link(['page' => $p]) ?>" class="<?= $p === $result['page'] ? 'active' : '' ?>"><?= $p ?></a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
