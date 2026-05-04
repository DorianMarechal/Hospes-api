import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export const options = {
  stages: [
    { duration: '30s', target: 20 },   // ramp up
    { duration: '1m', target: 50 },    // steady
    { duration: '30s', target: 100 },  // peak
    { duration: '30s', target: 0 },    // ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],  // 95% des requêtes < 500ms
    errors: ['rate<0.05'],              // < 5% d'erreurs
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

function getAuthToken() {
  const res = http.post(`${BASE_URL}/api/login_check`, JSON.stringify({
    username: 'loadtest@hospes.io',
    password: 'LoadTest!2026',
  }), { headers: { 'Content-Type': 'application/json' } });

  if (res.status === 200) {
    return JSON.parse(res.body).token;
  }
  return null;
}

export default function () {
  // Public endpoints (no auth)
  const lodgingsRes = http.get(`${BASE_URL}/api/lodgings`);
  check(lodgingsRes, { 'lodgings 200': (r) => r.status === 200 }) || errorRate.add(1);

  const checkin = new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0];
  const checkout = new Date(Date.now() + 17 * 86400000).toISOString().split('T')[0];

  const searchRes = http.get(
    `${BASE_URL}/api/availability?checkin=${checkin}&checkout=${checkout}&city=Paris`
  );
  check(searchRes, { 'search 200': (r) => r.status === 200 }) || errorRate.add(1);

  // Authenticated endpoints
  const token = getAuthToken();
  if (token) {
    const headers = {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    };

    const meRes = http.get(`${BASE_URL}/api/auth/me`, { headers });
    check(meRes, { 'me 200': (r) => r.status === 200 }) || errorRate.add(1);

    const bookingsRes = http.get(`${BASE_URL}/api/me/bookings`, { headers });
    check(bookingsRes, { 'bookings 200': (r) => r.status === 200 }) || errorRate.add(1);

    const notifRes = http.get(`${BASE_URL}/api/me/notifications`, { headers });
    check(notifRes, { 'notifications 200': (r) => r.status === 200 }) || errorRate.add(1);
  }

  sleep(1);
}
