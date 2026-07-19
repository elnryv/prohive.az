// SPA-shell router: History API əsaslı, hər ekran ayrı ES modul olaraq lazy
// yüklənir. Bu, reload-suz təcrübənin və Android back gesture-in açarıdır
// (bax: Hissə 12).

const routes = [];
let rootEl = null;
let current = null; // { path, unmount }

export function registerRoute(pattern, loader) {
  const paramNames = [];
  const regex = new RegExp(
    '^' + pattern.replace(/:([a-zA-Z]+)/g, (_, name) => {
      paramNames.push(name);
      return '([^/]+)';
    }) + '$'
  );
  routes.push({ regex, paramNames, loader });
}

function matchRoute(path) {
  for (const route of routes) {
    const m = path.match(route.regex);
    if (m) {
      const params = {};
      route.paramNames.forEach((name, i) => { params[name] = m[i + 1]; });
      return { loader: route.loader, params };
    }
  }
  return null;
}

async function renderRoute(path, { direction = 'forward' } = {}) {
  const found = matchRoute(path);
  if (!found) {
    return;
  }

  const mod = await found.loader();
  const screenEl = document.createElement('div');
  screenEl.className = 'screen screen-enter';
  if (direction === 'back') {
    screenEl.style.animationName = 'screen-slide-in';
  }
  rootEl.appendChild(screenEl);

  const unmount = await mod.mount(screenEl, found.params);

  const previous = current;
  current = { path, unmount };

  if (previous?.screenEl) {
    previous.screenEl.classList.add('screen-leave');
    setTimeout(() => {
      previous.unmount?.();
      previous.screenEl.remove();
    }, 280);
  }

  current.screenEl = screenEl;
}

export function init(root) {
  rootEl = root;
  window.addEventListener('popstate', (e) => {
    renderRoute(location.pathname, { direction: 'back' });
  });
}

export function navigate(path, { replace = false } = {}) {
  if (replace) {
    history.replaceState({ path }, '', path);
  } else {
    history.pushState({ path }, '', path);
  }
  renderRoute(path, { direction: 'forward' });
}

export function start() {
  renderRoute(location.pathname, { direction: 'forward' });
}
