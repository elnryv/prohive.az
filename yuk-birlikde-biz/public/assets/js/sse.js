// Real-time SSE client skeleti. Tam hadisə axını (feed/user/system kanalları,
// reconnect + catch-up) Faza 3-də qurulur — bax Hissə 7.

export class RealtimeClient {
  constructor() {
    this.source = null;
    this.handlers = new Map();
  }

  connect(channels) {
    if (this.source) {
      this.source.close();
    }
    this.source = new EventSource(`/sse/stream.php?channels=${encodeURIComponent(channels.join(','))}`);
    this.source.onerror = () => {
      // EventSource avtomatik reconnect edir; Faza 3-də bağlantı statusu zolağı əlavə olunacaq.
    };
    for (const [type, handler] of this.handlers) {
      this.source.addEventListener(type, (event) => handler(JSON.parse(event.data)));
    }
  }

  on(type, handler) {
    this.handlers.set(type, handler);
    if (this.source) {
      this.source.addEventListener(type, (event) => handler(JSON.parse(event.data)));
    }
  }

  close() {
    if (this.source) {
      this.source.close();
      this.source = null;
    }
  }
}

export const realtime = new RealtimeClient();
