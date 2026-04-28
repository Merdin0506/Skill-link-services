import { getElementById } from '../../core/dom.js';

function ensureOtpDialog() {
  let root = getElementById('otpDialogRoot');
  if (root) {
    return root;
  }

  root = document.createElement('div');
  root.id = 'otpDialogRoot';
  root.className = 'otp-dialog-root hidden';
  root.innerHTML = `
    <div class="otp-dialog-backdrop"></div>
    <div class="otp-dialog-card" role="dialog" aria-modal="true" aria-labelledby="otpDialogTitle">
      <div class="otp-dialog-icon"><i class="fas fa-shield-alt"></i></div>
      <h3 id="otpDialogTitle">Enter verification code</h3>
      <p id="otpDialogMessage" class="otp-dialog-message"></p>
      <form id="otpDialogForm" class="otp-dialog-form">
        <input
          id="otpDialogInput"
          class="otp-dialog-input"
          type="text"
          inputmode="numeric"
          autocomplete="one-time-code"
          maxlength="6"
          placeholder="6-digit OTP"
          required
        />
        <p id="otpDialogError" class="otp-dialog-error hidden"></p>
        <div class="otp-dialog-actions">
          <button type="button" id="otpDialogCancel" class="ghost-button">Cancel</button>
          <button type="submit" id="otpDialogSubmit" class="action-button">Verify</button>
        </div>
      </form>
    </div>
  `;

  document.body.appendChild(root);
  return root;
}

export function requestOtpCode(email) {
  const root = ensureOtpDialog();
  const form = getElementById('otpDialogForm');
  const input = getElementById('otpDialogInput');
  const cancelButton = getElementById('otpDialogCancel');
  const submitButton = getElementById('otpDialogSubmit');
  const message = getElementById('otpDialogMessage');
  const error = getElementById('otpDialogError');

  if (!form || !input || !cancelButton || !submitButton || !message || !error) {
    return Promise.reject(new Error('OTP dialog failed to initialize.'));
  }

  message.textContent = `Enter the 6-digit code sent to ${email}.`;
  error.textContent = '';
  error.classList.add('hidden');
  input.value = '';
  root.classList.remove('hidden');

  return new Promise((resolve, reject) => {
    const cleanup = () => {
      root.classList.add('hidden');
      form.onsubmit = null;
      cancelButton.onclick = null;
    };

    cancelButton.onclick = () => {
      cleanup();
      reject(new Error('OTP verification was cancelled.'));
    };

    form.onsubmit = (event) => {
      event.preventDefault();
      const otp = input.value.trim();

      if (!/^\d{6}$/.test(otp)) {
        error.textContent = 'Please enter a valid 6-digit code.';
        error.classList.remove('hidden');
        return;
      }

      cleanup();
      resolve(otp);
    };

    requestAnimationFrame(() => input.focus());
  });
}
