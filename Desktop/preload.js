const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('desktopApp', {
  getVersion: () => ipcRenderer.invoke('app:getVersion'),
  getApiBaseUrl: () => ipcRenderer.invoke('app:getApiBaseUrl'),
  login: (credentials) => ipcRenderer.invoke('auth:login', credentials),
  getProfile: (token) => ipcRenderer.invoke('auth:profile', token)
});
