<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function path(): string
    {
        return getenv('INVENTARIO_DB') ?: BASE_PATH . '/database/inventario.sqlite';
    }

    /** Devuelve la conexión PDO; crea el esquema y los datos demo la primera vez. */
    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $path = self::path();
            $isNew = !is_file($path);

            $pdo = new PDO('sqlite:' . $path, options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo = $pdo;

            if ($isNew) {
                self::migrate($pdo);
                self::seed($pdo);
            }
        }

        return self::$pdo;
    }

    public static function migrate(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
        CREATE TABLE users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT NOT NULL,
            email         TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE categories (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        );

        CREATE TABLE products (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            sku         TEXT NOT NULL UNIQUE,
            name        TEXT NOT NULL,
            category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
            price_cents INTEGER NOT NULL CHECK (price_cents >= 0),
            stock       INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0),
            min_stock   INTEGER NOT NULL DEFAULT 5 CHECK (min_stock >= 0),
            created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );

        CREATE INDEX idx_products_category ON products(category_id);
        CREATE INDEX idx_products_name ON products(name);
        SQL);
    }

    private static function seed(PDO $pdo): void
    {
        $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)')
            ->execute(['Administrador', 'admin@demo.com', password_hash('admin123', PASSWORD_DEFAULT)]);

        $categoryIds = [];
        $insertCategory = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
        foreach (['Tecnología', 'Oficina', 'Papelería', 'Hogar'] as $name) {
            $insertCategory->execute([$name]);
            $categoryIds[$name] = (int) $pdo->lastInsertId();
        }

        $products = [
            ['TEC-001', 'Teclado mecánico 60%', 'Tecnología', 289000, 14, 5],
            ['TEC-002', 'Mouse inalámbrico', 'Tecnología', 69000, 32, 10],
            ['TEC-003', 'Monitor 24" Full HD', 'Tecnología', 629000, 6, 5],
            ['TEC-004', 'Cable HDMI 2 m', 'Tecnología', 18000, 3, 10],
            ['TEC-005', 'Hub USB-C 7 en 1', 'Tecnología', 139000, 11, 4],
            ['OFI-001', 'Silla ergonómica', 'Oficina', 519000, 4, 3],
            ['OFI-002', 'Escritorio 120 cm', 'Oficina', 389000, 7, 2],
            ['OFI-003', 'Lámpara LED de escritorio', 'Oficina', 59000, 18, 6],
            ['PAP-001', 'Cuaderno A4 cuadriculado', 'Papelería', 9500, 120, 40],
            ['PAP-002', 'Bolígrafos x12', 'Papelería', 14000, 8, 15],
            ['PAP-003', 'Resma papel carta 500 h', 'Papelería', 21000, 45, 20],
            ['HOG-001', 'Termo de acero 750 ml', 'Hogar', 42000, 0, 5],
        ];

        $insertProduct = $pdo->prepare(
            'INSERT INTO products (sku, name, category_id, price_cents, stock, min_stock) VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($products as [$sku, $name, $category, $price, $stock, $minStock]) {
            $insertProduct->execute([$sku, $name, $categoryIds[$category], $price * 100, $stock, $minStock]);
        }
    }
}
