import { setStatus } from '../core/status.js';

function createUnavailableBridge() {
  const unavailable = async () => {
    throw new Error('Desktop bridge unavailable. Restart the Desktop app.');
  };

  return {
    getVersion: unavailable,
    getBackendBaseUrl: unavailable,
    pingBackend: unavailable,
    login: unavailable,
    register: unavailable,
    verifyOtp: unavailable,
    resendOtp: unavailable,
    getProfile: unavailable,
    logout: unavailable,
    getDashboardData: unavailable,
    getDashboardStats: unavailable,
    getDashboardAnalytics: unavailable,
    getDashboardBookings: unavailable,
  };
}

export function getDesktopBridge() {
  return window.desktopApp || null;
}

export function createFallbackBridge() {
  return createUnavailableBridge();
}
