<?php
/** @var string|null $result */
use App\Core\Csrf;
?>
<h1>Push kampaniya</h1>

<?php if ($result !== null): ?><div class="banner" style="border-left-color:var(--ok)"><?= e($result) ?></div><?php endif; ?>

<form method="post" action="/kampaniya/gonder" class="card">
  <div class="field">
    <label for="target">Hədəf</label>
    <select id="target" name="target">
      <option value="all_drivers">Bütün sürücülər</option>
      <option value="all_customers">Bütün müştərilər</option>
      <option value="route_subscribers">Marşrut abunəçiləri</option>
    </select>
  </div>
  <div class="field">
    <label>Başlıq</label>
    <input type="text" name="title" required>
  </div>
  <div class="field">
    <label>Mətn</label>
    <textarea name="body" required></textarea>
  </div>
  <?= Csrf::field() ?>
  <button type="submit" class="btn btn-amber">Göndər</button>
</form>
