const path = require('path');

const DEFAULT_BACKEND_BASE_URL = process.env.SKILLLINK_API_BASE_URL || 'http://127.0.0.1:8080';
const FALLBACK_BACKEND_BASE_URLS = ['http://127.0.0.1:8080', 'http://localhost:8080'];

function normalizeBaseUrl(url) {
  return (url || DEFAULT_BACKEND_BASE_URL).replace(/\/$/, '');
}

function getBackendBaseUrl() {
  return normalizeBaseUrl(process.env.SKILLLINK_API_BASE_URL || DEFAULT_BACKEND_BASE_URL);
}

function getBackendBaseUrls() {
  const urls = [getBackendBaseUrl(), ...FALLBACK_BACKEND_BASE_URLS];
  return [...new Set(urls.map(normalizeBaseUrl))];
}

function getBackendUrl(routePath) {
  return `${getBackendBaseUrl()}${routePath}`;
}

function getFallbackFilePath() {
  return path.join(__dirname, '..', '..', '..', 'index.html');
}

module.exports = {
  getBackendBaseUrl,
  getBackendBaseUrls,
  getBackendUrl,
  getFallbackFilePath
};
