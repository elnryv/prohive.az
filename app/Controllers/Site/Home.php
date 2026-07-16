<?php
declare(strict_types=1);

final class Home
{
    public function index(): void
    {
        View::render('site/home', [
            'title' => Lang::t('home.hello') . ' — ' . Lang::t('app.name'),
        ]);
    }
}
