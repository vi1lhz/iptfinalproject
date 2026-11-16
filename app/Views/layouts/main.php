<?php
/** @var string $content  the rendered page */
$appName = config('app.name');
// Error pages can render before ShareViewData ran, so fall back gracefully.
$authUser = $authUser ?? (function () { try { return \Core\Auth::user(); } catch (\Throwable) { return null; } })();
$navCategories = $navCategories ?? (function () { try { return \App\Models\Category::all('id ASC'); } catch (\Throwable) { return []; } })();
$currentPath = $currentPath ?? '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Marketplace') ?> · <?= e($appName) ?></title>
    <link rel="icon" href="<?= asset('assets/img/logo.svg') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body>

<header class="topbar">
    <div class="container topbar-inner">
        <a href="<?= url('/') ?>" class="logo">
            <span class="logo-mark"><?= icon('bolt', 20) ?></span>
            <span class="logo-text">Loot<span>Vault</span></span>
        </a>

        <form class="search" action="<?= url('/browse') ?>" method="get" role="search">
            <?= icon('search') ?>
            <input type="search" name="q" placeholder="Search game accounts, items, gift cards…" value="<?= e($_GET['q'] ?? '') ?>">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
        </form>

        <nav class="top-actions">
            <?php if ($authUser): ?>
                <a href="<?= url('/sell/new') ?>" class="btn btn-outline btn-sm hide-sm"><?= icon('plus', 16) ?> Sell</a>
                <a href="<?= url('/cart') ?>" class="icon-btn" aria-label="Cart">
                    <?= icon('cart', 20) ?>
                    <?php if (($cartCount ?? 0) > 0): ?><span class="dot"><?= (int) $cartCount ?></span><?php endif; ?>
                </a>
                <div class="dropdown">
                    <button class="user-btn" type="button" data-dropdown>
                        <?= avatar($authUser['username'], 'sm') ?>
                        <span class="hide-sm"><?= e($authUser['username']) ?></span>
                    </button>
                    <div class="dropdown-menu right">
                        <div class="dropdown-head">
                            <strong><?= e($authUser['username']) ?></strong>
                            <small>Balance: <?= money($authUser['balance']) ?></small>
                        </div>
                        <a href="<?= url('/orders') ?>"><?= icon('box', 16) ?> My orders</a>
                        <a href="<?= url('/sell') ?>"><?= icon('store', 16) ?> Seller dashboard</a>
                        <a href="<?= url('/sellers/' . $authUser['username']) ?>"><?= icon('user', 16) ?> My public profile</a>
                        <?php if ($authUser['role'] === 'admin'): ?>
                            <a href="<?= url('/admin') ?>"><?= icon('settings', 16) ?> Admin panel</a>
                        <?php endif; ?>
                        <form action="<?= url('/logout') ?>" method="post">
                            <?= csrf_field() ?>
                            <button type="submit"><?= icon('logout', 16) ?> Log out</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= url('/login') ?>" class="btn btn-ghost btn-sm">Log in</a>
                <a href="<?= url('/register') ?>" class="btn btn-primary btn-sm">Register</a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="catbar">
        <div class="container catbar-inner">
            <a href="<?= url('/browse') ?>" class="<?= $currentPath === '/browse' && empty($_GET['category']) ? 'active' : '' ?>"><?= icon('menu', 16) ?> All items</a>
            <a href="<?= url('/games') ?>" class="<?= str_starts_with($currentPath, '/games') ? 'active' : '' ?>"><?= icon('globe', 16) ?> Games</a>
            <?php foreach ($navCategories as $cat): ?>
                <a href="<?= url('/browse?category=' . $cat['slug']) ?>" class="<?= ($_GET['category'] ?? '') === $cat['slug'] ? 'active' : '' ?>">
                    <?= icon($cat['icon'], 16) ?> <?= e($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</header>

<?= \Core\View::partial('partials/flash') ?>

<main>
    <?= $content ?>
</main>

<footer class="footer">
    <div class="container footer-grid">
        <div>
            <a href="<?= url('/') ?>" class="logo">
                <span class="logo-mark"><?= icon('bolt', 20) ?></span>
                <span class="logo-text">Loot<span>Vault</span></span>
            </a>
            <p class="muted">A peer-to-peer marketplace for digital game items, accounts, gift cards and creative assets. Built on a custom PHP MVC framework for IPT10.</p>
        </div>
        <div>
            <h4>Marketplace</h4>
            <a href="<?= url('/browse') ?>">Browse all</a>
            <a href="<?= url('/browse?sort=popular') ?>">Top sellers</a>
            <a href="<?= url('/browse?sort=newest') ?>">New listings</a>
            <a href="<?= url('/games') ?>">Games catalog</a>
        </div>
        <div>
            <h4>Sell</h4>
            <a href="<?= url('/sell/new') ?>">List an item</a>
            <a href="<?= url('/sell') ?>">Seller dashboard</a>
        </div>
        <div>
            <h4>Developers</h4>
            <a href="<?= url('/api/items') ?>">JSON API: items</a>
            <a href="<?= url('/api/games') ?>">JSON API: games</a>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> <?= e($appName) ?> · School project – all prices in ₱, payments are simulated.</span>
        <span class="muted">Game data provided by <a href="https://www.freetogame.com" target="_blank" rel="noopener">FreeToGame.com</a></span>
    </div>
</footer>

<script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
