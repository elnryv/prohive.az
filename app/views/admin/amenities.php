<?php
/** @var array<int,array<string,mixed>> $amenities */
/** @var string[] $errors */

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editing = null;
if ($editId !== null) {
    foreach ($amenities as $a) {
        if ((int) $a['id'] === $editId) {
            $editing = $a;
            break;
        }
    }
}
$formAction = $editing !== null ? '/amenities/' . $editing['id'] : '/amenities';
?>
<section class="admin-amenities">
    <h1>Şəraitlər</h1>

    <?php if ($errors !== []): ?>
        <ul class="form-errors"><?php foreach ($errors as $e): ?><li><?= View::e($e) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <?php if (isset($_GET['saved'])): ?><p class="notice notice--success">Yadda saxlanıldı.</p><?php endif; ?>
    <?php if (isset($_GET['silindi'])): ?><p class="notice notice--success">Şərait silindi.</p><?php endif; ?>
    <?php if (isset($_GET['xeta']) && $_GET['xeta'] === 'istifade_olunur'): ?><p class="notice notice--warn">Bu şərait evlərdə istifadə olunur, silinə bilmədi.</p><?php endif; ?>

    <table class="admin-table">
        <thead><tr><th>İkon</th><th>Ad (AZ)</th><th>Sıra</th><th>Aktiv</th><th>İstifadə</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($amenities as $a): ?>
                <tr>
                    <td><?= View::e($a['icon']) ?></td>
                    <td><?= View::e($a['name_az']) ?></td>
                    <td><?= (int) $a['sort_order'] ?></td>
                    <td><?= $a['is_active'] ? 'Bəli' : 'Xeyr' ?></td>
                    <td><?= (int) $a['usage_count'] ?></td>
                    <td>
                        <a class="btn" href="/amenities?edit=<?= (int) $a['id'] ?>">Redaktə</a>
                        <form method="post" action="/amenities/<?= (int) $a['id'] ?>/sil" class="admin-inline-form" onsubmit="return confirm('Silinsin?');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn--danger">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2><?= $editing !== null ? 'Şəraiti redaktə et' : 'Yeni şərait' ?></h2>
    <form method="post" action="<?= View::e($formAction) ?>" class="admin-region-form">
        <?= Csrf::field() ?>
        <label>İkon (emoji) <input type="text" name="icon" value="<?= View::e($editing['icon'] ?? '') ?>" required></label>
        <label>Ad (AZ) <input type="text" name="name_az" value="<?= View::e($editing['name_az'] ?? '') ?>" required></label>
        <label>Ad (RU) <input type="text" name="name_ru" value="<?= View::e($editing['name_ru'] ?? '') ?>" required></label>
        <label>Ad (EN) <input type="text" name="name_en" value="<?= View::e($editing['name_en'] ?? '') ?>" required></label>
        <label>Sıra <input type="number" name="sort_order" value="<?= View::e((string) ($editing['sort_order'] ?? 0)) ?>"></label>
        <label class="checkbox-field">
            <input type="checkbox" name="is_active" <?= ($editing === null || $editing['is_active']) ? 'checked' : '' ?>>
            <span>Aktiv</span>
        </label>
        <button type="submit" class="btn btn--primary"><?= $editing !== null ? 'Yadda saxla' : 'Əlavə et' ?></button>
        <?php if ($editing !== null): ?><a class="btn" href="/amenities">Ləğv et</a><?php endif; ?>
    </form>
</section>
