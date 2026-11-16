<section class="hero">
    <div class="container hero-inner">
        <div class="hero-copy">
            <span class="eyebrow"><?= icon('shield', 14) ?> Buyer protection on every order</span>
            <h1>Buy &amp; sell <span class="grad">digital game items</span> safely.</h1>
            <p class="lead">Game accounts, in-game items, gift cards, top-ups and creator assets — delivered instantly, with payment held until you confirm it works.</p>

            <form class="hero-search" action="<?= url('/browse') ?>" method="get">
                <?= icon('search', 20) ?>
                <input type="search" name="q" placeholder="What are you looking for?">
                <button class="btn btn-primary">Search</button>
            </form>

            <div class="hero-stats">
                <div><strong><?= number_format($stats['items']) ?></strong><span>Active listings</span></div>
                <div><strong><?= number_format($stats['users']) ?></strong><span>Members</span></div>
                <div><strong><?= number_format($stats['orders']) ?></strong><span>Orders delivered</span></div>
            </div>
        </div>
        <div class="hero-art" aria-hidden="true">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <?php foreach (array_slice($topItems, 0, 3) as $i => $item): ?>
                <div class="float-card float-<?= $i + 1 ?>">
                    <img src="<?= e(item_image($item['image'])) ?>" alt="">
                    <div>
                        <small><?= e($item['game'] ?: $item['category_name']) ?></small>
                        <strong><?= e(str_limit($item['title'], 26)) ?></strong>
                        <span class="price"><?= money($item['price']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="container section">
    <div class="section-head">
        <h2>Browse by category</h2>
        <a href="<?= url('/browse') ?>" class="link">View all →</a>
    </div>
    <div class="cat-grid">
        <?php foreach ($categories as $cat): ?>
            <a class="cat-tile" href="<?= url('/browse?category=' . $cat['slug']) ?>">
                <span class="cat-icon"><?= icon($cat['icon'], 26) ?></span>
                <strong><?= e($cat['name']) ?></strong>
                <small><?= (int) $cat['item_count'] ?> listings</small>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($games): ?>
<section class="container section">
    <div class="section-head">
        <h2>Popular games</h2>
        <a href="<?= url('/games') ?>" class="link">All games →</a>
    </div>
    <div class="game-strip">
        <?php foreach ($games as $g): ?>
            <a class="game-chip card" href="<?= url('/games/' . $g['id']) ?>">
                <img src="<?= e($g['thumbnail']) ?>" alt="" loading="lazy" data-fallback="<?= asset('assets/img/placeholder.svg') ?>">
                <div><strong><?= e($g['title']) ?></strong><small class="muted"><?= (int) $g['listing_count'] ?> listing<?= $g['listing_count'] == 1 ? '' : 's' ?> · <?= e($g['genre']) ?></small></div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="container section">
    <div class="section-head">
        <h2><?= icon('zap', 22) ?> Top items</h2>
        <a href="<?= url('/browse?sort=popular') ?>" class="link">See more →</a>
    </div>
    <div class="item-grid">
        <?php foreach ($topItems as $item): ?>
            <?= \Core\View::partial('partials/item-card', ['item' => $item]) ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="container section">
    <div class="section-head">
        <h2>Fresh listings</h2>
        <a href="<?= url('/browse?sort=newest') ?>" class="link">See more →</a>
    </div>
    <div class="item-grid">
        <?php foreach ($latest as $item): ?>
            <?= \Core\View::partial('partials/item-card', ['item' => $item]) ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="container section">
    <div class="section-head center"><h2>How it works</h2></div>
    <div class="steps">
        <?php
        $steps = [
            ['user', 'Register', 'Create a free account in seconds.'],
            ['search', 'Choose', 'Find the item you want from verified sellers.'],
            ['cart', 'Check out', 'Pay securely — funds are held by LootVault.'],
            ['zap', 'Instant delivery', 'Codes and files are delivered right after payment.'],
            ['shield', 'Validate', 'Confirm the item works to release payment to the seller.'],
            ['star', 'Review', 'Rate the seller to help other buyers.'],
        ];
        foreach ($steps as $n => [$ic, $name, $text]): ?>
            <div class="step">
                <span class="step-num"><?= $n + 1 ?></span>
                <span class="step-icon"><?= icon($ic, 22) ?></span>
                <strong><?= e($name) ?></strong>
                <p><?= e($text) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container section">
    <div class="trust">
        <div class="trust-copy">
            <h2>Proven &amp; reliable</h2>
            <p class="muted">Every transaction runs through LootVault. Sellers only get paid after you confirm delivery, and our team verifies top sellers.</p>
            <a href="<?= url($authUser ?? null ? '/sell/new' : '/register') ?>" class="btn btn-primary">Start selling</a>
        </div>
        <div class="trust-points">
            <div><?= icon('shield', 22) ?><strong>Escrow-style protection</strong><span>Payment released only after you confirm.</span></div>
            <div><?= icon('check', 22) ?><strong>Verified sellers</strong><span>Look for the green check mark.</span></div>
            <div><?= icon('globe', 22) ?><strong>Real game catalog</strong><span>Game info synced from FreeToGame.com.</span></div>
            <div><?= icon('lock', 22) ?><strong>Secure downloads</strong><span>Files are only available to the buyer.</span></div>
        </div>
    </div>
</section>
