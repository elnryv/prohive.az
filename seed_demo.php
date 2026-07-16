<?php
declare(strict_types=1);

/**
 * Dev/demo seed: FAZA 1 üçün 6 nümunə ev + 3 sahibkar + yaşıl gradient placeholder
 * fotolar (bölmə 10.2: "şəkil yoxdursa yaşıl gradient placeholder + ev ikonu").
 *
 * İşə salma: php seed_demo.php
 * install.sql-dən SONRA, təmiz/yenicə qurulmuş bazada bir dəfə işə salınmaq üçün
 * nəzərdə tutulub. Idempotentdir — mövcud telefon/slug tapılarsa həmin qeyd ötürülür.
 */

require_once __DIR__ . '/app/bootstrap.php';

if (!function_exists('imagewebp')) {
    fwrite(STDERR, "GD/WebP dəstəyi tapılmadı — seed dayandırıldı.\n");
    exit(1);
}

$owners = [
    ['phone' => '994551000001', 'full_name' => 'Elvin Məmmədov', 'billing_status' => 'free', 'trial_until' => null, 'paid_until' => null],
    ['phone' => '994551000002', 'full_name' => 'Aygün Hüseynova', 'billing_status' => 'paid', 'trial_until' => null, 'paid_until' => date('Y-m-d', strtotime('+20 days'))],
    ['phone' => '994551000003', 'full_name' => 'Rəşad Quliyev', 'billing_status' => 'trial', 'trial_until' => date('Y-m-d', strtotime('+10 days')), 'paid_until' => null],
];

$houses = [
    [
        'owner' => 0, 'region' => 'qebele', 'slug' => 'meshekenari-ev-qebele-1',
        'title' => 'Meşəkənarı ev', 'village' => 'Vəndam kəndi',
        'description' => "Qəbələnin meşələrlə əhatələnmiş sakit kəndində, tam təchiz olunmuş ailə evi. Səhəri quş səsi ilə açırsınız, axşamı mangal başında bitirirsiniz.\nHəyətdə hovuz və uşaq üçün yellənçək var.",
        'price_night' => 120, 'price_weekend' => 150, 'rooms' => 3, 'capacity' => 6,
        'whatsapp_phone' => '994551000001', 'map_lat' => 40.9789, 'map_lng' => 47.8460,
        'amenities' => ['Wi-Fi', 'Hovuz', 'Mangal yeri', 'Dağ mənzərəsi', 'Uşaq üçün uyğun', 'Həyət'],
        'views_total' => 340, 'busy_offsets' => [3, 4, 5],
    ],
    [
        'owner' => 0, 'region' => 'seki', 'slug' => 'seki-bagli-ev-2',
        'title' => 'Bağlı köhnə şəki evi', 'village' => 'Kiş kəndi',
        'description' => "Ənənəvi Şəki memarlığında bərpa olunmuş, geniş bağçalı ev. Kişin qədim kilsəsinə piyada 10 dəqiqə.\nSəhər yeməyi ev sahibinin bağından təzə meyvələrlə.",
        'price_night' => 95, 'price_weekend' => null, 'rooms' => 2, 'capacity' => 4,
        'whatsapp_phone' => '994551000001', 'map_lat' => null, 'map_lng' => null,
        'amenities' => ['Wi-Fi', 'Səhər yeməyi', 'Həyət', 'Kondisioner'],
        'views_total' => 120, 'busy_offsets' => [],
    ],
    [
        'owner' => 1, 'region' => 'quba', 'slug' => 'quba-dagliq-ev-3',
        'title' => 'Dağlıq mənzərəli qonaq evi', 'village' => 'Qonaqkənd',
        'description' => "Qubanın ən hündür kəndlərindən birində, pəncərədən Şahdağ görünən ev. Qışda qar, yayda sərinlik.\nİsti döşəmə və soba ilə hər fəsil rahat.",
        'price_night' => 140, 'price_weekend' => 170, 'rooms' => 4, 'capacity' => 8,
        'whatsapp_phone' => '994551000002', 'map_lat' => 41.3667, 'map_lng' => 48.3000,
        'amenities' => ['Wi-Fi', 'Dağ mənzərəsi', 'İsti döşəmə/soba', 'Parkinq', 'Mangal yeri'],
        'views_total' => 510, 'busy_offsets' => [10, 11],
    ],
    [
        'owner' => 1, 'region' => 'lankaran', 'slug' => 'lankaran-deniz-4',
        'title' => 'Dəniz kənarı yay evi', 'village' => null,
        'description' => "Xəzərin sahilinə 300 metr məsafədə, geniş terraslı yay evi. Səhər dəniz kənarında gəzinti, axşam gün batımı seyri.\nAiləvi istirahət üçün ideal.",
        'price_night' => 160, 'price_weekend' => 190, 'rooms' => 3, 'capacity' => 7,
        'whatsapp_phone' => '994551000002', 'map_lat' => 38.7539, 'map_lng' => 48.8489,
        'amenities' => ['Wi-Fi', 'Dəniz mənzərəsi', 'Kondisioner', 'Parkinq', 'Uşaq üçün uyğun'],
        'views_total' => 275, 'busy_offsets' => [],
    ],
    [
        'owner' => 2, 'region' => 'qusar', 'slug' => 'qusar-sahdag-5',
        'title' => 'Şahdağ ətəyində şale', 'village' => 'Laza kəndi',
        'description' => "Laza şəlaləsinə yaxın, dağ turizmi həvəskarları üçün rahat şale tipli ev. Trekking marşrutlarının başlanğıcı 5 dəqiqəlik məsafədədir.",
        'price_night' => 110, 'price_weekend' => null, 'rooms' => 2, 'capacity' => 5,
        'whatsapp_phone' => '994551000003', 'map_lat' => 41.2231, 'map_lng' => 48.0480,
        'amenities' => ['Dağ mənzərəsi', 'Mangal yeri', 'Heyvan olar', 'Paltaryuyan'],
        'views_total' => 88, 'busy_offsets' => [],
    ],
    [
        'owner' => 2, 'region' => 'ismayilli', 'slug' => 'ismayilli-yasil-vadi-6',
        'title' => 'Yaşıl vadi qonaq evi', 'village' => 'Lahıc yaxınlığı',
        'description' => "İsmayıllının meşəli təpələrində, Lahıcdan 20 dəqiqə məsafədə sakit təbiət evi. Hovuz və geniş həyət.",
        'price_night' => 105, 'price_weekend' => 125, 'rooms' => 3, 'capacity' => 6,
        'whatsapp_phone' => '994551000003', 'map_lat' => null, 'map_lng' => null,
        'amenities' => ['Wi-Fi', 'Hovuz', 'Həyət', 'Yelləncək', 'Uşaq üçün uyğun'],
        'views_total' => 156, 'busy_offsets' => [7, 8, 9, 10],
    ],
];

$ownerIds = [];
foreach ($owners as $i => $o) {
    $existing = DB::one('SELECT id FROM owners WHERE phone = :p', [':p' => $o['phone']]);
    if ($existing !== null) {
        $ownerIds[$i] = (int) $existing['id'];
        echo "Owner {$o['phone']} artıq var (id={$ownerIds[$i]}), ötürülür.\n";
        continue;
    }
    DB::query(
        'INSERT INTO owners (phone, password_hash, full_name, billing_status, trial_until, paid_until, created_at)
         VALUES (:phone, :hash, :name, :status, :trial, :paid, NOW())',
        [
            ':phone' => $o['phone'],
            ':hash' => password_hash('Demo!2026', PASSWORD_DEFAULT),
            ':name' => $o['full_name'],
            ':status' => $o['billing_status'],
            ':trial' => $o['trial_until'],
            ':paid' => $o['paid_until'],
        ]
    );
    $ownerIds[$i] = (int) DB::lastInsertId();
    echo "Owner {$o['phone']} yaradıldı (id={$ownerIds[$i]}).\n";
}

foreach ($houses as $h) {
    $existing = DB::one('SELECT id FROM houses WHERE slug = :s', [':s' => $h['slug']]);
    if ($existing !== null) {
        echo "House {$h['slug']} artıq var, ötürülür.\n";
        continue;
    }

    $region = DB::one('SELECT id FROM regions WHERE slug = :s', [':s' => $h['region']]);
    if ($region === null) {
        fwrite(STDERR, "Bölgə tapılmadı: {$h['region']} — {$h['slug']} atlanıldı.\n");
        continue;
    }

    DB::query(
        'INSERT INTO houses (owner_id, region_id, slug, title, village, description,
                              price_night, price_weekend, rooms, capacity, whatsapp_phone,
                              map_lat, map_lng, status, views_total, created_at)
         VALUES (:owner_id, :region_id, :slug, :title, :village, :description,
                 :price_night, :price_weekend, :rooms, :capacity, :whatsapp_phone,
                 :map_lat, :map_lng, "approved", :views_total, NOW())',
        [
            ':owner_id' => $ownerIds[$h['owner']],
            ':region_id' => (int) $region['id'],
            ':slug' => $h['slug'],
            ':title' => $h['title'],
            ':village' => $h['village'],
            ':description' => $h['description'],
            ':price_night' => $h['price_night'],
            ':price_weekend' => $h['price_weekend'],
            ':rooms' => $h['rooms'],
            ':capacity' => $h['capacity'],
            ':whatsapp_phone' => $h['whatsapp_phone'],
            ':map_lat' => $h['map_lat'],
            ':map_lng' => $h['map_lng'],
            ':views_total' => $h['views_total'],
        ]
    );
    $houseId = (int) DB::lastInsertId();
    echo "House {$h['slug']} yaradıldı (id={$houseId}).\n";

    foreach ($h['amenities'] as $nameAz) {
        $amenity = DB::one('SELECT id FROM amenities WHERE name_az = :n', [':n' => $nameAz]);
        if ($amenity !== null) {
            DB::query(
                'INSERT IGNORE INTO house_amenities (house_id, amenity_id) VALUES (:h, :a)',
                [':h' => $houseId, ':a' => (int) $amenity['id']]
            );
        }
    }

    foreach ($h['busy_offsets'] as $offset) {
        $date = date('Y-m-d', strtotime("+{$offset} days"));
        DB::query(
            'INSERT IGNORE INTO house_calendar (house_id, busy_date) VALUES (:h, :d)',
            [':h' => $houseId, ':d' => $date]
        );
    }

    generatePlaceholderPhotos($houseId);
}

echo "Seed tamamlandı.\n";

function generatePlaceholderPhotos(int $houseId): void
{
    $dir = APP_ROOT . '/storage/uploads/houses/' . $houseId;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $count = 4;
    for ($i = 0; $i < $count; $i++) {
        $filename = bin2hex(random_bytes(8)) . '.webp';
        $bytes = renderPlaceholderWebp($i + 1, $count);
        file_put_contents($dir . '/' . $filename, $bytes);

        DB::query(
            'INSERT INTO house_photos (house_id, filename, is_cover, is_video, is_approved, sort_order, created_at)
             VALUES (:h, :f, :cover, 0, 1, :sort, NOW())',
            [
                ':h' => $houseId,
                ':f' => $filename,
                ':cover' => $i === 0 ? 1 : 0,
                ':sort' => $i,
            ]
        );
    }
    echo "  → {$count} placeholder foto əlavə olundu.\n";
}

function renderPlaceholderWebp(int $index, int $total): string
{
    $w = 1280;
    $h = 854;
    $im = imagecreatetruecolor($w, $h);

    // yaşıl gradient (bölmə 10.1: --moss -> --leaf)
    $c1 = [46, 87, 68];
    $c2 = [62, 124, 79];
    for ($y = 0; $y < $h; $y++) {
        $ratio = $y / $h;
        $r = (int) ($c1[0] + ($c2[0] - $c1[0]) * $ratio);
        $g = (int) ($c1[1] + ($c2[1] - $c1[1]) * $ratio);
        $b = (int) ($c1[2] + ($c2[2] - $c1[2]) * $ratio);
        $color = imagecolorallocate($im, $r, $g, $b);
        imageline($im, 0, $y, $w, $y, $color);
    }

    $white = imagecolorallocate($im, 255, 255, 255);
    $font = 5;
    // GD-nin daxili bitmap fontu (imagestring) yalnız ASCII dəstəkləyir —
    // diakritikli başlıq əvəzinə sadə ASCII etiket istifadə olunur.
    $label = 'Birlikde Getdik - Foto ' . $index . '/' . $total;
    $tw = imagefontwidth($font) * strlen($label);
    $th = imagefontheight($font);
    imagestring($im, $font, (int) (($w - $tw) / 2), (int) (($h - $th) / 2), $label, $white);

    ob_start();
    imagewebp($im, null, 78);
    $data = (string) ob_get_clean();
    imagedestroy($im);

    return $data;
}
