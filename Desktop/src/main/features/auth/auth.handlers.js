const { ipcMain } = require('electron');
const { requestJson } = require('../../services/apiClient');

function registerAuthHandlers({ getBackendBaseUrl }) {
  ipcMain.handle('auth:login', async (_event, credentials) => {
    const payload = {
      email: credentials?.email || '',
      password: credentials?.password || ''
    };

    return requestJson(getBackendBaseUrl(), '/api/auth/login', {
      method: 'POST',
      body: payload
    });
  });

  ipcMain.handle('auth:register', async (_event, payload) => {
    return requestJson(getBackendBaseUrl(), '/api/auth/register', {
      method: 'POST',
      body: payload || {}
    });
  });

  ipcMain.handle('auth:profile', async (_event, token) => {
    return requestJson(getBackendBaseUrl(), '/api/auth/profile', {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  });

  ipcMain.handle('auth:logout', async (_event, token) => {
    return requestJson(getBackendBaseUrl(), '/api/auth/logout', {
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
