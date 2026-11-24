<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);

        return $stmt->fetch() ?: null;
    }
}
