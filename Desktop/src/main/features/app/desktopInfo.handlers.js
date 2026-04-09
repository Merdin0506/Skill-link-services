const { ipcMain, app } = require('electron');

async function pingBackend(baseUrl) {
  const loginUrl = `${baseUrl}/auth/login`;

  try {
    const response = await fetch(loginUrl, { method: 'GET' });
    return { ok: response.ok, status: response.status };
  } catch (error) {
    return { ok: false, status: 0, message: error.message };
  }
}

function registerDesktopInfoHandlers({ getBackendBaseUrl }) {
  ipcMain.handle('app:getVersion', () => app.getVersion());
  ipcMain.handle('app:getBackendBaseUrl', () => getBackendBaseUrl());
  ipcMain.handle('app:pingBackend', async () => pingBackend(getBackendBaseUrl()));
}

module.exports = {
  registerDesktopInfoHandlers
};
