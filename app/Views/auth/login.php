<section class="auth">
    <div class="auth-card card">
        <a href="<?= url('/') ?>" class="logo center"><span class="logo-mark"><?= icon('bolt', 20) ?></span><span class="logo-text">Loot<span>Vault</span></span></a>
        <h1>Welcome back</h1>
        <p class="muted">Log in to buy, sell and track your orders.</p>

        <form action="<?= url('/login') ?>" method="post" class="form">
            <?= csrf_field() ?>
            <label>Email
                <input type="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
                <?php if ($err = error('email')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>
            <label>Password
                <input type="password" name="password" required>
                <?php if ($err = error('password')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>
            <button class="btn btn-primary btn-block">Log in</button>
        </form>
        <p class="muted center small">No account yet? <a href="<?= url('/register') ?>" class="link">Register for free</a></p>
        <div class="demo-accounts">
            <strong>Demo accounts</strong> (password: <code>password</code>)<br>
            admin@lootvault.test · seller@lootvault.test · buyer@lootvault.test
        </div>
    </div>
</section>
