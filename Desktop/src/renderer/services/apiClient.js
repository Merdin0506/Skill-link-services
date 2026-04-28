import { BACKEND_BASE_URL, BACKEND_BASE_URLS, NETWORK_TIMEOUT_MS } from '../config/appConfig.js';

function createHeaders(options = {}) {
  return {
    'Content-Type': 'application/json',
    ...(options.headers || {})
  };
}

function polishMessage(message, fallback) {
  if (!message || typeof message !== 'string') {
    return fallback;
  }

  const cleaned = message.replace(/\s*\[DBG-[A-Z-]+\]\s*/g, '').trim();
  const normalized = cleaned.toLowerCase();

  if (normalized.includes('failed login attempts')) {
    return 'Too many login tries for now. Please wait a bit and try again.';
  }

  if (normalized.includes('invalid credentials')) {
    return 'That email or password does not look right.';
  }

  if (normalized.includes('not active')) {
    return 'Your account is not active right now. Please contact support.';
  }

  if (normalized.includes('unable to connect to the database') || normalized.includes('mysql')) {
    return 'The system is still connecting to the database. Please try again in a moment.';
  }

  return cleaned;
}

function parseErrorMessage(data, status) {
  const nestedError =
    data && typeof data.messages === 'object' && data.messages !== null
      ? data.messages.error || Object.values(data.messages)[0]
      : null;

  return polishMessage((
    data?.message ||
    nestedError ||
    (typeof data?.messages === 'string' ? data.messages : null) ||
    `Request failed (${status})`
  ), `Request failed (${status})`);
}

async function requestOnce(baseUrl, endpoint, options = {}) {
  const url = `${baseUrl.replace(/\/$/, '')}${endpoint}`;
  const controller = new AbortController();
  const timeoutMs = Number(options.timeoutMs) > 0 ? Number(options.timeoutMs) : NETWORK_TIMEOUT_MS;
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

  const response = await fetch(url, {
    method: options.method || 'GET',
    headers: createHeaders(options),
    body: options.body ? JSON.stringify(options.body) : undefined,
    signal: controller.signal
  }).finally(() => {
    clearTimeout(timeoutId);
  });

  const text = await response.text();
  let data = {};

  if (text) {
    try {
      data = JSON.parse(text);
    } catch {
      data = { raw: text };
    }
  }

  if (!response.ok) {
    throw new Error(parseErrorMessage(data, response.status));
  }

  return data;
}

export async function requestJson(endpoint, options = {}) {
  const baseUrls = [BACKEND_BASE_URL, ...BACKEND_BASE_URLS.filter((url) => url !== BACKEND_BASE_URL)];
  let lastError = null;

  for (const baseUrl of baseUrls) {
    try {
      return await requestOnce(baseUrl, endpoint, options);
    } catch (error) {
      lastError = error;

      if (error?.name === 'AbortError') {
        lastError = new Error('The server took too long to respond. Please try again.');
        continue;
      }

      if (error instanceof TypeError) {
        lastError = new Error('We could not reach the server. Please make sure the backend is running.');
        continue;
      }

      throw error;
    }
  }

  throw lastError || new Error('Failed to fetch');
}
