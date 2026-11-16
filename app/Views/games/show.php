<section class="container page">
    <div class="crumbs"><a href="<?= url('/') ?>">Home</a> / <a href="<?= url('/games') ?>">Games</a> / <span><?= e($game['title']) ?></span></div>

    <div class="game-hero card">
        <img class="game-cover" src="<?= e($game['thumbnail']) ?>" alt="" data-fallback="<?= asset('assets/img/placeholder.svg') ?>">
        <div class="game-hero-info">
            <span class="eyebrow"><?= e($game['genre']) ?></span>
            <h1><?= e($game['title']) ?></h1>
            <p class="muted"><?= e($game['short_description']) ?></p>
            <dl class="facts">
                <div><dt>Platform</dt><dd><?= e($game['platform'] ?: '–') ?></dd></div>
                <div><dt>Publisher</dt><dd><?= e($game['publisher'] ?: '–') ?></dd></div>
                <div><dt>Developer</dt><dd><?= e($game['developer'] ?: '–') ?></dd></div>
                <div><dt>Released</dt><dd><?= $game['release_date'] ? e(date('M j, Y', strtotime($game['release_date']))) : '–' ?></dd></div>
            </dl>
            <div class="hero-buttons">
                <a href="<?= url('/browse?game=' . $game['id']) ?>" class="btn btn-primary"><?= icon('cart', 16) ?> Shop <?= count($listings) ?> listing<?= count($listings) == 1 ? '' : 's' ?></a>
                <?php if ($game['game_url']): ?><a href="<?= e($game['game_url']) ?>" class="btn btn-outline" target="_blank" rel="noopener">Play free ↗</a><?php endif; ?>
                <?php if (\Core\Auth::check()): ?><a href="<?= url('/sell/new?game=' . $game['id']) ?>" class="btn btn-ghost"><?= icon('plus', 16) ?> Sell for this game</a><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="product-lower">
        <div class="card pad">
            <h2>About the game</h2>
            <div class="description"><?= nl2br(e($game['description'] ?: $game['short_description'])) ?></div>
            <?php if ($game['screenshots']): ?>
                <div class="shots">
                    <?php foreach ($game['screenshots'] as $shot): ?>
                        <a href="<?= e($shot) ?>" target="_blank" rel="noopener"><img src="<?= e($shot) ?>" alt="Screenshot" loading="lazy" data-fallback="<?= asset('assets/img/placeholder.svg') ?>"></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card pad">
            <h2>Minimum requirements</h2>
            <?php if ($game['requirements']): ?>
                <dl class="reqs">
                    <?php foreach ($game['requirements'] as $key => $value): ?>
                        <?php if ($value): ?><div><dt><?= e(ucfirst($key)) ?></dt><dd><?= e($value) ?></dd></div><?php endif; ?>
                    <?php endforeach; ?>
                </dl>
            <?php else: ?>
                <p class="muted"><?= str_contains((string) $game['platform'], 'Browser') ? 'Runs in any modern web browser.' : 'Not available yet – details load from FreeToGame when online.' ?></p>
            <?php endif; ?>
            <p class="muted small">Source: <a class="link" href="<?= e($game['profile_url'] ?: 'https://www.freetogame.com') ?>" target="_blank" rel="noopener">FreeToGame.com</a></p>
        </div>
    </div>

    <div class="section-head"><h2>Listings for <?= e($game['title']) ?></h2></div>
    <?php if (!$listings): ?>
        <div class="empty card"><p class="muted">No listings for this game yet.</p>
            <a href="<?= url('/sell/new?game=' . $game['id']) ?>" class="btn btn-primary">Be the first to sell</a></div>
    <?php else: ?>
        <div class="item-grid">
            <?php foreach ($listings as $item): ?>
                <?= \Core\View::partial('partials/item-card', ['item' => $item]) ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
