import { registerRoute, init, start } from './router.js';

registerRoute('/', () => import('./screens/splash.js'));
registerRoute('/telefon', () => import('./screens/phone.js'));
registerRoute('/pin', () => import('./screens/pin.js'));
registerRoute('/qeydiyyat', () => import('./screens/register.js'));
registerRoute('/ana-sehife', () => import('./screens/home.js'));

document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('app');
  init(root);
  start();
});
