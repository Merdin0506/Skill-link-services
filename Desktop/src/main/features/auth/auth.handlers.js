const { ipcMain } = require('electron');
const { requestJson } = require('../../services/apiClient');

function registerAuthHandlers({ getBackendBaseUrls }) {
  ipcMain.handle('auth:login', async (_event, credentials) => {
    const payload = {
      email: credentials?.email || '',
      password: credentials?.password || ''
    };

    return requestJson(getBackendBaseUrls(), '/api/auth/login', {
      method: 'POST',
      body: payload
    });
  });

  ipcMain.handle('auth:register', async (_event, payload) => {
    return requestJson(getBackendBaseUrls(), '/api/auth/register', {
      method: 'POST',
      body: payload || {}
    });
  });

  ipcMain.handle('auth:profile', async (_event, token) => {
    return requestJson(getBackendBaseUrls(), '/api/auth/profile', {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  });

  ipcMain.handle('auth:logout', async (_event, token) => {
    return requestJson(getBackendBaseUrls(), '/api/auth/logout', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  });
}

module.exports = {
  registerAuthHandlers,
};
