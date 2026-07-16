<?php
declare(strict_types=1);

/** Ev əlavə/redaktə sehrbazı (7.3): 4 addım. */
final class HouseEdit
{
    public function create(): void
    {
        Auth::requireLogin();
        $this->renderStep(1, null, [], []);
    }

    public function createSubmit(): void
    {
        Auth::requireLogin();
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $this->renderStep(1, null, ['Sessiya vaxtı bitib, formu yenidən göndərin.'], $_POST);
            return;
        }

        [$fields, $errors] = $this->validateStep1($_POST);
        if ($errors !== []) {
            $this->renderStep(1, null, $errors, $_POST);
            return;
        }

        $owner = Auth::user();
        $fields['whatsapp_phone'] = $owner['phone'];
        $houseId = HouseRepository::createDraft((int) $owner['id'], $fields);

        header("Location: /sahib/ev/{$houseId}/redakte?addim=2");
        exit;
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();
        $house = $this->ownedHouseOrDie($params);
        $step = $this->clampStep((int) ($_GET['addim'] ?? 1));
        $this->renderStep($step, $house, [], []);
    }

    public function update(array $params): void
    {
        Auth::requireLogin();
        $house = $this->ownedHouseOrDie($params);
        $step = $this->clampStep((int) ($_POST['addim'] ?? 1));

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $this->renderStep($step, $house, ['Sessiya vaxtı bitib, formu yenidən göndərin.'], []);
            return;
        }

        [$fields, $errors] = match ($step) {
            1 => $this->validateStep1($_POST),
            2 => $this->validateStep2($_POST),
            3 => [['amenity_ids' => array_map('intval', $_POST['amenities'] ?? [])], []],
            default => [[], []],
        };

        if ($errors !== []) {
            $this->renderStep($step, $house, $errors, $_POST);
            return;
        }

        if ($step === 3) {
            HouseRepository::setAmenities((int) $house['id'], $fields['amenity_ids']);
        } else {
            HouseRepository::updateFields((int) $house['id'], $fields);
        }

        $isOnboarding = $house['status'] === 'draft';
        if ($isOnboarding && $step < 4) {
            header("Location: /sahib/ev/{$house['id']}/redakte?addim=" . ($step + 1));
        } else {
            header("Location: /sahib/ev/{$house['id']}/redakte?addim={$step}&saved=1");
        }
        exit;
    }

    public function submitForApproval(array $params): void
    {
        Auth::requireLogin();
        $house = $this->ownedHouseOrDie($params);

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            header("Location: /sahib/ev/{$house['id']}/redakte?addim=4");
            exit;
        }

        $photoCount = count(array_filter(HouseRepository::allPhotos((int) $house['id']), static fn ($p) => !$p['is_video']));
        if ($photoCount < 4) {
            $this->renderStep(4, $house, ['Təsdiqə göndərmək üçün minimum 4 foto lazımdır.'], []);
            return;
        }

        HouseRepository::submitForApproval((int) $house['id']);
        Sse::emit('admin', 'house_pending', ['house_id' => (int) $house['id'], 'title' => $house['title']]);
        header('Location: /sahib/panel?gonderildi=1');
        exit;
    }

    private function clampStep(int $step): int
    {
        return max(1, min(4, $step));
    }

    /** @return array{0:array<string,mixed>,1:string[]} */
    private function validateStep1(array $in): array
    {
        $errors = [];
        $title = trim((string) ($in['title'] ?? ''));
        $regionId = (int) ($in['region_id'] ?? 0);
        $village = trim((string) ($in['village'] ?? '')) ?: null;
        $description = trim((string) ($in['description'] ?? ''));
        $rooms = (int) ($in['rooms'] ?? 0);
        $capacity = (int) ($in['capacity'] ?? 0);
        $titleRu = trim((string) ($in['title_ru'] ?? '')) ?: null;
        $titleEn = trim((string) ($in['title_en'] ?? '')) ?: null;
        $descRu = trim((string) ($in['description_ru'] ?? '')) ?: null;
        $descEn = trim((string) ($in['description_en'] ?? '')) ?: null;

        if (mb_strlen($title) < 3) {
            $errors[] = 'Ev adı ən azı 3 simvol olmalıdır.';
        }
        if ($regionId <= 0 || DB::one('SELECT id FROM regions WHERE id = :id AND is_active = 1', [':id' => $regionId]) === null) {
            $errors[] = 'Bölgə seçilməyib.';
        }
        if (mb_strlen($description) < 20) {
            $errors[] = 'Təsvir ən azı 20 simvol olmalıdır.';
        }
        if ($rooms < 1 || $rooms > 20) {
            $errors[] = 'Otaq sayı düzgün deyil.';
        }
        if ($capacity < 1 || $capacity > 50) {
            $errors[] = 'Tutum düzgün deyil.';
        }

        return [[
            'title' => $title, 'title_ru' => $titleRu, 'title_en' => $titleEn,
            'region_id' => $regionId, 'village' => $village,
            'description' => $description, 'description_ru' => $descRu, 'description_en' => $descEn,
            'rooms' => $rooms, 'capacity' => $capacity,
        ], $errors];
    }

    /** @return array{0:array<string,mixed>,1:string[]} */
    private function validateStep2(array $in): array
    {
        $errors = [];
        $priceNight = $in['price_night'] ?? '';
        $priceWeekend = trim((string) ($in['price_weekend'] ?? ''));
        $phoneRaw = trim((string) ($in['whatsapp_phone'] ?? ''));
        $lat = trim((string) ($in['map_lat'] ?? ''));
        $lng = trim((string) ($in['map_lng'] ?? ''));

        if (!is_numeric($priceNight) || (float) $priceNight <= 0) {
            $errors[] = 'Gecəlik qiymət düzgün deyil.';
        }
        if ($priceWeekend !== '' && (!is_numeric($priceWeekend) || (float) $priceWeekend <= 0)) {
            $errors[] = 'Həftəsonu qiyməti düzgün deyil.';
        }

        $phone = null;
        try {
            $phone = Phone::normalize($phoneRaw);
        } catch (InvalidArgumentException) {
            $errors[] = 'WhatsApp nömrəsi düzgün formatda deyil.';
        }

        if ($lat !== '' && (!is_numeric($lat) || (float) $lat < -90 || (float) $lat > 90)) {
            $errors[] = 'Enlik (lat) düzgün deyil.';
        }
        if ($lng !== '' && (!is_numeric($lng) || (float) $lng < -180 || (float) $lng > 180)) {
            $errors[] = 'Uzunluq (lng) düzgün deyil.';
        }

        return [[
            'price_night' => is_numeric($priceNight) ? (float) $priceNight : 0,
            'price_weekend' => $priceWeekend !== '' ? (float) $priceWeekend : null,
            'whatsapp_phone' => $phone,
            'map_lat' => $lat !== '' ? (float) $lat : null,
            'map_lng' => $lng !== '' ? (float) $lng : null,
        ], $errors];
    }

    private function ownedHouseOrDie(array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $owner = Auth::user();
        $house = HouseRepository::findOwnedById($id, (int) $owner['id']);
        if ($house === null) {
            http_response_code(404);
            View::render('site/404');
            exit;
        }
        return $house;
    }

    /** @param string[] $errors */
    private function renderStep(int $step, ?array $house, array $errors, array $old): void
    {
        View::render('owner/house_form', [
            'title' => Lang::t('owner.house_form_title') . ' — ' . Lang::t('app.name'),
            'step' => $step,
            'house' => $house,
            'errors' => $errors,
            'old' => $old,
            'regions' => RegionRepository::listActive(),
            'amenities' => AmenityRepository::listActive(),
            'selectedAmenityIds' => $house ? array_map(static fn ($a) => (int) $a['id'], HouseRepository::amenities((int) $house['id'])) : [],
            'photos' => $house ? HouseRepository::allPhotos((int) $house['id']) : [],
            'saved' => isset($_GET['saved']),
        ], 'layout');
    }
}
