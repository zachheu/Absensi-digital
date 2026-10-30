import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = (__ENV.BASE_URL || '').replace(/\/$/, '');

export const options = {
  vus: Number(__ENV.VUS || 5),
  duration: __ENV.DURATION || '1m',
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<1500'],
  },
};

export function setup() {
  if (!BASE_URL || !__ENV.NPP || !__ENV.PASSWORD) {
    throw new Error('Set BASE_URL, NPP, dan PASSWORD.');
  }

  const loginPage = http.get(`${BASE_URL}/login`);
  check(loginPage, { 'login page 200': (r) => r.status === 200 });

  const token = loginPage.html('input[name=_token]').first().attr('value');

  if (!token) {
    throw new Error('Token CSRF tidak ditemukan di halaman login.');
  }

  const jar = http.cookieJar();
  const login = http.post(
    `${BASE_URL}/login`,
    { _token: token, npp: __ENV.NPP, password: __ENV.PASSWORD },
    { redirects: 0 },
  );

  check(login, { 'login redirect 302': (r) => r.status === 302 });

  if (login.status !== 302) {
    throw new Error(`Login gagal dengan status ${login.status}.`);
  }

  const cookies = jar.cookiesForURL(BASE_URL);
  const cookieHeader = Object.entries(cookies)
    .map(([name, values]) => `${name}=${values[0]}`)
    .join('; ');

  if (!cookieHeader) {
    throw new Error('Cookie sesi tidak ditemukan setelah login.');
  }

  return { cookieHeader };
}

export default function (data) {
  const headers = { Cookie: data.cookieHeader };
  const pages = ['/dashboard', '/activities', '/my-attendances'];
  const path = pages[Math.floor(Math.random() * pages.length)];

  const response = http.get(`${BASE_URL}${path}`, { headers });

  check(response, {
    'halaman 200': (r) => r.status === 200,
    'tidak diarahkan ke login': (r) => !r.url.endsWith('/login'),
  });

  sleep(1);
}
