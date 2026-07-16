<?php
declare(strict_types=1);

return [
    'app.name' => 'Birlikdə Getdik',
    'app.tagline' => 'Bölgə qonaq evləri vitrini',

    'nav.home' => 'Ana səhifə',
    'nav.regions' => 'Bölgələr',
    'nav.owner_login' => 'Ev sahibi girişi',

    'footer.about' => 'Haqqında',
    'footer.terms' => 'İstifadə şərtləri',
    'footer.privacy' => 'Məxfilik',
    'footer.rights' => 'Bütün hüquqlar qorunur.',
    'footer.add_house' => 'Evinizi əlavə edin — ilk ay pulsuz',

    // Ana səhifə
    'home.hero_title' => 'Sevdiyin bölgədə evini seç',
    'home.hero_subtitle' => 'Azərbaycanın bölgələrindəki qonaq evlərini vitrində gəz, bəyəndiyini bir toxunuşla ev sahibinə yaz.',
    'home.search_region' => 'Bölgə',
    'home.search_region_all' => 'Bütün bölgələr',
    'home.search_checkin' => 'Giriş tarixi',
    'home.search_checkout' => 'Çıxış tarixi',
    'home.search_guests' => 'Qonaq sayı',
    'home.search_submit' => 'Axtar',
    'home.chip_all' => 'Bütün evlər',
    'home.chip_mountain' => 'Dağ evi',
    'home.chip_pool' => 'Hovuzlu',
    'home.chip_family' => 'Ailəvi',
    'home.chip_sea' => 'Dəniz mənzərəli',
    'home.chip_bbq' => 'Mangal yeri',
    'home.regions_title' => 'Bölgələr',
    'home.popular_title' => 'Populyar evlər',

    // Siyahı (bölgə/axtarış ortaq)
    'listing.house_count' => '%d ev',
    'listing.filter_price' => 'Qiymət aralığı',
    'listing.filter_price_min' => 'min',
    'listing.filter_price_max' => 'maks',
    'listing.filter_capacity' => 'Tutum',
    'listing.filter_amenities' => 'Şərait',
    'listing.filter_dates' => 'Tarix aralığı',
    'listing.filter_checkin' => 'Giriş',
    'listing.filter_checkout' => 'Çıxış',
    'listing.filter_apply' => 'Tətbiq et',
    'listing.sort_label' => 'Sırala',
    'listing.sort_default' => 'Standart',
    'listing.sort_price_asc' => 'Ucuzdan bahaya',
    'listing.sort_price_desc' => 'Bahadan ucuza',
    'listing.empty' => 'Bu bölgədə hələ ev yoxdur — ilk sən ol.',
    'listing.empty_search' => 'Axtarışa uyğun ev tapılmadı. Filtrləri dəyişməyi sınayın.',
    'listing.load_more' => 'Daha çox göstər',
    'listing.badge_maybe_full' => 'Seçdiyin tarixdə dolu ola bilər',
    'listing.price_from' => 'gecə',
    'listing.capacity_short' => 'nəfər',
    'search.title' => 'Axtarış nəticələri',

    // Ev səhifəsi
    'house.capacity' => 'Tutum',
    'house.rooms' => 'Otaq',
    'house.whole_house' => 'Bütöv ev',
    'house.per_night' => 'gecə',
    'house.per_weekend' => 'həftəsonu',
    'house.amenities_title' => 'Şərait',
    'house.description_title' => 'Ev haqqında',
    'house.description_note' => 'Bu dildə tərcümə hələ əlavə olunmayıb, əsas mətn göstərilir.',
    'house.calendar_title' => 'Dolu/boş təqvim',
    'house.calendar_note' => 'Təqvimi ev sahibi özü yeniləyir — dəqiqləşdirmək üçün yazın.',
    'house.owner_title' => 'Ev sahibi',
    'house.owner_verified' => '✓ Yoxlanılıb',
    'house.owner_since' => 'qeydiyyat ili: %s',
    'house.map_title' => 'Xəritədə yer',
    'house.wa_button' => 'WhatsApp ilə əlaqə',
    'house.village_unknown' => '',
    'house.not_found' => 'Bu ev tapılmadı və ya artıq yayımda deyil.',

    // WhatsApp mesaj şablonu (bölmə 6.4.1)
    'wa.greeting' => 'Salam!',
    'wa.about' => '"{title}" ({region}) haqqında məlumat almaq istəyirəm.',
    'wa.dates' => 'Tarix: {range}',
    'wa.guests' => 'Qonaq: {n}',
    'wa.via' => '— Birlikdə Getdik vasitəsilə: {link}',

    // Statik səhifələr
    'static.about_title' => 'Haqqında',
    'static.about_body' => '
        <p>Birlikdə Getdik — Azərbaycanın bölgələrindəki qonaq evlərini bir vitrində toplayan
        məlumat vasitəçisi platformadır. Qəbələ, Quba, Qusar, Şəki, İsmayıllı, Lənkəran və
        digər bölgələrdə ev axtaran qonaqlar heç bir qeydiyyat olmadan evləri gəzə, bəyəndiyini
        birbaşa ev sahibinin WhatsApp-ına yaza bilər.</p>
        <p>Platforma rezervasiya sistemi deyil: tarix, qiymət və razılaşma tamamilə qonaq və ev
        sahibi arasındadır. Ev sahibləri aylıq abunə ödəyərək evlərini vitrində saxlayır, qonaq
        üçün hər şey tamamilə pulsuzdur.</p>
    ',
    'static.terms_title' => 'İstifadə şərtləri',
    'static.terms_body' => '
        <p><strong>Vasitəçilik müddəası:</strong> Birlikdə Getdik yalnız məlumat vasitəçisidir.
        Platforma qonaq və ev sahibi arasında heç bir rezervasiya aparmır, ödəniş qəbul etmir və
        tərəflər arasındakı razılaşmaya, ödənişə, xidmətin keyfiyyətinə və ya ev haqqında
        göstərilən məlumatların dəqiqliyinə cavabdeh deyil.</p>
        <p>Ev sahibi tərəfindən göstərilən qiymət, tarix və şərait məlumatları vitrin xarakteri
        daşıyır və zəmanət təşkil etmir — dəqiq razılaşma birbaşa tərəflər arasında aparılmalıdır.</p>
        <p>Ev sahibləri platformaya doğru və qanuni məlumat təqdim etməyə borcludur. Yanlış və ya
        aldadıcı elanlar admin tərəfindən silinə bilər.</p>
        <p>Platformadan istifadə etməklə bu şərtləri qəbul etmiş olursunuz.</p>
    ',
    'static.privacy_title' => 'Məxfilik',
    'static.privacy_body' => '
        <p>Qonaq tərəfi tamamilə qeydiyyatsız işləyir — şəxsi məlumatınız saxlanılmır.
        Ev sahibi qeydiyyatında yalnız ad, telefon nömrəsi və şifrə tələb olunur; bu məlumatlar
        yalnız platforma daxilində identifikasiya və əlaqə məqsədilə istifadə olunur.</p>
        <p>Ödəniş məlumatları birbaşa Payriff ödəniş provayderi tərəfindən emal olunur, platforma
        kart məlumatlarını saxlamır.</p>
        <p>Statistik məlumatlar (baxış, WhatsApp klik sayı) yalnız ev sahibinə xidmət keyfiyyətini
        göstərmək üçün anonim şəkildə toplanır.</p>
    ',
    'static.become_host_title' => 'Ev sahibi olun',
    'static.become_host_lead' => 'Evinizi bölgənizin ən çox baxılan vitrininə əlavə edin.',
    'static.become_host_body' => '
        <p>Qeydiyyat 2 dəqiqə çəkir, ilk ay tamamilə pulsuzdur. Sonra aylıq cəmi
        25 AZN-ə evinizi minlərlə potensial qonağa göstərin — heç bir komissiya, heç bir
        rezervasiya haqqı yoxdur.</p>
        <ul>
            <li>Qonaqlar birbaşa sizin WhatsApp-ınıza yazır — vasitəçi yoxdur</li>
            <li>Təqvimi özünüz idarə edirsiniz</li>
            <li>Panel üzərindən canlı baxış və klik statistikası</li>
        </ul>
    ',
    'static.become_host_cta' => 'İndi qeydiyyatdan keç — ilk ay pulsuz',

    // Ev sahibi tərəfi (bölmə 7)
    'owner.register_title' => 'Qeydiyyat',
    'owner.login_title' => 'Giriş',
    'owner.dashboard_title' => 'Panel',
    'owner.house_form_title' => 'Ev əlavə et',
    'owner.calendar_title' => 'Təqvim',
    'owner.billing_title' => 'Abunə',

    'owner.nav_panel' => 'Panel',
    'owner.nav_calendar' => 'Təqvim',
    'owner.nav_billing' => 'Abunə',
    'owner.nav_logout' => 'Çıxış',
    'owner.nav_new_house' => 'Yeni ev əlavə et',
    'owner.nav_back_to_site' => 'Sayta qayıt',

    'owner.field_full_name' => 'Ad Soyad',
    'owner.field_phone' => 'Telefon',
    'owner.field_password' => 'Şifrə',
    'owner.field_password2' => 'Şifrə (təkrar)',
    'owner.field_agree' => 'Şərtlərlə razıyam',
    'owner.field_remember' => 'Məni xatırla',

    'owner.action_register' => 'Qeydiyyatdan keç',
    'owner.action_login' => 'Daxil ol',
    'owner.already_have_account' => 'Artıq hesabınız var?',
    'owner.no_account_yet' => 'Hesabınız yoxdur?',
    'owner.go_to_login' => 'Daxil olun',
    'owner.go_to_register' => 'Qeydiyyatdan keçin',

    'owner.step1_title' => '1. Əsas məlumat',
    'owner.step2_title' => '2. Qiymət və əlaqə',
    'owner.step3_title' => '3. Şərait',
    'owner.step4_title' => '4. Foto/video',

    'owner.field_title' => 'Ev adı (AZ)',
    'owner.field_title_optional_langs' => 'RU/EN adı (könüllü)',
    'owner.field_region' => 'Bölgə',
    'owner.field_village' => 'Kənd/qəsəbə',
    'owner.field_description' => 'Təsvir (AZ)',
    'owner.field_description_optional_langs' => 'RU/EN təsvir (könüllü)',
    'owner.field_rooms' => 'Otaq sayı',
    'owner.field_capacity' => 'Tutum (nəfər)',
    'owner.field_price_night' => 'Gecəlik qiymət (AZN)',
    'owner.field_price_weekend' => 'Həftəsonu qiyməti (AZN, könüllü)',
    'owner.field_whatsapp_phone' => 'WhatsApp nömrəsi',
    'owner.field_map_lat' => 'Enlik (lat, könüllü)',
    'owner.field_map_lng' => 'Uzunluq (lng, könüllü)',

    'owner.action_next' => 'Sonrakı',
    'owner.action_save' => 'Yadda saxla',
    'owner.action_submit_for_approval' => 'Təsdiqə göndər',
    'owner.action_back_to_panel' => 'Panelə qayıt',

    'owner.photos_title' => 'Foto/video',
    'owner.photos_min_note' => 'Dərc üçün minimum 4 foto lazımdır (maks. 20 foto + 1 video).',
    'owner.photos_upload_button' => 'Fayl seç',
    'owner.photos_cover_label' => 'Üz qabığı',
    'owner.photos_make_cover' => 'Üz qabığı et',
    'owner.photos_delete' => 'Sil',
    'owner.photos_pending_note' => 'Admin baxışına düşməyib',

    'owner.dashboard_no_houses' => 'Hələ ev əlavə etməmisiniz.',
    'owner.dashboard_add_house' => 'İlk evini əlavə et',
    'owner.house_status_draft' => 'Qaralama',
    'owner.house_status_pending' => 'Təsdiq gözləyir',
    'owner.house_status_approved' => 'Yayımda',
    'owner.house_status_rejected' => 'Geri göndərilib',
    'owner.house_reject_reason' => 'Səbəb: %s',
    'owner.action_edit' => 'Redaktə et',
    'owner.action_view' => 'Saytda bax',
    'owner.submitted_notice' => 'Ev təsdiqə göndərildi. Admin baxışından sonra saytda görünəcək.',
    'owner.saved_notice' => 'Dəyişikliklər yadda saxlanıldı.',

    'owner.stats_title' => 'Son 30 gün',
    'owner.stats_views' => 'Baxış',
    'owner.stats_wa_clicks' => 'WhatsApp klik',
    'owner.stats_total_views' => 'Cəmi baxış',
    'owner.stats_total_wa_clicks' => 'Cəmi WhatsApp klik',

    'owner.billing_status_trial' => 'Sınaq müddəti',
    'owner.billing_status_paid' => 'Ödənilib',
    'owner.billing_status_free' => 'Pulsuz',
    'owner.billing_status_expired' => 'Bitib',
    'owner.billing_status_blocked' => 'Bloklanıb',
    'owner.billing_until' => 'Bitmə tarixi: %s',
    'owner.billing_price' => 'Aylıq qiymət: %s AZN',
    'owner.billing_pay_button' => 'Ödə',
    'owner.billing_coming_soon' => 'Onlayn ödəniş tezliklə aktivləşdiriləcək. Bu müddətdə platforma ilə əlaqə saxlayın.',
    'owner.billing_active_free' => 'Abunəniz aktivdir (pulsuz status).',
    'owner.billing_expired_note' => 'Abunəniz bitib — evləriniz saytda gizlədilib. Ödəniş edin, evləriniz dərhal geri qayıtsın.',
    'owner.billing_visible_note' => 'Evləriniz saytda görünür.',
    'owner.billing_hidden_note' => 'Evləriniz saytda gizlədilib.',

    'owner.calendar_select_house' => 'Ev seçin',
    'owner.calendar_hint' => 'Günə toxunub dolu/boş işarələyin.',
    'owner.calendar_range_from' => 'Başlanğıc',
    'owner.calendar_range_to' => 'Son',
    'owner.calendar_range_mark_busy' => 'Aralığı dolu et',
    'owner.calendar_range_mark_free' => 'Aralığı boş et',
    'owner.calendar_apply' => 'Tətbiq et',
    'owner.calendar_no_houses' => 'Təqvimi idarə etmək üçün əvvəlcə ev əlavə edin.',
];
