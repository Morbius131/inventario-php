<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Product;

final class ProductController
{
    public function __construct()
    {
        Auth::guard();
    }

    public function home(): void
    {
        View::redirect('/products');
    }

    public function index(): void
    {
        [$q, $category] = $this->filters();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Product::paginate($q, $category, $page);

        View::render('products/index', [
            'title' => 'Productos',
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'stats' => Product::stats(),
            'categories' => Product::categories(),
            'q' => $q,
            'cat' => $category,
        ]);
    }

    public function create(): void
    {
        $this->form(null, [
            'sku' => '', 'name' => '', 'category_id' => '', 'new_category' => '',
            'price' => '', 'stock' => '0', 'min_stock' => '5',
        ], []);
    }

    public function store(): void
    {
        Csrf::verify();
        [$data, $errors] = $this->validate($_POST, null);

        if ($errors) {
            http_response_code(422);
            $this->form(null, $data, $errors);

            return;
        }

        $data['category_id'] = Product::resolveCategory($data['category_id'], $data['new_category']);
        Product::create($data);
        flash_set('success', 'Producto creado correctamente.');
        View::redirect('/products');
    }

    public function edit(string $id): void
    {
        $product = $this->findOr404((int) $id);
        $price = rtrim(rtrim(number_format($product['price_cents'] / 100, 2, '.', ''), '0'), '.');

        $this->form($product, [
            'sku' => $product['sku'],
            'name' => $product['name'],
            'category_id' => (string) ($product['category_id'] ?? ''),
            'new_category' => '',
            'price' => $price,
            'stock' => (string) $product['stock'],
            'min_stock' => (string) $product['min_stock'],
        ], []);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $product = $this->findOr404((int) $id);
        [$data, $errors] = $this->validate($_POST, (int) $product['id']);

        if ($errors) {
            http_response_code(422);
            $this->form($product, $data, $errors);

            return;
        }

        $data['category_id'] = Product::resolveCategory($data['category_id'], $data['new_category']);
        Product::update((int) $product['id'], $data);
        flash_set('success', 'Producto actualizado.');
        View::redirect('/products');
    }

    public function destroy(string $id): void
    {
        Csrf::verify();
        $product = $this->findOr404((int) $id);
        Product::delete((int) $product['id']);
        flash_set('success', 'Producto «' . $product['name'] . '» eliminado.');
        View::redirect('/products');
    }

    public function export(): void
    {
        [$q, $category] = $this->filters();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="inventario-' . date('Ymd-His') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel respete las tildes
        fputcsv($out, ['SKU', 'Nombre', 'Categoría', 'Precio', 'Stock', 'Stock mínimo', 'Valor total'], ',', '"', '');

        foreach (Product::export($q, $category) as $p) {
            fputcsv($out, [
                self::csvSafe($p['sku']),
                self::csvSafe($p['name']),
                self::csvSafe((string) $p['category']),
                number_format($p['price_cents'] / 100, 2, '.', ''),
                $p['stock'],
                $p['min_stock'],
                number_format($p['stock'] * $p['price_cents'] / 100, 2, '.', ''),
            ], ',', '"', '');
        }
        fclose($out);
    }

    /** @return array{0:string,1:?int} */
    private function filters(): array
    {
        $q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
        $raw = $_GET['category'] ?? '';
        $category = is_string($raw) && ctype_digit($raw) ? (int) $raw : null;

        return [$q, $category];
    }

    private function findOr404(int $id): array
    {
        $product = Product::find($id);
        if ($product === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'No encontrado']);
            exit;
        }

        return $product;
    }

    private function form(?array $product, array $data, array $errors): void
    {
        View::render('products/form', [
            'title' => $product ? 'Editar producto' : 'Nuevo producto',
            'product' => $product,
            'data' => $data,
            'errors' => $errors,
            'categories' => Product::categories(),
        ]);
    }

    /**
     * Valida y normaliza la entrada del formulario.
     *
     * @return array{0:array<string,mixed>,1:array<string,string>}
     */
    private function validate(array $in, ?int $id): array
    {
        $errors = [];
        $str = static fn (string $k): string => is_string($in[$k] ?? null) ? trim($in[$k]) : '';

        $sku = strtoupper($str('sku'));
        $name = $str('name');
        $newCategory = $str('new_category');
        $categoryRaw = $str('category_id');
        $priceRaw = str_replace([' ', ','], ['', '.'], $str('price'));
        $stockRaw = $str('stock');
        $minRaw = $str('min_stock');

        if ($sku === '') {
            $errors['sku'] = 'El SKU es obligatorio.';
        } elseif (!preg_match('/^[A-Z0-9._-]{1,40}$/', $sku)) {
            $errors['sku'] = 'Usa solo letras, números, punto, guion y guion bajo (máx. 40).';
        } elseif (Product::skuExists($sku, $id)) {
            $errors['sku'] = 'Ya existe un producto con ese SKU.';
        }

        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'El nombre es obligatorio (máx. 120 caracteres).';
        }

        if (mb_strlen($newCategory) > 60) {
            $errors['new_category'] = 'La categoría admite máx. 60 caracteres.';
        }

        $priceCents = 0;
        if ($priceRaw === '' || !is_numeric($priceRaw) || (float) $priceRaw < 0 || (float) $priceRaw > 999999999) {
            $errors['price'] = 'Ingresa un precio válido (≥ 0). Ej: 12500 o 12500.50';
        } else {
            $priceCents = (int) round((float) $priceRaw * 100);
        }

        $stock = filter_var($stockRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]);
        if ($stock === false) {
            $errors['stock'] = 'El stock debe ser un entero ≥ 0.';
        }

        $minStock = filter_var($minRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]);
        if ($minStock === false) {
            $errors['min_stock'] = 'El stock mínimo debe ser un entero ≥ 0.';
        }

        $data = [
            'sku' => $sku,
            'name' => $name,
            'category_id' => ctype_digit($categoryRaw) && $categoryRaw !== '' ? (int) $categoryRaw : null,
            'new_category' => $newCategory,
            'price' => $str('price'),
            'price_cents' => $priceCents,
            'stock' => $stock === false ? $stockRaw : $stock,
            'min_stock' => $minStock === false ? $minRaw : $minStock,
        ];

        // Para repoblar el formulario, category_id debe ser string.
        if ($errors) {
            $data['category_id'] = $categoryRaw;
        }

        return [$data, $errors];
    }

    /** Evita inyección de fórmulas al abrir el CSV en Excel/Sheets. */
    private static function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}
