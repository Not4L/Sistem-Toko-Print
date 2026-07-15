<?php
/**
 * Reusable component untuk 1 field form (label + input + pesan error).
 * Dipakai bareng di create.php dan edit.php supaya markup tidak diulang (DRY).
 *
 * Cara pakai:
 *   renderInput([
 *       'name'  => 'nama_jenis',
 *       'label' => 'Nama Jenis Kertas',
 *       'type'  => 'text',
 *       'value' => $old['nama_jenis'] ?? '',
 *       'error' => $errors['nama_jenis'] ?? null,
 *       'attrs' => 'required maxlength="50"',
 *   ]);
 */
function renderInput(array $field): void
{
    $name  = e($field['name']);
    $label = e($field['label']);
    $type  = e($field['type'] ?? 'text');
    $value = e($field['value'] ?? '');
    $error = $field['error'] ?? null;
    $attrs = $field['attrs'] ?? '';
    ?>
    <div class="form-group <?= $error ? 'has-error' : '' ?>">
        <label for="field-<?= $name ?>"><?= $label ?></label>
        <input
            type="<?= $type ?>"
            id="field-<?= $name ?>"
            name="<?= $name ?>"
            value="<?= $value ?>"
            <?= $attrs ?>
        >
        <?php if ($error): ?>
            <small class="form-error"><?= e($error) ?></small>
        <?php endif; ?>
    </div>
    <?php
}
