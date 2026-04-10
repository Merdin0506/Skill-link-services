import { BACKEND_BASE_URL, BACKEND_BASE_URLS } from '../config/appConfig.js';

function createHeaders(options = {}) {
  return {
    'Content-Type': 'application/json',
    ...(options.headers || {})
  };
}

function parseErrorMessage(data, status) {
  const nestedError =
    data && typeof data.messages === 'object' && data.messages !== null
      ? data.messages.error || Object.values(data.messages)[0]
      : null;

  return (
    data?.message ||
    nestedError ||
    (typeof data?.messages === 'string' ? data.messages : null) ||
    `Request failed (${status})`
  );
}

async function requestOnce(baseUrl, endpoint, options = {}) {
  const url = `${baseUrl.replace(/\/$/, '')}${endpoint}`;
  const response = await fetch(url, {
    method: options.method || 'GET',
    headers: createHeaders(options),
    body: options.body ? JSON.stringify(options.body) : undefined
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

      if (error instanceof TypeError) {
        continue;
      }

      throw error;
    }
  }

  throw lastError || new Error('Failed to fetch');
}
