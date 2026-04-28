import { getElementById } from '../../core/dom.js';
import { requestJson } from '../../services/apiClient.js';
import { getDesktopBridge } from '../../services/desktopBridge.js';
import { requestOtpCode } from './otpDialog.js';

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

export function renderRegisterView(onRegisterSuccess) {
  const registerSection = getElementById('registerSection');
  const authSection = getElementById('authSection');
  const registerForm = getElementById('registerForm');
  const userTypeSelect = getElementById('user_type');
  const workerFields = getElementById('worker-fields');
  const loginLink = getElementById('loginLink');
  const registerLink = getElementById('registerLink');
  const registerStatusElement = getElementById('registerStatus');

  if (!registerSection || !registerForm) {
    return;
  }

  function setRegisterStatus(message, type = null) {
    if (!registerStatusElement) return;
    registerStatusElement.className = 'status-banner';
    if (!message) {
      registerStatusElement.classList.add('is-empty');
    } else if (type) {
      registerStatusElement.classList.add(type);
    }
    registerStatusElement.textContent = message || '';
  }

  // Toggle worker fields based on account type
  function toggleWorkerFields() {
    const userType = userTypeSelect?.value;
    const skillsInput = getElementById('skills');
    const experienceInput = getElementById('experience_years');

    if (userType === 'worker') {
      workerFields?.classList.remove('hidden');
      skillsInput?.setAttribute('required', '');
      experienceInput?.setAttribute('required', '');
    } else {
      workerFields?.classList.add('hidden');
      skillsInput?.removeAttribute('required');
      experienceInput?.removeAttribute('required');
    }
  }

  // Toggle password visibility
  function setupPasswordToggles() {
    const toggleButtons = registerSection.querySelectorAll('.toggle-password');
    toggleButtons.forEach((button) => {
      button.onclick = (e) => {
        e.preventDefault();
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

  // Show register, hide login
  function showRegister() {
    authSection?.classList.add('hidden');
    registerSection?.classList.remove('hidden');
    setRegisterStatus('', null);
  }

  // Show login, hide register
  function showLogin() {
    registerSection?.classList.add('hidden');
    authSection?.classList.remove('hidden');
    setRegisterStatus('', null);
  }

  // Handle account type change
  if (userTypeSelect) {
    userTypeSelect.onchange = toggleWorkerFields;
  }

  // Toggle links
  if (loginLink) {
    loginLink.addEventListener('click', (e) => {
      e.preventDefault();
      showLogin();
    });
  }

  if (registerLink) {
    registerLink.addEventListener('click', (e) => {
      e.preventDefault();
      showRegister();
    });
  }

  // Handle register form submission
  registerForm.onsubmit = async (e) => {
    e.preventDefault();
    setRegisterStatus('Registering...', null);

    const formData = new FormData(registerForm);
    const payload = Object.fromEntries(formData);

    try {
      const bridge = getDesktopBridge();
      const response = bridge
        ? await bridge.register(payload)
        : await requestJson('/api/auth/register', {
            method: 'POST',
            body: payload
          });

      if (response?.requires_otp) {
        setRegisterStatus('Verification code sent. Waiting for OTP...', 'success');
        await requestOtpVerification(response?.data?.email || payload.email);
        setRegisterStatus('Registration verified. You can now log in.', 'success');
        setTimeout(() => {
          if (onRegisterSuccess) {
            onRegisterSuccess(response);
          }
          showLogin();
          registerForm.reset();
        }, 1000);
      } else {
        setRegisterStatus(response?.message || 'Registration failed.', 'error');
      }
    } catch (error) {
      setRegisterStatus(error.message || 'Registration error.', 'error');
    }
  };

  setupPasswordToggles();
  toggleWorkerFields();
}
