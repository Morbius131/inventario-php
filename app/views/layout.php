<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Inventario') ?> · Inventario</title>
    <link rel="stylesheet" href="/assets/style.css">
    <script src="/assets/app.js" defer></script>
</head>
<body>
<?php if ($user = auth_user()): ?>
    <header class="topbar">
        <a class="brand" href="/products">Inventario</a>
        <form method="post" action="/logout" class="topbar-user">
            <?= csrf_field() ?>
            <span><?= e($user['name']) ?></span>
            <button type="submit" class="btn btn-ghost btn-sm">Cerrar sesión</button>
        </form>
    </header>
<?php endif; ?>
<main class="container">
    <?php foreach (flash_get() as [$type, $message]): ?>
        <div class="alert alert-<?= e($type) ?>" role="status"><?= e($message) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
</main>
</body>
</html>
