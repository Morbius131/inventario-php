<section class="card auth-card">
    <h1>Iniciar sesión</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/login" novalidate>
        <?= csrf_field() ?>
        <label>Correo
            <input type="email" name="email" value="<?= e($email ?? '') ?>" required autofocus autocomplete="username">
        </label>
        <label>Contraseña
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn btn-block">Entrar</button>
    </form>

    <p class="hint">Demo: <code>admin@demo.com</code> / <code>admin123</code></p>
</section>
