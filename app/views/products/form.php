<?php $isEdit = $product !== null; ?>
<div class="page-head">
    <h1><?= $isEdit ? 'Editar producto' : 'Nuevo producto' ?></h1>
</div>

<form class="card form" method="post" action="<?= $isEdit ? '/products/' . (int) $product['id'] : '/products' ?>" novalidate>
    <?= csrf_field() ?>

    <div class="grid">
        <label>SKU
            <input type="text" name="sku" value="<?= e($data['sku']) ?>" maxlength="40" required>
            <?= field_error($errors, 'sku') ?>
        </label>

        <label>Nombre
            <input type="text" name="name" value="<?= e($data['name']) ?>" maxlength="120" required>
            <?= field_error($errors, 'name') ?>
        </label>

        <label>Categoría
            <select name="category_id">
                <option value="">Sin categoría</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (string) $data['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>…o crear una nueva
            <input type="text" name="new_category" value="<?= e($data['new_category']) ?>" maxlength="60" placeholder="Opcional">
            <?= field_error($errors, 'new_category') ?>
        </label>

        <label>Precio (COP)
            <input type="text" name="price" value="<?= e($data['price']) ?>" inputmode="decimal" required>
            <?= field_error($errors, 'price') ?>
        </label>

        <label>Stock
            <input type="number" name="stock" value="<?= e($data['stock']) ?>" min="0" step="1" required>
            <?= field_error($errors, 'stock') ?>
        </label>

        <label>Stock mínimo (alerta)
            <input type="number" name="min_stock" value="<?= e($data['min_stock']) ?>" min="0" step="1" required>
            <?= field_error($errors, 'min_stock') ?>
        </label>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn"><?= $isEdit ? 'Guardar cambios' : 'Crear producto' ?></button>
        <a href="/products">Cancelar</a>
    </div>
</form>
