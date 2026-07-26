// Real-time SSE client — Hissə 7. EventSource native Last-Event-ID izləməsi
// vasitəsilə reconnect zamanı buraxılmış hadisələri özü "catch-up" edir.
// Hər ekran öz connectSSE() çağırışını edir və router/tab keçidində .close()
// çağıraraq bağlantını təmizləyir (bax: order-detail.js, driver-feed.js, home.js).
export function connectSSE(channels, handlers, { onStatus } = {}) {
  const url = `/sse/stream.php?channels=${encodeURIComponent(channels.join(','))}`;
  let source = open();
  let closed = false;
  let hadError = false;

  function open() {
    const s = new EventSource(url);
    s.onopen = () => {
      if (hadError) {
        onStatus?.('reconnected');
      } else {
        onStatus?.('connected');
      }
      hadError = false;
    };
    s.onerror = () => {
      hadError = true;
      onStatus?.('reconnecting');
    };
    for (const [type, handler] of Object.entries(handlers)) {
      s.addEventListener(type, (event) => handler(JSON.parse(event.data)));
    }
    return s;
  }

  function onVisible() {
    if (closed || document.visibilityState !== 'visible') return;
    // iOS-da arxa fonda EventSource tez-tez sükut edir (xəta atmadan) —
    // ön plana qayıdanda bağlantını yeniləyirik, Last-Event-ID buraxılanları çatdırır.
    source.close();
    source = open();
  }
  document.addEventListener('visibilitychange', onVisible);

  return {
    close() {
      closed = true;
      source.close();
      document.removeEventListener('visibilitychange', onVisible);
    },
  };
}
