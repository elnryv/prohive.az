<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Tək-cizgili (line) SVG ikon dəsti — emoji-nin bütün platformalarda eyni
 * görünməməsi (bax PROGRESS.md, dizayn cilası) probleminin qarşısını alır.
 * Hamısı 24x24 viewBox, stroke=currentColor — rəng CSS-dən idarə olunur.
 */
final class Icon
{
    private const PATHS = [
        'truck' => '<path d="M1 3h13v13H1z"/><path d="M14 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="1.8"/><circle cx="17.5" cy="18.5" r="1.8"/>',
        'box' => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'building' => '<rect x="4" y="2" width="16" height="20" rx="1"/><path d="M9 22v-4h6v4"/><path d="M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1"/>',
        'appliance' => '<rect x="4" y="2" width="16" height="20" rx="2"/><circle cx="12" cy="13" r="5"/><path d="M8 6h.01M12 6h.01"/>',
        'sofa' => '<path d="M5 11V8a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v3"/><path d="M3 11h18v5a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M5 17v2M19 17v2"/>',
        'dots' => '<circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/>',
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'list' => '<path d="M9 6h11M9 12h11M9 18h11"/><path d="M4 6h.01M4 12h.01M4 18h.01"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4.5 5-6 8-6s6.5 1.5 8 6"/>',
        'bell' => '<path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'map-pin' => '<path d="M12 21s7-6.5 7-12a7 7 0 0 0-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="9" r="2.4"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'chevron-left' => '<path d="M15 6l-6 6 6 6"/>',
        'close' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'check' => '<path d="M5 13l4 4L19 7"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9"/>',
        'star' => '<path d="M12 3l2.6 5.6 6.1.6-4.6 4.1 1.3 6-5.4-3.2-5.4 3.2 1.3-6-4.6-4.1 6.1-.6z"/>',
        'phone' => '<path d="M6 3h3l1.5 5L8 9.5a12 12 0 0 0 6.5 6.5L16 13.5l5 1.5v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4 5.2 2 2 0 0 1 6 3z"/>',
        'whatsapp' => '<path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.7-1.2A9 9 0 1 0 12 3z"/><path d="M8.5 8.8c.2-.6.6-.6 1-.6h.6c.2 0 .4 0 .6.5s.7 1.7.8 1.9.1.3 0 .5-.2.3-.4.5-.4.4-.5.6-.2.3 0 .6a6.7 6.7 0 0 0 3 2.9c.3.1.4 0 .6-.1s.7-.8.9-1.1.4-.2.6-.1l1.7.8c.2.1.4.2.4.4s0 1.1-.5 1.6-1.5 1-2.4.9a8.4 8.4 0 0 1-6.2-3.7 7.6 7.6 0 0 1-1.6-4.4c0-1 .4-1.9 1-2.4z"/>',
        'camera' => '<path d="M4 8h3l2-2h6l2 2h3v11H4z"/><circle cx="12" cy="13.5" r="3.5"/>',
        'zap' => '<path d="M13 2L4 14h6l-1 8 9-12h-6z"/>',
        'lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 13.5a7.6 7.6 0 0 0 0-3l2-1.5-2-3.4-2.3.9a7.7 7.7 0 0 0-2.6-1.5L14 2h-4l-.5 2.4a7.7 7.7 0 0 0-2.6 1.5l-2.3-.9-2 3.4 2 1.5a7.6 7.6 0 0 0 0 3l-2 1.5 2 3.4 2.3-.9c.8.7 1.6 1.2 2.6 1.5L10 22h4l.5-2.4a7.7 7.7 0 0 0 2.6-1.5l2.3.9 2-3.4z"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'trash' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 14h10l1-14"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'alert-triangle' => '<path d="M12 3l10 18H2z"/><path d="M12 10v4"/><path d="M12 17h.01"/>',
        'wallet' => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M16 13h2"/><path d="M3 9h18"/>',
        'shield-check' => '<path d="M12 3l8 3v6c0 4.5-3.4 8-8 9-4.6-1-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'upload' => '<path d="M12 16V4"/><path d="M6 9l6-6 6 6"/><path d="M4 20h16"/>',
        'download' => '<path d="M12 4v12"/><path d="M6 11l6 6 6-6"/><path d="M4 20h16"/>',
        'smartphone' => '<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 0 1 4.8 1c0 1.5-2.3 1.8-2.3 3.5"/><path d="M12 17h.01"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'refresh' => '<path d="M21 12a9 9 0 0 1-15.3 6.4L3 16"/><path d="M3 12a9 9 0 0 1 15.3-6.4L21 8"/><path d="M3 16v-4h4"/><path d="M21 8v4h-4"/>',
        'message' => '<path d="M4 4h16v12H8l-4 4z"/>',
    ];

    private const CATEGORY_ICONS = [
        'ev-kocurmesi' => 'home',
        'mebel-teknika' => 'sofa',
        'tikinti-materiali' => 'building',
        'bolge-yuku' => 'truck',
        'temir-tullantisi' => 'trash',
        'diger' => 'box',
    ];

    /** categories.slug-a görə ikon adı — categories.icon sütunundakı emoji-nin əvəzinə. */
    public static function forCategorySlug(?string $slug): string
    {
        return self::CATEGORY_ICONS[$slug] ?? 'box';
    }

    public static function render(string $name, string $class = 'icon', int $size = 20): string
    {
        $inner = self::PATHS[$name] ?? self::PATHS['box'];
        $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES) . '"' : '';
        return '<svg' . $classAttr . ' width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" '
            . 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . $inner . '</svg>';
    }
}
