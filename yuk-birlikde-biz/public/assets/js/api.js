const BASE = '/api/v1';

export class ApiError extends Error {
  constructor(code, message, status) {
    super(message);
    this.code = code;
    this.status = status;
  }
}

function readCookie(name) {
  const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
  return match ? decodeURIComponent(match[1]) : '';
}

async function request(method, path, body) {
  const headers = { 'Content-Type': 'application/json' };
  if (method !== 'GET') {
    headers['X-CSRF-Token'] = readCookie('ybb_csrf');
  }

  const res = await fetch(BASE + path, {
    method,
    headers,
    credentials: 'same-origin',
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  let json;
  try {
    json = await res.json();
  } catch {
    throw new ApiError('BAD_RESPONSE', 'Serverdən cavab alına bilmədi.', res.status);
  }

  if (!json.ok) {
    throw new ApiError(json.error?.code ?? 'UNKNOWN', json.error?.message ?? 'Xəta baş verdi.', res.status);
  }

  return json.data;
}

export const api = {
  get: (path) => request('GET', path),
  post: (path, body) => request('POST', path, body ?? {}),
  patch: (path, body) => request('PATCH', path, body ?? {}),
  del: (path, body) => request('DELETE', path, body ?? {}),
};
