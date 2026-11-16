<?php $link = fn (array $c) => url('/games?' . http_build_query(array_filter(array_merge($filters, $c), fn ($v) => $v !== null && $v !== ''))); ?>
<section class="container page">
    <div class="page-head">
        <div>
            <div class="crumbs"><a href="<?= url('/') ?>">Home</a> / <span>Games</span></div>
            <h1>Games</h1>
            <p class="muted"><?= count($games) ?> free-to-play games · catalog data from <a class="link" href="https://www.freetogame.com" target="_blank" rel="noopener">FreeToGame.com</a>
                <?php if ($sync['source'] === 'snapshot' || $sync['source'] === 'stale'): ?>
                    <span class="badge badge-paused" title="<?= e($sync['error'] ?? '') ?>">offline – showing cached data</span>
                <?php endif; ?>
            </p>
        </div>
        <form class="search inline" action="<?= url('/games') ?>" method="get">
            <?= icon('search') ?>
            <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Search games or publishers">
            <?php if (!empty($filters['genre'])): ?><input type="hidden" name="genre" value="<?= e($filters['genre']) ?>"><?php endif; ?>
        </form>
    </div>

    <div class="toolbar">
        <div class="sort-tabs wrap">
            <a href="<?= $link(['genre' => null]) ?>" class="<?= empty($filters['genre']) ? 'active' : '' ?>">All genres</a>
            <?php foreach ($genres as $genre => $n): ?>
                <a href="<?= $link(['genre' => $genre]) ?>" class="<?= ($filters['genre'] ?? '') === $genre ? 'active' : '' ?>"><?= e($genre) ?> <span class="muted">(<?= (int) $n ?>)</span></a>
            <?php endforeach; ?>
        </div>
        <div class="sort-tabs">
            <a href="<?= $link(['platform' => null]) ?>" class="<?= empty($filters['platform']) ? 'active' : '' ?>">All</a>
            <a href="<?= $link(['platform' => 'pc']) ?>" class="<?= ($filters['platform'] ?? '') === 'pc' ? 'active' : '' ?>">PC</a>
            <a href="<?= $link(['platform' => 'browser']) ?>" class="<?= ($filters['platform'] ?? '') === 'browser' ? 'active' : '' ?>">Browser</a>
        </div>
    </div>

    <?php if (!$games): ?>
        <div class="empty card"><?= icon('search', 36) ?><h3>No games found</h3></div>
    <?php else: ?>
        <div class="game-grid">
            <?php foreach ($games as $g): ?>
                <a href="<?= url('/games/' . $g['id']) ?>" class="card game-card">
                    <div class="game-thumb">
                        <img src="<?= e($g['thumbnail']) ?>" alt="" loading="lazy" data-fallback="<?= asset('assets/img/placeholder.svg') ?>">
                        <?php if ($g['listing_count'] > 0): ?><span class="item-instant"><?= (int) $g['listing_count'] ?> on LootVault</span><?php endif; ?>
                    </div>
                    <div class="item-body">
                        <div class="item-game"><?= e($g['genre']) ?> · <?= e(str_contains((string) $g['platform'], 'Browser') ? 'Browser' : 'PC') ?></div>
                        <h3 class="item-title"><?= e($g['title']) ?></h3>
                        <p class="muted small clamp-2"><?= e($g['short_description']) ?></p>
                        <div class="muted small"><?= e($g['publisher']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
