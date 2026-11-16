<section class="auth">
    <div class="auth-card card">
        <a href="<?= url('/') ?>" class="logo center"><span class="logo-mark"><?= icon('bolt', 20) ?></span><span class="logo-text">Loot<span>Vault</span></span></a>
        <h1>Create your account</h1>
        <p class="muted">Register for free to buy and sell digital items.</p>

        <form action="<?= url('/register') ?>" method="post" class="form">
            <?= csrf_field() ?>
            <label>Username
                <input type="text" name="username" value="<?= e(old('username')) ?>" required minlength="3" maxlength="30">
                <?php if ($err = error('username')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>
            <label>Email
                <input type="email" name="email" value="<?= e(old('email')) ?>" required>
                <?php if ($err = error('email')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>
            <label>Password
                <input type="password" name="password" required minlength="8">
                <?php if ($err = error('password')): ?><small class="field-error"><?= e($err) ?></small><?php endif; ?>
            </label>
            <label>Confirm password
                <input type="password" name="password_confirmation" required>
            </label>
            <button class="btn btn-primary btn-block">Create account</button>
        </form>
        <p class="muted center small">Already registered? <a href="<?= url('/login') ?>" class="link">Log in</a></p>
    </div>
</section>
