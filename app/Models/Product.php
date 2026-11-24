<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Product
{
    public const PER_PAGE = 10;

    /**
     * @return array{items:list<array>,total:int,page:int,pages:int}
     */
    public static function paginate(string $q, ?int $categoryId, int $page, int $perPage = self::PER_PAGE): array
    {
        [$where, $params] = self::filters($q, $categoryId);
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products p $where");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        $stmt = $pdo->prepare(
            "SELECT p.*, c.name AS category
               FROM products p
          LEFT JOIN categories c ON c.id = p.category_id
              $where
           ORDER BY p.name COLLATE NOCASE
              LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /** @return list<array> todos los productos que cumplen el filtro (sin paginar). */
    public static function export(string $q, ?int $categoryId): array
    {
        [$where, $params] = self::filters($q, $categoryId);
        $stmt = Database::pdo()->prepare(
            "SELECT p.*, c.name AS category
               FROM products p
          LEFT JOIN categories c ON c.id = p.category_id
              $where
           ORDER BY p.name COLLATE NOCASE"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array{total:int,units:int,value_cents:int,low:int} */
    public static function stats(): array
    {
        $row = Database::pdo()->query(
            'SELECT COUNT(*)                                          AS total,
                    COALESCE(SUM(stock), 0)                           AS units,
                    COALESCE(SUM(stock * price_cents), 0)             AS value_cents,
                    COALESCE(SUM(CASE WHEN stock <= min_stock THEN 1 ELSE 0 END), 0) AS low
               FROM products'
        )->fetch();

        return array_map('intval', $row);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function skuExists(string $sku, ?int $exceptId = null): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM products WHERE sku = :sku AND id != :id');
        $stmt->execute([':sku' => $sku, ':id' => $exceptId ?? 0]);

        return (bool) $stmt->fetchColumn();
    }

    public static function create(array $d): int
    {
        $pdo = Database::pdo();
        $pdo->prepare(
            'INSERT INTO products (sku, name, category_id, price_cents, stock, min_stock)
             VALUES (:sku, :name, :category_id, :price_cents, :stock, :min_stock)'
        )->execute(self::bindings($d));

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::pdo()->prepare(
            'UPDATE products
                SET sku = :sku, name = :name, category_id = :category_id,
                    price_cents = :price_cents, stock = :stock, min_stock = :min_stock,
                    updated_at = CURRENT_TIMESTAMP
              WHERE id = :id'
        )->execute(self::bindings($d) + [':id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM products WHERE id = :id')->execute([':id' => $id]);
    }

    /** @return list<array{id:int,name:string}> */
    public static function categories(): array
    {
        return Database::pdo()->query('SELECT id, name FROM categories ORDER BY name COLLATE NOCASE')->fetchAll();
    }

    /** Devuelve el id de la categoría: crea una nueva si se escribió un nombre, o valida la existente. */
    public static function resolveCategory(?int $categoryId, string $newName): ?int
    {
        $pdo = Database::pdo();

        if ($newName !== '') {
            $pdo->prepare('INSERT OR IGNORE INTO categories (name) VALUES (:name)')->execute([':name' => $newName]);
            $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = :name COLLATE NOCASE');
            $stmt->execute([':name' => $newName]);

            return (int) $stmt->fetchColumn();
        }

        if ($categoryId === null) {
            return null;
        }

        $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = :id');
        $stmt->execute([':id' => $categoryId]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /** @return array{0:string,1:array<string,string>} */
    private static function filters(string $q, ?int $categoryId): array
    {
        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = "(p.name LIKE :q ESCAPE '\\' OR p.sku LIKE :q ESCAPE '\\')";
            $params[':q'] = '%' . addcslashes($q, '%_\\') . '%';
        }
        if ($categoryId) {
            $where[] = 'p.category_id = :category';
            $params[':category'] = (string) $categoryId;
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private static function bindings(array $d): array
    {
        return [
            ':sku' => $d['sku'],
            ':name' => $d['name'],
            ':category_id' => $d['category_id'],
            ':price_cents' => $d['price_cents'],
            ':stock' => $d['stock'],
            ':min_stock' => $d['min_stock'],
        ];
    }
}
