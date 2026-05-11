const { ipcMain, app } = require('electron');

async function pingBackend(baseUrls) {
  let lastError = null;

  for (const baseUrl of baseUrls) {
    const healthUrl = `${baseUrl}/api/health`;
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 3000);

    try {
      const response = await fetch(healthUrl, { method: 'GET', signal: controller.signal }).finally(() => {
        clearTimeout(timeoutId);
      });
      return { ok: response.ok, status: response.status, baseUrl };
    } catch (error) {
      clearTimeout(timeoutId);
      lastError = error;
    }
  }

  return {
    ok: false,
    status: 0,
    message: lastError?.message || 'Failed to fetch',
    baseUrl: baseUrls[0]
  };
}

function registerDesktopInfoHandlers({ getBackendBaseUrl, getBackendBaseUrls }) {
  ipcMain.handle('app:getVersion', () => app.getVersion());
  ipcMain.handle('app:getBackendBaseUrl', () => getBackendBaseUrl());
  ipcMain.handle('app:pingBackend', async () => pingBackend(getBackendBaseUrls()));
}

module.exports = {
  registerDesktopInfoHandlers
};
