import { getElementById, updateText } from '../../core/dom.js';
import { clearSession, saveSession } from '../../core/storage.js';
import { requestJson } from '../../services/apiClient.js';
import { getDesktopBridge } from '../../services/desktopBridge.js';
import { requestOtpCode, requestNewPassword } from './otpDialog.js';

async function requestOtpVerification(email) {
  const otp = await requestOtpCode(email);

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
  const forgotPasswordButton = getElementById('forgotPasswordButton');
  const body = document.body;

  function setupPasswordToggles() {
    const toggleButtons = authSection?.querySelectorAll('.toggle-password');
    toggleButtons?.forEach((button) => {
      button.onclick = (event) => {
        event.preventDefault();
        const targetId = button.getAttribute('data-target');
        const input = getElementById(targetId);
        if (input) {
          const isHidden = input.type === 'password';
          input.type = isHidden ? 'text' : 'password';
          button.classList.toggle('is-visible', isHidden);
        }
      };
    });
  }

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
  body?.classList.remove('register-mode');
  if (dashboardSection) {
    dashboardSection.classList.add('hidden');
  }
  setupPasswordToggles();

  if (forgotPasswordButton) {
    forgotPasswordButton.onclick = async () => {
      const email = getElementById('email')?.value?.trim() || '';

      if (!email) {
        setLoginStatus('Enter your email first, then click Forgot password.', 'error');
        return;
    }

    authButton.disabled = true;
    forgotPasswordButton.disabled = true;
    setLoginStatus('Sending password reset code...');

    try {
      const bridge = getDesktopBridge();
      const requestResponse = bridge
        ? await bridge.requestPasswordReset({ email })
        : await requestJson('/api/auth/forgot-password/request', {
            method: 'POST',
            body: { email }
          });

      setLoginStatus(requestResponse?.message || 'Password reset code sent. Enter the code to continue.', 'success');

      const otp = await requestOtpCode(email, {
        title: 'Verify reset code',
        iconClass: 'fas fa-envelope-open-text',
        submitLabel: 'Continue',
        message: `Enter the 6-digit reset code sent to ${email}.`
      });
      const passwordDetails = await requestNewPassword();
      const resetResponse = bridge
        ? await bridge.resetPasswordWithOtp({ email, otp, ...passwordDetails })
        : await requestJson('/api/auth/forgot-password/reset', {
            method: 'POST',
            body: { email, otp, ...passwordDetails }
          });

      const passwordInput = getElementById('password');
      if (passwordInput) {
        passwordInput.value = '';
        passwordInput.focus();
      }

      setLoginStatus(resetResponse?.message || 'Password reset successful. You can now sign in.', 'success');
    } catch (error) {
      setLoginStatus(error.message || 'Failed to reset password.', 'error');
    } finally {
      authButton.disabled = false;
      forgotPasswordButton.disabled = false;
    }
    };
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

      if (finalResponse?.approval_required) {
        setLoginStatus(finalResponse.message || 'Your worker account is pending approval.', 'error');
        return;
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
