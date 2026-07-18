# Birlikdə Yük — İş Prinsipi (necə işləyir, kim nə edə bilir)

> Yük daşıma bazarı: müştəri yük elanı yerləşdirir, sürücülər elana baxıb qiymət təklifi
> verir, müştəri istədiyi təklifi qəbul edir. Üç rol: **Müştəri**, **Sürücü**, **Admin** —
> hər birinin öz giriş nöqtəsi və imkanları var.

---

## 1. MÜŞTƏRİ

### Qeydiyyat/giriş
- Telefon nömrəsi (prefiks + nömrə) + şifrə ilə qeydiyyat.
- Giriş zamanı "yaddaş saxla" seçimi — tətbiqi bağlayıb yenidən açanda sessiya avtomatik
  bərpa olunur (30 gün).
- 5 səhv cəhddən sonra hesab 15 dəqiqəliyə kilidlənir.

### Elan yaratma
Doldurulan sahələr:
- Kateqoriya (Ev köçürməsi, Mebel-məişət texnikası, Tikinti materialı, Bölgə yükü, Təmir
  tullantısı, Digər).
- Haradan / Hara — şəhər/rayon siyahısından seçim (+ könüllü dəqiq ünvan — bu, YALNIZ
  razılaşdığı sürücüyə deyilir, elanda göstərilmir).
- Tarix — "Razılaşma ilə" və ya konkret tarix.
- "Təcili (bu gün-sabah)" bayrağı — bu cür elanlar lentdə fərqli çip ilə göstərilir.
- Təsvir (məcburi) + maks 6 şəkil (könüllü).
- Qiymət — yazmasa, sürücülər öz təkliflərini verir.

Sistem bunu avtomatik təyin edir:
- Marşrutun növü: "Haradan" və "Hara" hər ikisi Bakı daxilindədirsə — "Bakı daxili"
  bölməsinə düşür, əks halda "Bölgələrarası" bölməsinə. Sürücü lentində bu iki bölmə
  ayrı tab-dır.
- Bitmə vaxtı: konkret tarix seçilibsə, o tarixdən bir gün sonra; yoxdursa, adətən 72
  saat sonra elan avtomatik bağlanır.
- Gündə maksimum 5 aktiv elan limiti var.

### Yaradıldıqdan sonra
- Elan dərhal, canlı şəkildə bütün açıq sürücü ekranlarına düşür (bax aşağı — real-time
  bölməsi).
- Bütün təsdiqlənmiş sürücülərə (ödənişli abunəliyi olsun-olmasın, hansı bölgəyə aid
  olmasından asılı olmadan) push bildirişi eyni anda göndərilir.
- Müştəri öz "Elanlarım" siyahısında, elanın öz səhifəsində gələn təklifləri görür.

### Təkliflərə baxış və qəbul
- Hər sürücü təklifi: qiymət + qeyd + sürücünün adı göstərilir.
- Müştəri bir təklifi qəbul edir — bu, elə qurulub ki, eyni elan iki dəfə "qəbul edilmiş"
  vəziyyətinə düşə bilməsin (yarış vəziyyətinə qarşı qorunma). Qəbul olunandan sonra
  qalan bütün təkliflər avtomatik "uduzdu" statusuna keçir, seçilən sürücüyə bildiriş
  gedir.
- Müştəri elanı ləğv edə bilər (səbəb seçimi ilə, məsələn "sürücü gəlmədi") — bu, elanı
  yenidən aktiv vəziyyətə qaytarır (+72 saat), əvvəlki uduzmuş təkliflər yenidən aktiv
  olur, sürücülərə "elan yenidən açıldı" bildirişi gedir.
- Elanı tam silmək və ya müddətini bir dəfə +72 saat uzatmaq mümkündür.

### Tarixçə və yenidən sifariş
- Keçmiş (bağlanmış/silinmiş) elanların ayrıca siyahısı var.
- "Yenidən sifariş" düyməsi — köhnə elanın bütün sahələrini (marşrut, kateqoriya) əvvəlcədən
  doldurulmuş formaya köçürür, yenidən təsdiqləyib göndərmək kifayətdir.

### Profil
- Ad və telefon nömrəsi redaktəsi.
- Push bildirişlərini aktivləşdirmə düyməsi.

### Paylaşım
- Hər elanın qısa, paylaşıla bilən linki var (sosial şəbəkədə paylaşmaq üçün, marşrut və
  kateqoriyanı göstərən şəkil avtomatik yaradılır).

---

## 2. SÜRÜCÜ

### Qeydiyyat və təsdiq axını
- Qeydiyyatdan sonra sürücü statusu "gözləmədə" olur — admin təsdiqləməyənə qədər lentə
  giriş bloklanır, ekranda bu barədə xəbərdarlıq göstərilir.
- Admin təsdiqləyəndə sürücü aktivləşir, rədd edəndə isə səbəblə birgə rədd statusuna
  düşür (ekranda göstərilir).
- Yalnız təsdiqlənmiş sürücü lentə çıxıb təklif verə bilər.

### Ödəniş/abunəlik (aktivlik statusu)
- Sürücünün TƏKLİF VERƏ BİLMƏSİ üçün: təsdiqlənmiş olmalı, bloklanmamalı, VƏ (ödəniş
  sistemi aktivdirsə) ya pulsuz təyin olunmuş, ya sınaq müddəti bitməmiş, ya da ödənişi
  bitməmiş olmalıdır.
- Aktiv olmayan (abunəsi bitmiş) sürücü lentə baxa bilir, AMMA təklif verə bilmir —
  ödəniş səhifəsindən abunəsini yeniləyə bilər.
- Push bildirişi bu statusdan ASILI DEYİL — bütün təsdiqlənmiş sürücülər (abunəsi aktiv
  olsun-olmasın) yeni elan bildirişini alır, çünki bildirişi görüb abunəsini yeniləyə bilər.

### Lent ("Canlı lövhə")
- İki tab: "Bakı daxili" və "Bölgələrarası".
- Kateqoriya üzrə filtr.
- Yeni elan yaradılan kimi canlı olaraq lentin başında görünür — səhifəni yeniləməyə
  ehtiyac yoxdur.
- Hər kartda: marşrut, kateqoriya, "Təcili" işarəsi (varsa), qısa təsvir, neçə vaxt əvvəl
  yaradıldığı, neçə təklif olduğu.
- Elan bağlananda (silinəndə/qəbul olunandan sonra) kart lentdən canlı silinir.

### Təklif vermə
- Elana daxil olub qiymət + qeyd yazır — təklif dərhal müştəriyə görünür.
- Eyni elana yenidən təklif versə, köhnə təklifi yenilənir (təkrarlanmır).
- Təklifini geri çəkə bilər (hələ cavablanmayıbsa).
- Müştəri qəbul edərsə sürücüyə bildiriş gedir, "Təkliflərim" bölməsində statusu yenilənir.

### Marşrut abunəlikləri
- Sürücü öz maraq bölgəsini (haradan/hara, ya da sadəcə Bakı-daxili/Bölgələrarası/hamısı)
  qeyd edə bilər, maksimum 5 marşrut.
- Hazırda bu, push bildirişinin kimə gedəcəyinə TƏSİR ETMİR — bütün təsdiqlənmiş
  sürücülərə hər elan bildirişi gedir.

### Təkliflərim / Tarixçə / Profil
- "Təkliflərim" — özünün verdiyi bütün təklifləri statusuna görə (gözləmədə/qəbul
  olunub/uduzub) görür.
- Tarixçə — keçmiş, artıq bağlanmış elanlarla bağlı fəaliyyəti göstərir.
- Profil — ad/nömrə redaktəsi, push aktivləşdirmə, ödəniş/abunəlik statusu.

---

## 3. ADMİN

Sayt istifadəçilərindən (müştəri/sürücü) tamamilə ayrı, öz giriş sistemi olan idarəetmə
paneli.

### İdarəetmə bölmələri
- Dashboard — ümumi statistika/trend qrafiklər.
- Sürücülər — siyahı, hər sürücünün profili; təsdiqləmə/rədd etmə (qeydiyyatdan sonrakı
  gözləmə vəziyyətini həll edir); ödəniş tipini (pulsuz/sınaq/ödənişli) və qiymətini əl ilə
  dəyişmə; bloklama.
- Müştərilər — siyahı, profil, bloklama.
- Elanlar — bütün elanların siyahısı, ətraflı baxış, silmə, şikayət bayraqlama.
- Parametrlər — ödəniş sisteminin qlobal aç/bağla, ümumi ayarlar (məsələn elanın avtomatik
  bağlanma müddəti), texniki fasilə rejimi, admin şifrəsini dəyişmə, kateqoriya/lokasiya
  siyahılarının idarəsi (aktiv/passiv).
- Ödənişlər — edilən bütün ödəniş tranzaksiyalarının siyahısı.
- Kampaniya — bütün (və ya seçilmiş) sürücülərə əl ilə kütləvi push bildirişi göndərmə.
- Loglar — admin əməliyyat tarixçəsi (kim, nə vaxt, nə etdi).
- Bannerlər — əsas ekranda görünən carousel banner-lərin idarəsi (yükləmə, aktiv/passiv,
  silmə).

### Texniki fasilə rejimi
- Admin saytı müvəqqəti "texniki fasilədədir" vəziyyətinə sala bilər — bu zaman adi
  istifadəçilər üçün sayt bağlıdır, YALNIZ admin girişi işləyir.

---

## 4. Real-time (canlı yeniləmə) necə işləyir — bütün rollara ortaq

- Hər əhəmiyyətli hadisə (yeni elan, yeni təklif, qəbul, ləğv/yenidən-açılma, bağlanma)
  baş verən kimi qeydə alınır.
- Sürücü/müştəri ekranı açıq olduğu müddətcə tətbiq serverlə canlı bir bağlantı saxlayır —
  server bu bağlantı üzərindən yeni hadisələri DƏRHAL göndərir, səhifə yenilənmədən.
- Bu bağlantı təbii olaraq hər bir saatda bir bağlanıb yenidən açılır — bu, adi, gözlənilən
  bir dövrdür.
- Bundan əlavə, yeni elan/təklif zamanı telefon bildiriş mərkəzinə görünən push bildirişi
  də göndərilir (tətbiq bağlı olsa belə) — bütün uyğun sürücülərə eyni anda çatdırılır.
