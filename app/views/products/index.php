<div class="page-head">
    <h1>Productos</h1>
    <div class="actions">
        <a class="btn btn-ghost" href="<?= e(url('/products/export', ['q' => $q, 'category' => $cat])) ?>">Exportar CSV</a>
        <a class="btn" href="/products/create">Nuevo producto</a>
    </div>
</div>

<section class="stats">
    <div class="stat"><span class="stat-label">Productos</span><strong><?= (int) $stats['total'] ?></strong></div>
    <div class="stat"><span class="stat-label">Unidades en stock</span><strong><?= number_format($stats['units'], 0, ',', '.') ?></strong></div>
    <div class="stat"><span class="stat-label">Valor del inventario</span><strong><?= e(money($stats['value_cents'])) ?></strong></div>
    <div class="stat <?= $stats['low'] > 0 ? 'stat-warn' : '' ?>"><span class="stat-label">Con stock bajo</span><strong><?= (int) $stats['low'] ?></strong></div>
</section>

<form class="filters" method="get" action="/products">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o SKU" aria-label="Buscar">
    <select name="category" aria-label="Categoría">
        <option value="">Todas las categorías</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filtrar</button>
    <?php if ($q !== '' || $cat): ?>
        <a href="/products">Limpiar</a>
    <?php endif; ?>
</form>

<div class="card table-wrap">
    <table>
        <thead>
        <tr>
            <th>SKU</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <th class="num">Precio</th>
            <th class="num">Stock</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$items): ?>
            <tr><td colspan="6" class="empty">No hay productos que coincidan con la búsqueda.</td></tr>
        <?php endif; ?>
        <?php foreach ($items as $p): ?>
            <?php $low = $p['stock'] <= $p['min_stock']; ?>
            <tr>
                <td><code><?= e($p['sku']) ?></code></td>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['category'] ?? '—') ?></td>
                <td class="num"><?= e(money((int) $p['price_cents'])) ?></td>
                <td class="num">
                    <span class="badge <?= $low ? 'badge-warn' : 'badge-ok' ?>"><?= (int) $p['stock'] ?></span>
                </td>
                <td class="row-actions">
                    <a href="/products/<?= (int) $p['id'] ?>/edit">Editar</a>
                    <form method="post" action="/products/<?= (int) $p['id'] ?>/delete"
                          data-confirm="¿Eliminar «<?= e($p['name']) ?>»? Esta acción no se puede deshacer.">
                        <?= csrf_field() ?>
                        <button type="submit" class="link-danger">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Paginación">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="<?= $i === $page ? 'active' : '' ?>"
               href="<?= e(url('/products', ['q' => $q, 'category' => $cat, 'page' => $i > 1 ? $i : null])) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
<p class="hint"><?= (int) $total ?> resultado(s)</p>
