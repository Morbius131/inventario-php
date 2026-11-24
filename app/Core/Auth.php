<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        // Se verifica siempre contra un hash para que el tiempo de respuesta no revele si el correo existe.
        $hash = $user['password_hash'] ?? password_hash('no-existe', PASSWORD_DEFAULT);
        $valid = password_verify($password, $hash);

        if ($user === null || !$valid) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['name']];

        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Redirige al login si no hay sesión iniciada. */
    public static function guard(): void
    {
        if (!self::check()) {
            View::redirect('/login');
        }
    }
}
