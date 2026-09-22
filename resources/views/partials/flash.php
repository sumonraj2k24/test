<?php foreach (['success' => 'flash-success', 'error' => 'flash-error'] as $key => $class): ?>
    <?php if ($message = \App\Core\Session::flashed($key)): ?>
        <div class="flash <?= $class ?>"><?= e($message) ?></div>
    <?php endif; ?>
<?php endforeach; ?>
