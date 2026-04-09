const { app } = require('electron');

function registerAppLifecycle({ createWindow }) {
  app.whenReady().then(() => {
    createWindow();

    app.on('activate', () => {
      if (require('electron').BrowserWindow.getAllWindows().length === 0) {
        createWindow();
      }
    });
  });

  app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
      app.quit();
    }
  });
}

module.exports = {
  registerAppLifecycle
};
