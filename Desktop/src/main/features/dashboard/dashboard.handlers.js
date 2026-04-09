const { ipcMain } = require('electron');
const { requestJson } = require('../../services/apiClient');

function registerDashboardHandlers({ getBackendBaseUrl }) {
  ipcMain.handle('dashboard:data', async (_event, token) => {
    return requestJson(getBackendBaseUrl(), '/api/dashboard/data', {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  });

  ipcMain.handle('dashboard:stats', async (_event, token) => {
    return requestJson(getBackendBaseUrl(), '/api/dashboard/stats', {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  });

  ipcMain.handle('dashboard:analytics', async (_event, token) => {
    return requestJson(getBackendBaseUrl(), '/api/dashboard/analytics', {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`}
    });
  });

  ipcMain.handle('dashboard:bookings', async (_event, token, limit = 10) => {
    const query = new URLSearchParams({ limit: String(limit) });
    return requestJson(getBackendBaseUrl(), `/api/dashboard/bookings?${query.toString()}`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  });
}

module.exports = {
  registerDashboardHandlers,
};
