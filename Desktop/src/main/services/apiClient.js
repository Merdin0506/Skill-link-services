async function requestJson(baseUrl, endpoint, options = {}) {
  const url = `${baseUrl.replace(/\/$/, '')}${endpoint}`;
  const response = await fetch(url, {
    method: options.method || 'GET',
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers || {})
    },
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
    const nestedError =
      data && typeof data.messages === 'object' && data.messages !== null
        ? data.messages.error || Object.values(data.messages)[0]
        : null;

    const message =
      data?.message ||
      nestedError ||
      (typeof data?.messages === 'string' ? data.messages : null) ||
      `Request failed (${response.status})`;

    throw new Error(message);
  }

  return data;
}

module.exports = {
  requestJson,
};
