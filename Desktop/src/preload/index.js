const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('desktopApp', {
  getVersion: () => ipcRenderer.invoke('app:getVersion'),
  getBackendBaseUrl: () => ipcRenderer.invoke('app:getBackendBaseUrl'),
  pingBackend: () => ipcRenderer.invoke('app:pingBackend'),
  login: (credentials) => ipcRenderer.invoke('auth:login', credentials),
  register: (payload) => ipcRenderer.invoke('auth:register', payload),
  getProfile: (token) => ipcRenderer.invoke('auth:profile', token),
  logout: (token) => ipcRenderer.invoke('auth:logout', token),
  getDashboardData: (token) => ipcRenderer.invoke('dashboard:data', token),
  getDashboardStats: (token) => ipcRenderer.invoke('dashboard:stats', token),
  getDashboardAnalytics: (token) => ipcRenderer.invoke('dashboard:analytics', token),
  getDashboardBookings: (token, limit) => ipcRenderer.invoke('dashboard:bookings', token, limit)
});
