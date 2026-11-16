<?php $message = \Core\Session::getFlash('message'); ?>
<?php if ($message): ?>
    <div class="container">
        <div class="flash flash-<?= e($message['type']) ?>" data-flash>
            <?= icon($message['type'] === 'success' ? 'check' : ($message['type'] === 'error' ? 'shield' : 'bolt'), 18) ?>
            <span><?= e($message['text']) ?></span>
            <button type="button" class="flash-close" aria-label="Dismiss" data-dismiss>×</button>
        </div>
    </div>
<?php endif; ?>
