const { ipcMain } = require('electron');
const { requestJson } = require('../../services/apiClient');

function registerDashboardHandlers({ getBackendBaseUrls }) {
  ipcMain.handle('dashboard:data', async (_event, token) => {
    return requestJson(getBackendBaseUrls(), '/api/dashboard/data', {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  });

  ipcMain.handle('dashboard:stats', async (_event, token) => {
    return requestJson(getBackendBaseUrls(), '/api/dashboard/stats', {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  });

  ipcMain.handle('dashboard:analytics', async (_event, token) => {
    return requestJson(getBackendBaseUrls(), '/api/dashboard/analytics', {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`}
    });
  });

  ipcMain.handle('dashboard:bookings', async (_event, token, limit = 10) => {
    const query = new URLSearchParams({ limit: String(limit) });
    return requestJson(getBackendBaseUrls(), `/api/dashboard/bookings?${query.toString()}`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  });

  ipcMain.handle('dashboard:availableJobs', async (_event, token, limit = 50) => {
    const query = new URLSearchParams({ limit: String(limit) });
    return requestJson(getBackendBaseUrls(), `/api/bookings/available?${query.toString()}`, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  });

  ipcMain.handle('dashboard:acceptJob', async (_event, token, bookingId) => {
    return requestJson(getBackendBaseUrls(), `/api/bookings/${bookingId}/accept`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  });

  ipcMain.handle('dashboard:completeJobWithPayment', async (_event, token, bookingId, payload) => {
    return requestJson(getBackendBaseUrls(), `/api/bookings/${bookingId}/complete-with-payment`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`
      },
      body: payload || {}
    });
  });
}

module.exports = {
  registerDashboardHandlers,
};
