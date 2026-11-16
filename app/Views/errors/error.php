<section class="container error-page">
    <div class="error-code"><?= (int) ($status ?? 500) ?></div>
    <h1><?= e($status === 403 ? 'Access denied' : ($status === 404 ? 'Not found' : ($status === 419 ? 'Session expired' : 'Something went wrong'))) ?></h1>
    <p class="muted"><?= e($message ?? '') ?></p>
    <?php if (!empty($trace)): ?>
        <pre class="trace"><?= e($trace) ?></pre>
    <?php endif; ?>
    <a href="<?= url('/') ?>" class="btn btn-primary">Back to the marketplace</a>
</section>
