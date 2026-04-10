const { ipcMain, app } = require('electron');

async function pingBackend(baseUrls) {
  let lastError = null;

  for (const baseUrl of baseUrls) {
    const loginUrl = `${baseUrl}/auth/login`;

    try {
      const response = await fetch(loginUrl, { method: 'GET' });
      return { ok: response.ok, status: response.status, baseUrl };
    } catch (error) {
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
