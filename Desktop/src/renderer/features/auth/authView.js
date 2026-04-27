import { getElementById, updateText } from '../../core/dom.js';
import { clearSession, saveSession } from '../../core/storage.js';
import { requestJson } from '../../services/apiClient.js';
import { getDesktopBridge } from '../../services/desktopBridge.js';

async function requestOtpVerification(email) {
  const otp = window.prompt(`Enter the 6-digit OTP sent to ${email}`);
  if (!otp) {
    throw new Error('OTP verification was cancelled.');
  }

  const bridge = getDesktopBridge();
  return bridge
    ? bridge.verifyOtp({ email, otp: otp.trim() })
    : requestJson('/api/auth/verify-otp', {
        method: 'POST',
        body: { email, otp: otp.trim() }
      });
}

export function renderAuthView(onAuthenticated) {
  const authSection = getElementById('authSection');
  const loginForm = getElementById('loginForm');
  const authButton = getElementById('loginButton');
  const dashboardSection = getElementById('dashboardSection');
  const loginStatusElement = getElementById('loginStatus');

  function setLoginStatus(message, type = null) {
    if (!loginStatusElement) {
      return;
    }

    loginStatusElement.className = 'status-banner';
    if (!message) {
      loginStatusElement.classList.add('is-empty');
    } else if (type) {
      loginStatusElement.classList.add(type);
    }

    updateText(loginStatusElement, message || '');
  }

  if (!authSection || !loginForm || !authButton) {
    return;
  }

  authSection.classList.remove('hidden');
  if (dashboardSection) {
    dashboardSection.classList.add('hidden');
  }

  loginForm.onsubmit = async (event) => {
    event.preventDefault();

    const email = getElementById('email')?.value?.trim() || '';
    const password = getElementById('password')?.value || '';

    if (!email || !password) {
      setLoginStatus('Please provide both email and password.', 'error');
      return;
    }

    authButton.disabled = true;
    updateText(authButton, 'Signing in...');
    setLoginStatus('Authenticating...');

    try {
      const bridge = getDesktopBridge();
      const response = bridge
        ? await bridge.login({ email, password })
        : await requestJson('/api/auth/login', {
            method: 'POST',
            body: { email, password }
          });

      let finalResponse = response;
      if (response?.requires_otp) {
        setLoginStatus('Verification code sent. Waiting for OTP...', 'success');
        finalResponse = await requestOtpVerification(response?.data?.email || email);
      }

      const token = finalResponse?.data?.token;
      const user = finalResponse?.data?.user;

      if (!token || !user) {
        throw new Error('Login response did not include user details or token.');
      }

      saveSession(token, user);
      setLoginStatus('Login successful. Loading dashboard...', 'success');
      
      // Small delay to show success message, then switch to dashboard
      await new Promise(resolve => setTimeout(resolve, 500));
      
      // Call the authenticated callback and await it
      if (onAuthenticated) {
        await onAuthenticated({ token, user });
      }
    } catch (error) {
      clearSession();
      setLoginStatus(error.message || 'Login failed.', 'error');
    } finally {
      authButton.disabled = false;
      updateText(authButton, 'Login');
    }
  };
}
