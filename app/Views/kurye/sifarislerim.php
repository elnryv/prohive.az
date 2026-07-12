<?php
/**
 * @var callable $t
 * @var string $whatsappSupport
 */
require __DIR__ . '/../partials/head.php';
?>
<div class="card glass">
  <h1><?= htmlspecialchars($t('kurye.sifarislerim')) ?></h1>
</div>

<div id="sifarislerimList"></div>
<div class="empty-state" id="sifarislerimBos" style="display:none;"><?= htmlspecialchars($t('kurye.sifarislerim_bos')) ?></div>

<a class="btn btn-support" id="sikayetBtn" target="_blank" rel="noopener">&#128172;&nbsp; <?= htmlspecialchars($t('kurye.sikayet_teklif')) ?></a>

<script>
var MUSTERI_ETIKETI = <?= json_encode($t('kurye.musteri_adi'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var WHATSAPP_METNI = <?= json_encode($t('kurye.whatsapp_elaqe'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var GOTURULME_ETIKETI = <?= json_encode($t('musteri.goturulme'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var CATDIRILMA_ETIKETI = <?= json_encode($t('musteri.catdirilma'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var SIKAYET_WA_LINK = <?= json_encode(
    'https://wa.me/' . $whatsappSupport . '?text=' . rawurlencode('Təklif/İrad — '),
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

(function () {
  document.getElementById('sikayetBtn').href = SIKAYET_WA_LINK;

  var listEl = document.getElementById('sifarislerimList');
  var bosEl = document.getElementById('sifarislerimBos');

  async function yukle() {
    var res = await Birlikde.api('GET', '/kurye/sifarislerim');
    if (Birlikde.redirectIfUnauthorized(res.status)) return;
    var sifarisler = (res.data && res.data.data) || [];

    if (sifarisler.length === 0) {
      bosEl.style.display = 'block';
      return;
    }

    listEl.innerHTML = sifarisler.map(function (s) {
      var html = '<div class="card glass">';
      html += '<div style="display:flex; justify-content:space-between; align-items:center;">';
      html += '<strong>#' + s.id + '</strong>';
      html += '<span class="badge badge-tamamlandi">' + Birlikde.escapeHtml(s.goturulme_vaxti || s.created_at) + '</span>';
      html += '</div>';
      html += Birlikde.routeStepperHtml(s.goturulme_unvan, s.catdirilma_unvan, GOTURULME_ETIKETI, CATDIRILMA_ETIKETI);
      html += '<div class="dasiyici-info">';
      html += '<span>' + Birlikde.escapeHtml(MUSTERI_ETIKETI) + ': ' + Birlikde.escapeHtml(s.musteri_adi || '—') + '</span>';
      if (s.musteri_whatsapp_link) {
        html += '<a class="btn btn-ghost btn-small" target="_blank" rel="noopener" href="' +
          Birlikde.escapeHtml(s.musteri_whatsapp_link) + '">' + Birlikde.escapeHtml(WHATSAPP_METNI) + '</a>';
      }
      html += '</div></div>';
      return html;
    }).join('');
  }

  yukle();
})();
</script>
<?php require __DIR__ . '/../partials/foot.php'; ?>
