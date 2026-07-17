<?php
/** @var string $oldPrefix (könüllü) */
/** @var string $oldNumber (könüllü) */
$oldPrefix ??= '';
$oldNumber ??= '';
$prefixes = ['055', '050', '099', '051', '070', '077', '010'];
?>
<div class="field">
  <label><?= e(t('common.phone')) ?></label>
  <div class="phone-input-group">
    <select name="phone_prefix" class="phone-prefix-select" aria-label="Operator kodu">
      <?php foreach ($prefixes as $p): ?>
        <option value="<?= e($p) ?>" <?= $oldPrefix === $p ? 'selected' : '' ?>><?= e($p) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="tel" name="phone_number" id="phone_number" class="phone-number-input"
      inputmode="numeric" maxlength="7" pattern="[0-9]{7}" placeholder="1234567"
      value="<?= e($oldNumber) ?>" required
      oninput="this.value=this.value.replace(/\D/g,'').slice(0,7);document.getElementById('phone_number_hint').style.color=this.value.length===7?'var(--ok)':'var(--danger)';">
  </div>
  <p id="phone_number_hint" class="text-soft" style="margin-top:4px;font-size:12px"><?= e(t('auth.phone_digits_hint')) ?></p>
</div>
