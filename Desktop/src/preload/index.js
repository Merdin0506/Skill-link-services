const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('desktopApp', {
  getVersion: () => ipcRenderer.invoke('app:getVersion'),
  getBackendBaseUrl: () => ipcRenderer.invoke('app:getBackendBaseUrl'),
  pingBackend: () => ipcRenderer.invoke('app:pingBackend'),
  openBackendLogin: () => ipcRenderer.invoke('backend:openLogin'),
  openBackendDashboard: () => ipcRenderer.invoke('backend:openDashboard'),
  login: (credentials) => ipcRenderer.invoke('auth:login', credentials),
  register: (payload) => ipcRenderer.invoke('auth:register', payload),
  verifyOtp: (payload) => ipcRenderer.invoke('auth:verifyOtp', payload),
  resendOtp: (payload) => ipcRenderer.invoke('auth:resendOtp', payload),
  getProfile: (token) => ipcRenderer.invoke('auth:profile', token),
  updateProfile: (token, payload) => ipcRenderer.invoke('auth:updateProfile', token, payload),
  changePassword: (token, payload) => ipcRenderer.invoke('auth:changePassword', token, payload),
  logout: (token) => ipcRenderer.invoke('auth:logout', token),
  getDashboardData: (token) => ipcRenderer.invoke('dashboard:data', token),
  getDashboardStats: (token) => ipcRenderer.invoke('dashboard:stats', token),
  getDashboardAnalytics: (token) => ipcRenderer.invoke('dashboard:analytics', token),
  getDashboardBookings: (token, limit) => ipcRenderer.invoke('dashboard:bookings', token, limit),
  getAvailableJobs: (token, limit) => ipcRenderer.invoke('dashboard:availableJobs', token, limit),
  acceptJob: (token, bookingId) => ipcRenderer.invoke('dashboard:acceptJob', token, bookingId),
  completeJobWithPayment: (token, bookingId, payload) => ipcRenderer.invoke('dashboard:completeJobWithPayment', token, bookingId, payload)
});
