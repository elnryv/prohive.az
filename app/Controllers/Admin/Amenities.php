<?php
declare(strict_types=1);

/** Şəraitlər CRUD (9.6). Silinmə yalnız istifadə olunmursa. */
final class Amenities
{
    public function index(): void
    {
        AdminAuth::requireLogin();
        $this->render();
    }

    public function create(): void
    {
        AdminAuth::requireLogin();
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $this->render(['Sessiya vaxtı bitib.']);
            return;
        }

        [$data, $errors] = $this->validate($_POST);
        if ($errors !== []) {
            $this->render($errors);
            return;
        }

        $id = AmenityRepository::create($data);
        AdminLog::write('amenity.create', $id, $data['name_az']);
        header('Location: /amenities?saved=1');
        exit;
    }

    public function update(array $params): void
    {
        AdminAuth::requireLogin();
        $id = (int) ($params['id'] ?? 0);
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            $this->render(['Sessiya vaxtı bitib.']);
            return;
        }

        [$data, $errors] = $this->validate($_POST);
        if ($errors !== []) {
            $this->render($errors);
            return;
        }

        AmenityRepository::update($id, $data);
        AdminLog::write('amenity.update', $id, $data['name_az']);
        header('Location: /amenities?saved=1');
        exit;
    }

    public function delete(array $params): void
    {
        AdminAuth::requireLogin();
        $id = (int) ($params['id'] ?? 0);
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            header('Location: /amenities');
            exit;
        }

        $ok = AmenityRepository::delete($id);
        AdminLog::write($ok ? 'amenity.delete' : 'amenity.delete_blocked', $id);
        header('Location: /amenities?' . ($ok ? 'silindi=1' : 'xeta=istifade_olunur'));
        exit;
    }

    /** @return array{0:array<string,mixed>,1:string[]} */
    private function validate(array $in): array
    {
        $errors = [];
        $icon = trim((string) ($in['icon'] ?? ''));
        $nameAz = trim((string) ($in['name_az'] ?? ''));
        $nameRu = trim((string) ($in['name_ru'] ?? ''));
        $nameEn = trim((string) ($in['name_en'] ?? ''));

        if ($icon === '') {
            $errors[] = 'İkon tələb olunur.';
        }
        if ($nameAz === '' || $nameRu === '' || $nameEn === '') {
            $errors[] = 'Ad (AZ/RU/EN) bütün dillərdə tələb olunur.';
        }

        return [[
            'icon' => $icon,
            'name_az' => $nameAz, 'name_ru' => $nameRu, 'name_en' => $nameEn,
            'sort_order' => (int) ($in['sort_order'] ?? 0),
            'is_active' => isset($in['is_active']) ? 1 : 0,
        ], $errors];
    }

    /** @param string[] $errors */
    private function render(array $errors = []): void
    {
        View::render('admin/amenities', [
            'title' => 'Şəraitlər — Admin',
            'errors' => $errors,
            'amenities' => AmenityRepository::listAll(),
        ], 'layout');
    }
}
