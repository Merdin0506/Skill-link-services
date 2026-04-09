const path = require('path');

const {
  getBackendBaseUrl,
  getFallbackFilePath
} = require('./config/appConfig');
const { createMainWindow } = require('./features/window/createMainWindow');
const { registerAppLifecycle } = require('./features/app/lifecycle');
const { registerDesktopInfoHandlers } = require('./features/app/desktopInfo.handlers');
const { registerAuthHandlers } = require('./features/auth/auth.handlers');
const { registerDashboardHandlers } = require('./features/dashboard/dashboard.handlers');

function bootstrapDesktopApp() {
  const preloadPath = path.join(__dirname, '..', '..', '..', 'preload.js');

  registerDesktopInfoHandlers({ getBackendBaseUrl });
  registerAuthHandlers({ getBackendBaseUrl });
  registerDashboardHandlers({ getBackendBaseUrl });

  registerAppLifecycle({
    createWindow: () =>
      createMainWindow({
        preloadPath,
        initialFilePath: getFallbackFilePath(),
        fallbackFilePath: getFallbackFilePath()
      })
  });
}

bootstrapDesktopApp();
