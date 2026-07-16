<?php
declare(strict_types=1);

final class StaticPage
{
    public function about(): void
    {
        $this->render('static.about_title', 'static.about_body');
    }

    public function terms(): void
    {
        $this->render('static.terms_title', 'static.terms_body');
    }

    public function privacy(): void
    {
        $this->render('static.privacy_title', 'static.privacy_body');
    }

    public function becomeHost(): void
    {
        View::render('site/become_host', [
            'title' => Lang::t('static.become_host_title') . ' — ' . Lang::t('app.name'),
            'description' => Lang::t('static.become_host_lead'),
        ]);
    }

    private function render(string $titleKey, string $bodyKey): void
    {
        View::render('site/static', [
            'title' => Lang::t($titleKey) . ' — ' . Lang::t('app.name'),
            'description' => Lang::t('app.tagline'),
            'pageTitle' => Lang::t($titleKey),
            'bodyHtml' => Lang::t($bodyKey),
        ]);
    }
}
