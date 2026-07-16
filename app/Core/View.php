<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private static string $basePath = '';

    public static function boot(string $basePath): void
    {
        self::$basePath = $basePath;
    }

    public static function render(string $template, array $data = [], string $layout = 'layouts/app'): void
    {
        $content = self::capture($template, $data);
        if ($layout === '') {
            echo $content;
            return;
        }
        $data['content'] = $content;
        echo self::capture($layout, $data);
    }

    public static function capture(string $template, array $data = []): string
    {
        $file = self::$basePath . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$template}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    public static function partial(string $template, array $data = []): void
    {
        echo self::capture($template, $data);
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
