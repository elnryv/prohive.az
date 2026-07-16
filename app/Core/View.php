<?php
declare(strict_types=1);

final class View
{
    /** htmlspecialchars qısayolu, bütün output bununla keçməlidir (12.1) */
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], ?string $layout = 'layout'): void
    {
        $viewFile = APP_ROOT . '/app/views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new RuntimeException("View tapılmadı: {$view}");
        }

        $renderContent = static function () use ($viewFile, $data): string {
            extract($data, EXTR_SKIP);
            ob_start();
            require $viewFile;
            return (string) ob_get_clean();
        };

        if ($layout === null) {
            echo $renderContent();
            return;
        }

        $layoutFile = APP_ROOT . '/app/views/' . dirname($view) . '/' . $layout . '.php';
        $content = $renderContent();
        extract($data, EXTR_SKIP);
        require $layoutFile;
    }
}
