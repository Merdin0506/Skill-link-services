const { BrowserWindow, ipcMain } = require('electron');

function getFocusedWindow() {
  return BrowserWindow.getFocusedWindow() || BrowserWindow.getAllWindows()[0] || null;
}

function registerBackendNavigationHandlers({ getBackendUrl, BACKEND_ROUTES }) {
  ipcMain.handle('backend:openLogin', async () => {
    const win = getFocusedWindow();
    if (!win) {
      return { ok: false, message: 'No active window found.' };
    }

    await win.loadURL(getBackendUrl(BACKEND_ROUTES.LOGIN));
    return { ok: true };
  });

  ipcMain.handle('backend:openDashboard', async () => {
    const win = getFocusedWindow();
    if (!win) {
      return { ok: false, message: 'No active window found.' };
    }

    await win.loadURL(getBackendUrl(BACKEND_ROUTES.DASHBOARD));
    return { ok: true };
  });
}

module.exports = {
  registerBackendNavigationHandlers
};
