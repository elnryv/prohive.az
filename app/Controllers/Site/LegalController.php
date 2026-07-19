<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\View;

final class LegalController
{
    public function index(): void
    {
        View::render('legal/index', [
            'pageTitle' => t('legal.title'),
            'documents' => $this->documents(),
        ]);
    }

    public function show(array $params): void
    {
        $slug = (string) $params['slug'];
        $doc = null;
        foreach ($this->documents() as $d) {
            if ($d['slug'] === $slug) {
                $doc = $d;
                break;
            }
        }

        $contentFile = dirname(__DIR__, 2) . '/legal/content/' . $slug . '.php';
        if ($doc === null || !is_file($contentFile)) {
            http_response_code(404);
            View::render('site/404', []);
            return;
        }

        View::render('legal/show', [
            'pageTitle' => $doc['title_az'],
            'doc' => $doc,
            'html' => require $contentFile,
        ]);
    }

    private function documents(): array
    {
        return require dirname(__DIR__, 2) . '/legal/documents.php';
    }
}
