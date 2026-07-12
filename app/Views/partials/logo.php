<?php
/**
 * Paylaşılan "Birlikdə" logo markası — top-nav, drawer, auth-deck, splash
 * eyni markanı işlədir (bax app.css `.brand-logo`/`.brand-mark`/`.brand-word`).
 * Ölçü valideynin `font-size`-ına görə `em`-əsaslı miqyaslanır.
 * @var callable $t
 */
?><span class="brand-logo"><span class="brand-mark">B</span><span class="brand-word"><?= htmlspecialchars($t('ortaq.app_adi')) ?></span></span>
