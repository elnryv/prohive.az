// Vahid ikon dəsti — Hissə 2.5 komponent kitabxanasının davamı. Hamısı 24x24
// xətt-üslubunda (stroke=currentColor), açıq width/height atributları ilə
// (flexbox-da yalnız-viewBox SVG-lərin enin 0-a düşməsi bug-ının qarşısı).
const svg = (paths, size = 22) =>
  `<svg viewBox="0 0 24 24" width="${size}" height="${size}" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${paths}</svg>`;

export const ICONS = {
  home: svg('<path d="M4 11.5 12 4l8 7.5"/><path d="M6 10v9a1 1 0 0 0 1 1h4v-6h2v6h4a1 1 0 0 0 1-1v-9"/>'),
  list: svg('<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M8 9h8M8 12.5h8M8 16h5"/>'),
  layers: svg('<path d="M12 3.5 21 8l-9 4.5L3 8Z"/><path d="m3 12 9 4.5 9-4.5"/><path d="m3 16 9 4.5 9-4.5"/>'),
  bell: svg('<path d="M6 10a6 6 0 1 1 12 0c0 4 1.5 5.5 1.5 5.5H4.5S6 14 6 10Z"/><path d="M9.5 18.5a2.5 2.5 0 0 0 5 0"/>'),
  person: svg('<circle cx="12" cy="8.5" r="3.5"/><path d="M4.5 20c1-3.8 4-6 7.5-6s6.5 2.2 7.5 6"/>'),
  tag: svg('<path d="M12.5 4H6a2 2 0 0 0-2 2v6.5a2 2 0 0 0 .59 1.41l8.5 8.5a2 2 0 0 0 2.82 0l6.09-6.09a2 2 0 0 0 0-2.82l-8.5-8.5A2 2 0 0 0 12.5 4Z"/><circle cx="9" cy="9" r="1.4" fill="currentColor" stroke="none"/>'),
  chevronLeft: svg('<path d="m15 6-6 6 6 6"/>'),
  share: svg('<path d="M12 4v11"/><path d="m7.5 8.5 4.5-4.5 4.5 4.5"/><path d="M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/>'),
  close: svg('<path d="M6 6l12 12M18 6 6 18"/>'),
  closeSmall: svg('<path d="M6 6l12 12M18 6 6 18"/>', 12),
};
