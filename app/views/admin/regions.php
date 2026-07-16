<?php
/** @var array<int,array<string,mixed>> $regions */
/** @var string[] $errors */

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editing = null;
if ($editId !== null) {
    foreach ($regions as $r) {
        if ((int) $r['id'] === $editId) {
            $editing = $r;
            break;
        }
    }
}
$formAction = $editing !== null ? '/regions/' . $editing['id'] : '/regions';
?>
<section class="admin-regions">
    <h1>Bölgələr</h1>

    <?php if ($errors !== []): ?>
        <ul class="form-errors"><?php foreach ($errors as $e): ?><li><?= View::e($e) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <?php if (isset($_GET['saved'])): ?><p class="notice notice--success">Yadda saxlanıldı.</p><?php endif; ?>
    <?php if (isset($_GET['silindi'])): ?><p class="notice notice--success">Bölgə silindi.</p><?php endif; ?>
    <?php if (isset($_GET['xeta']) && $_GET['xeta'] === 'bagli_ev'): ?><p class="notice notice--warn">Bu bölgəyə bağlı evlər var, silinə bilmədi.</p><?php endif; ?>

    <table class="admin-table">
        <thead><tr><th>Slug</th><th>Ad (AZ)</th><th>Sıra</th><th>Aktiv</th><th>Ev sayı</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($regions as $r): ?>
                <tr>
                    <td><?= View::e($r['slug']) ?></td>
                    <td><?= View::e($r['name_az']) ?></td>
                    <td><?= (int) $r['sort_order'] ?></td>
                    <td><?= $r['is_active'] ? 'Bəli' : 'Xeyr' ?></td>
                    <td><?= (int) $r['house_count'] ?></td>
                    <td>
                        <a class="btn" href="/regions?edit=<?= (int) $r['id'] ?>">Redaktə</a>
                        <form method="post" action="/regions/<?= (int) $r['id'] ?>/sil" class="admin-inline-form" onsubmit="return confirm('Silinsin?');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn--danger">Sil</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2><?= $editing !== null ? 'Bölgəni redaktə et' : 'Yeni bölgə' ?></h2>
    <form method="post" action="<?= View::e($formAction) ?>" class="admin-region-form">
        <?= Csrf::field() ?>
        <label>Slug <input type="text" name="slug" pattern="[a-z0-9-]+" value="<?= View::e($editing['slug'] ?? '') ?>" required></label>
        <label>Ad (AZ) <input type="text" name="name_az" value="<?= View::e($editing['name_az'] ?? '') ?>" required></label>
        <label>Ad (RU) <input type="text" name="name_ru" value="<?= View::e($editing['name_ru'] ?? '') ?>" required></label>
        <label>Ad (EN) <input type="text" name="name_en" value="<?= View::e($editing['name_en'] ?? '') ?>" required></label>
        <label>Şüar (AZ) <input type="text" name="tagline_az" value="<?= View::e($editing['tagline_az'] ?? '') ?>"></label>
        <label>Şüar (RU) <input type="text" name="tagline_ru" value="<?= View::e($editing['tagline_ru'] ?? '') ?>"></label>
        <label>Şüar (EN) <input type="text" name="tagline_en" value="<?= View::e($editing['tagline_en'] ?? '') ?>"></label>
        <label>Sıra <input type="number" name="sort_order" value="<?= View::e((string) ($editing['sort_order'] ?? 0)) ?>"></label>
        <label class="checkbox-field">
            <input type="checkbox" name="is_active" <?= ($editing === null || $editing['is_active']) ? 'checked' : '' ?>>
            <span>Aktiv</span>
        </label>
        <button type="submit" class="btn btn--primary"><?= $editing !== null ? 'Yadda saxla' : 'Əlavə et' ?></button>
        <?php if ($editing !== null): ?><a class="btn" href="/regions">Ləğv et</a><?php endif; ?>
    </form>
</section>
