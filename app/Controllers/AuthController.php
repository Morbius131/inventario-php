<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;

final class AuthController
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 60;

    public function showLogin(): void
    {
        if (Auth::check()) {
            View::redirect('/products');
        }

        View::render('auth/login', ['title' => 'Iniciar sesión']);
    }

    public function login(): void
    {
        Csrf::verify();

        $email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        $lockedUntil = (int) ($_SESSION['_login_lock'] ?? 0);
        if ($lockedUntil > time()) {
            $this->fail('Demasiados intentos. Espera ' . ($lockedUntil - time()) . ' s e inténtalo de nuevo.', $email, 429);
        }

        if (Auth::attempt($email, $password)) {
            unset($_SESSION['_login_fails'], $_SESSION['_login_lock']);
            flash_set('success', 'Bienvenido, ' . Auth::user()['name'] . '.');
            View::redirect('/products');
        }

        $fails = (int) ($_SESSION['_login_fails'] ?? 0) + 1;
        $_SESSION['_login_fails'] = $fails;
        if ($fails >= self::MAX_ATTEMPTS) {
            $_SESSION['_login_lock'] = time() + self::LOCK_SECONDS;
            $_SESSION['_login_fails'] = 0;
        }

        $this->fail('Correo o contraseña incorrectos.', $email, 401);
    }

    public function logout(): void
    {
        Csrf::verify();
        Auth::logout();
        View::redirect('/login');
    }

    private function fail(string $message, string $email, int $status): never
    {
        http_response_code($status);
        View::render('auth/login', ['title' => 'Iniciar sesión', 'error' => $message, 'email' => $email]);
        exit;
    }
}
