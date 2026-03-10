const { app, BrowserWindow, ipcMain } = require('electron');
const path = require('path');

const DEFAULT_API_BASE_URL = process.env.SKILLLINK_API_BASE_URL || 'http://localhost:8080';

async function apiRequest(endpoint, options = {}) {
  const baseUrl = (process.env.SKILLLINK_API_BASE_URL || DEFAULT_API_BASE_URL).replace(/\/$/, '');
  const url = `${baseUrl}${endpoint}`;

  const response = await fetch(url, {
    method: options.method || 'GET',
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers || {})
    },
    body: options.body ? JSON.stringify(options.body) : undefined
  });

  const text = await response.text();
  let data;

  try {
    data = text ? JSON.parse(text) : {};
  } catch (error) {
    data = { raw: text };
  }

  if (!response.ok) {
    const message =
      data?.message ||
      (typeof data?.messages === 'string' ? data.messages : null) ||
      `Request failed (${response.status})`;
    throw new Error(message);
  }

  return data;
}

function createMainWindow() {
  const mainWindow = new BrowserWindow({
    width: 1200,
    height: 800,
    minWidth: 980,
    minHeight: 640,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false
    }
  });

  mainWindow.loadFile(path.join(__dirname, 'index.html'));
}

app.whenReady().then(() => {
  createMainWindow();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createMainWindow();
    }
  });
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

ipcMain.handle('app:getVersion', () => app.getVersion());
ipcMain.handle('app:getApiBaseUrl', () => process.env.SKILLLINK_API_BASE_URL || DEFAULT_API_BASE_URL);

ipcMain.handle('auth:login', async (_event, credentials) => {
  const payload = {
    email: credentials?.email || '',
    password: credentials?.password || ''
  };

  return apiRequest('/api/auth/login', {
    method: 'POST',
    body: payload
  });
});

ipcMain.handle('auth:profile', async (_event, token) => {
  return apiRequest('/api/auth/profile', {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${token}`
    }
  });
});
