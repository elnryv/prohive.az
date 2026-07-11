<?php
/**
 * @var callable $t
 */
?></div><!-- .container -->

<div class="install-overlay" id="installOverlay">
  <div class="install-sheet glass">
    <h2><?= htmlspecialchars($t('install.basliq')) ?></h2>
    <p id="installIosText" style="display:none;"><?= htmlspecialchars($t('install.ios_metn')) ?></p>
    <p id="installGenericText"><?= htmlspecialchars($t('install.metn')) ?></p>
    <div class="install-actions">
      <button class="btn btn-ghost" id="installSkipBtn" type="button"><?= htmlspecialchars($t('install.kec')) ?></button>
      <button class="btn btn-primary" id="installAddBtn" type="button"><?= htmlspecialchars($t('install.duyme')) ?></button>
    </div>
  </div>
</div>

<script>
  Birlikde.registerServiceWorker();
  Birlikde.initInstallPrompt(
    document.getElementById('installOverlay'),
    document.getElementById('installAddBtn'),
    document.getElementById('installSkipBtn'),
    document.getElementById('installIosText')
  );

  var navCixisBtn = document.getElementById('navCixisBtn');
  if (navCixisBtn) {
    navCixisBtn.addEventListener('click', function (event) {
      event.preventDefault();
      Birlikde.api('POST', '/cixis').finally(function () {
        window.location.href = '/giris';
      });
    });
  }
</script>
</body>
</html>
