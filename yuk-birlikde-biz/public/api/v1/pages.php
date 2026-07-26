<?php
declare(strict_types=1);

// Nginx /api/v1/pages/{slug} sorğusunu bu fayla ?slug={slug} kimi yönləndirir
// (bax: nginx.conf.example).

require_once __DIR__ . '/../../../app/response.php';
require_once __DIR__ . '/../../../app/db.php';

require_method('GET');

$slug = preg_replace('/[^a-z0-9-]/', '', (string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    json_error('SLUG_REQUIRED', 'Səhifə göstərilməyib.', 422);
}

$stmt = db()->prepare('SELECT slug, title, content, updated_at FROM pages WHERE slug = :slug LIMIT 1');
$stmt->execute(['slug' => $slug]);
$page = $stmt->fetch();

if ($page === false) {
    json_error('PAGE_NOT_FOUND', 'Səhifə tapılmadı.', 404);
}

json_ok(['page' => $page]);
