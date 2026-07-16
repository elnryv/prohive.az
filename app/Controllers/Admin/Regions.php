<?php
declare(strict_types=1);

/** Bölgələr CRUD (9.6). Silinmə yalnız bağlı ev yoxdursa. */
final class Regions
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

        $id = RegionRepository::create($data);
        AdminLog::write('region.create', $id, $data['slug']);
        header('Location: /regions?saved=1');
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

        RegionRepository::update($id, $data);
        AdminLog::write('region.update', $id, $data['slug']);
        header('Location: /regions?saved=1');
        exit;
    }

    public function delete(array $params): void
    {
        AdminAuth::requireLogin();
        $id = (int) ($params['id'] ?? 0);
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            header('Location: /regions');
            exit;
        }

        $ok = RegionRepository::delete($id);
        AdminLog::write($ok ? 'region.delete' : 'region.delete_blocked', $id);
        header('Location: /regions?' . ($ok ? 'silindi=1' : 'xeta=bagli_ev'));
        exit;
    }

    /** @return array{0:array<string,mixed>,1:string[]} */
    private function validate(array $in): array
    {
        $errors = [];
        $slug = trim((string) ($in['slug'] ?? ''));
        $nameAz = trim((string) ($in['name_az'] ?? ''));
        $nameRu = trim((string) ($in['name_ru'] ?? ''));
        $nameEn = trim((string) ($in['name_en'] ?? ''));

        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors[] = 'Slug yalnız kiçik hərf, rəqəm və tire ola bilər.';
        }
        if ($nameAz === '' || $nameRu === '' || $nameEn === '') {
            $errors[] = 'Ad (AZ/RU/EN) bütün dillərdə tələb olunur.';
        }

        return [[
            'slug' => $slug,
            'name_az' => $nameAz, 'name_ru' => $nameRu, 'name_en' => $nameEn,
            'tagline_az' => trim((string) ($in['tagline_az'] ?? '')) ?: null,
            'tagline_ru' => trim((string) ($in['tagline_ru'] ?? '')) ?: null,
            'tagline_en' => trim((string) ($in['tagline_en'] ?? '')) ?: null,
            'sort_order' => (int) ($in['sort_order'] ?? 0),
            'is_active' => isset($in['is_active']) ? 1 : 0,
        ], $errors];
    }

    /** @param string[] $errors */
    private function render(array $errors = []): void
    {
        View::render('admin/regions', [
            'title' => 'Bölgələr — Admin',
            'errors' => $errors,
            'regions' => RegionRepository::listAll(),
        ], 'layout');
    }
}
