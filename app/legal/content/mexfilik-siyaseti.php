<?php

declare(strict_types=1);

return <<<'HTML'
<h2>1. Hansı məlumatlar toplanır</h2>
<p>Qeydiyyat zamanı: ad-soyad, telefon nömrəsi, şifrənin təhlükəsiz (hash) forması. Sürücülər
üçün əlavə olaraq: nəqliyyat vasitəsi növü, nəqliyyat vasitəsinin şəkli. İstifadə zamanı:
elan/təklif/sifariş tarixçəsi, yerləşdirilən şəkillər, bildiriş (push) abunəlikləri.</p>

<h2>2. Məlumatlar necə istifadə olunur</h2>
<p>Toplanan məlumatlar yalnız platformanın işləməsi üçün istifadə olunur: müştəri ilə
sürücünü əlaqələndirmək (təklif qəbul edildikdə telefon nömrələrinin qarşılıqlı açılması),
bildiriş göndərmək, hesabı idarə etmək. Platforma məlumatları üçüncü tərəflərə satmır.</p>

<h2>3. WhatsApp və telefon əlaqəsi</h2>
<p>Təklif qəbul edildikdən sonra tərəflərin telefon nömrələri bir-birinə göstərilir ki,
WhatsApp və ya zənglə birbaşa əlaqə saxlaya bilsinlər. Bu əlaqə platformadan kənarda baş
verir, platforma yazışmaları saxlamır və izləmir.</p>

<h2>4. Məlumatların saxlanması</h2>
<p>Hesab məlumatları hesab aktiv olduğu müddətdə saxlanılır. Hesabın silinməsini tələb etmək
üçün istifadəçi admin ilə əlaqə saxlaya bilər.</p>

<h2>5. Bildirişlər (Push)</h2>
<p>İstifadəçi razılıq verdiyi halda brauzer/tətbiq bildirişləri (yeni elan, təklif, status
dəyişikliyi) göndərilir. Bu razılıq istənilən vaxt cihaz ayarlarından ləğv edilə bilər.</p>

<h2>6. Təhlükəsizlik</h2>
<p>Şifrələr heç vaxt açıq mətn şəklində saxlanmır (bcrypt hash). Bütün yazma əməliyyatları
CSRF qoruması ilə həyata keçirilir.</p>

<h2>7. Əlaqə</h2>
<p>Fərdi məlumatlarınızla bağlı sual və tələblər üçün admin panelindəki əlaqə vasitələrindən
istifadə edə bilərsiniz.</p>
HTML;
