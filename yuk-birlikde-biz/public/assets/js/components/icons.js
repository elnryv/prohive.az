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
  box: svg('<path d="M3.5 8 12 3.5 20.5 8 12 12.5 3.5 8Z"/><path d="M3.5 8v8l8.5 4.5 8.5-4.5V8"/><path d="M12 12.5V21"/>'),
  chevronRight: svg('<path d="m9 6 6 6-6 6"/>', 18),
  lock: svg('<rect x="5" y="10" width="14" height="10" rx="2.5"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>'),
  truck: svg('<rect x="2.5" y="7.5" width="12" height="9" rx="1.5"/><path d="M14.5 11h4l3 3v2.5h-2"/><circle cx="7" cy="18.5" r="1.7"/><circle cx="17" cy="18.5" r="1.7"/>'),
  flag: svg('<path d="M6 3v18"/><path d="M6 4.5h11l-2.5 3.75L17 12H6"/>'),
  info: svg('<circle cx="12" cy="12" r="9"/><path d="M12 11v6"/><circle cx="12" cy="7.6" r="0.9" fill="currentColor" stroke="none"/>'),
  doc: svg('<path d="M7 3h7l4 4v14H7Z"/><path d="M14 3v4h4"/><path d="M9.5 12.5h5M9.5 16h5"/>'),
  help: svg('<circle cx="12" cy="12" r="9"/><path d="M9.4 9.3a2.6 2.6 0 0 1 5.1.7c0 1.8-2.5 1.7-2.5 3.5"/><circle cx="12" cy="17" r="0.9" fill="currentColor" stroke="none"/>'),
  phone: svg('<path d="M5 4.5h3l1.4 3.8L7.6 9.8a11.5 11.5 0 0 0 5.1 5.1l1.5-1.8 3.8 1.4v3a1 1 0 0 1-1 1A15.5 15.5 0 0 1 4 6.5a1 1 0 0 1 1-1Z"/>'),
  logout: svg('<path d="M9.5 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3.5"/><path d="M15 8l4 4-4 4"/><path d="M19 12H9.5"/>'),
  trash: svg('<path d="M4.5 7h15"/><path d="M9.5 7V5a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v2"/><path d="M6.5 7l1 12.5A1 1 0 0 0 8.5 20.5h7a1 1 0 0 0 1-1.5L17.5 7"/>'),
  bank: svg('<path d="M4 10.5 12 5l8 5.5"/><path d="M5 10.5h14V19H5Z"/><path d="M9 10.5V19M15 10.5V19"/>'),
};
