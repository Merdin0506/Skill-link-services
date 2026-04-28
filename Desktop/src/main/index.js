const path = require('path');

const {
  BACKEND_ROUTES,
  getBackendBaseUrl,
  getBackendBaseUrls,
  getBackendUrl,
  getFallbackFilePath
} = require('./config/appConfig');
const { createMainWindow } = require('./features/window/createMainWindow');
const { registerAppLifecycle } = require('./features/app/lifecycle');
const { registerDesktopInfoHandlers } = require('./features/app/desktopInfo.handlers');
const { registerAuthHandlers } = require('./features/auth/auth.handlers');
const { registerDashboardHandlers } = require('./features/dashboard/dashboard.handlers');
const { registerBackendNavigationHandlers } = require('./features/navigation/backendNavigation.handlers');

function bootstrapDesktopApp() {
  const preloadPath = path.join(__dirname, '..', '..', '..', 'preload.js');

  registerDesktopInfoHandlers({ getBackendBaseUrl, getBackendBaseUrls });
  registerBackendNavigationHandlers({ getBackendUrl, BACKEND_ROUTES });
  registerAuthHandlers({ getBackendBaseUrls });
  registerDashboardHandlers({ getBackendBaseUrls });

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
