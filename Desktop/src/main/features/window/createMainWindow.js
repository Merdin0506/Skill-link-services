const { BrowserWindow } = require('electron');

function createMainWindow({ preloadPath, initialUrl, fallbackFilePath, initialFilePath }) {
  const mainWindow = new BrowserWindow({
    width: 1280,
    height: 840,
    minWidth: 980,
    minHeight: 640,
    webPreferences: {
      preload: preloadPath,
      contextIsolation: true,
      nodeIntegration: false
    }
  });

  mainWindow.webContents.on('did-fail-load', (_event, _errorCode, _errorDescription, _validatedURL, isMainFrame) => {
    if (isMainFrame) {
      mainWindow.loadFile(fallbackFilePath);
    }
  });

  if (initialUrl) {
    mainWindow.loadURL(initialUrl);
  } else if (initialFilePath) {
    mainWindow.loadFile(initialFilePath);
  } else {
    mainWindow.loadFile(fallbackFilePath);
  }

  return mainWindow;
}

module.exports = {
  createMainWindow
};
