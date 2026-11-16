<?php $active = $active ?? ''; ?>
<nav class="admin-nav">
    <a href="<?= url('/admin') ?>" class="<?= $active === 'dashboard' ? 'active' : '' ?>"><?= icon('chart', 16) ?> Dashboard</a>
    <a href="<?= url('/admin/users') ?>" class="<?= $active === 'users' ? 'active' : '' ?>"><?= icon('user', 16) ?> Users</a>
    <a href="<?= url('/admin/items') ?>" class="<?= $active === 'items' ? 'active' : '' ?>"><?= icon('box', 16) ?> Listings</a>
    <a href="<?= url('/admin/categories') ?>" class="<?= $active === 'categories' ? 'active' : '' ?>"><?= icon('menu', 16) ?> Categories</a>
    <a href="<?= url('/admin/orders') ?>" class="<?= $active === 'orders' ? 'active' : '' ?>"><?= icon('cart', 16) ?> Orders</a>
</nav>
