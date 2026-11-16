<section class="container page">
    <div class="page-head"><div><h1>Users</h1><p class="muted"><?= count($users) ?> registered</p></div></div>
    <?= \Core\View::partial('admin/_nav', ['active' => 'users']) ?>
    <div class="card">
        <table class="table">
            <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Balance</th><th>Joined</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td class="cell-item"><?= avatar($u['username'], 'sm') ?> <a href="<?= url('/sellers/' . $u['username']) ?>"><?= e($u['username']) ?></a>
                        <?php if ($u['is_verified']): ?><span class="verified"><?= icon('check', 11) ?></span><?php endif; ?></td>
                    <td class="muted"><?= e($u['email']) ?></td>
                    <td><?= e(ucfirst($u['role'])) ?></td>
                    <td><?= money($u['balance']) ?></td>
                    <td class="muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td><?= $u['is_banned'] ? '<span class="badge badge-removed">Banned</span>' : '<span class="badge badge-active">Active</span>' ?></td>
                    <td class="actions">
                        <form action="<?= url('/admin/users/' . $u['id'] . '/verify') ?>" method="post"><?= csrf_field() ?>
                            <button class="btn btn-sm btn-ghost"><?= $u['is_verified'] ? 'Unverify' : 'Verify' ?></button></form>
                        <?php if ($u['role'] !== 'admin'): ?>
                            <form action="<?= url('/admin/users/' . $u['id'] . '/ban') ?>" method="post" data-confirm="Change ban status for <?= e($u['username']) ?>?"><?= csrf_field() ?>
                                <button class="btn btn-sm <?= $u['is_banned'] ? 'btn-outline' : 'btn-danger' ?>"><?= $u['is_banned'] ? 'Unban' : 'Ban' ?></button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
