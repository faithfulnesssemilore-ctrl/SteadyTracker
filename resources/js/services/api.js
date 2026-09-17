

import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || window.location.origin,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

// Ensures a fresh CSRF cookie exists before any state-changing request.
// Sanctum's SPA flow requires this call before login/register/logout.
export async function ensureCsrfCookie() {
  await api.get('/sanctum/csrf-cookie');
}

export function getApiError(error, fallback) {
  const response = error.response?.data;
  const fieldErrors = response?.errors ?? {};
  const firstFieldError = Object.values(fieldErrors).flat()[0];

  return {
    message: firstFieldError ?? response?.message ?? fallback,
    fieldErrors,
    status: error.response?.status ?? null,
  };
}

export default api;