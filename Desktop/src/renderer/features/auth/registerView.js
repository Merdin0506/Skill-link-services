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

function collectRegisterPayload(registerForm) {
  const formData = new FormData(registerForm);
  const payload = {};

  for (const [key, value] of formData.entries()) {
    if (key === 'skills[]') {
      if (!Array.isArray(payload.skills)) {
        payload.skills = [];
      }

      payload.skills.push(value);
      continue;
    }

    if (Object.prototype.hasOwnProperty.call(payload, key)) {
      if (!Array.isArray(payload[key])) {
        payload[key] = [payload[key]];
      }

      payload[key].push(value);
      continue;
    }

    payload[key] = value;
  }

  return payload;
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
  const body = document.body;

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
    const experienceInput = getElementById('experience_years');
    const skillCards = document.querySelectorAll('.skill-card');

    if (userType === 'worker') {
      workerFields?.classList.remove('hidden');
      experienceInput?.setAttribute('required', '');
    } else {
      workerFields?.classList.add('hidden');
      experienceInput?.removeAttribute('required');
    }

    skillCards.forEach((card) => {
      const checkbox = card.querySelector('input[type="checkbox"]');
      if (!checkbox) {
        return;
      }

      card.classList.toggle('is-selected', checkbox.checked);
      checkbox.onchange = () => {
        card.classList.toggle('is-selected', checkbox.checked);
      };
    });
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
    body?.classList.add('register-mode');
    setRegisterStatus('', null);
  }

  // Show login, hide register
  function showLogin() {
    registerSection?.classList.add('hidden');
    authSection?.classList.remove('hidden');
    body?.classList.remove('register-mode');
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

    const payload = collectRegisterPayload(registerForm);

    if (payload.user_type === 'worker' && (!Array.isArray(payload.skills) || payload.skills.length === 0)) {
      setRegisterStatus('Please select at least one skill.', 'error');
      return;
    }

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
        const otpResponse = await requestOtpVerification(response?.data?.email || payload.email);

        if (otpResponse?.approval_required) {
          setRegisterStatus(otpResponse.message || 'Registration submitted successfully. Please wait for admin approval of your worker application. The admin will contact you through your email once reviewed. You cannot log in until approval is completed.', 'success');
          setTimeout(() => {
            showLogin();
            registerForm.reset();
          }, 1000);
          return;
        }

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
