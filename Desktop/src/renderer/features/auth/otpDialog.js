import { getElementById } from '../../core/dom.js';

function ensureDialogRoot() {
  let root = getElementById('otpDialogRoot');
  if (root) {
    return root;
  }

  root = document.createElement('div');
  root.id = 'otpDialogRoot';
  root.className = 'otp-dialog-root hidden';
  document.body.appendChild(root);
  return root;
}

function openDialog(markup) {
  const root = ensureDialogRoot();
  root.innerHTML = markup;
  root.classList.remove('hidden');
  return root;
}

function closeDialog(root) {
  root.classList.add('hidden');
}

export function requestOtpCode(email, options = {}) {
  const {
    title = 'Enter verification code',
    iconClass = 'fas fa-shield-alt',
    submitLabel = 'Verify',
    message = `Enter the 6-digit code sent to ${email}.`
  } = options;

  const root = openDialog(`
    <div class="otp-dialog-backdrop"></div>
    <div class="otp-dialog-card" role="dialog" aria-modal="true" aria-labelledby="otpDialogTitle">
      <div class="otp-dialog-icon"><i class="${iconClass}"></i></div>
      <h3 id="otpDialogTitle">${title}</h3>
      <p id="otpDialogMessage" class="otp-dialog-message">${message}</p>
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
          <button type="submit" id="otpDialogSubmit" class="action-button">${submitLabel}</button>
        </div>
      </form>
    </div>
  `);

  const form = getElementById('otpDialogForm');
  const input = getElementById('otpDialogInput');
  const cancelButton = getElementById('otpDialogCancel');
  const error = getElementById('otpDialogError');

  if (!form || !input || !cancelButton || !error) {
    return Promise.reject(new Error('OTP dialog failed to initialize.'));
  }

  error.textContent = '';
  error.classList.add('hidden');
  input.value = '';

  return new Promise((resolve, reject) => {
    const cleanup = () => {
      form.onsubmit = null;
      cancelButton.onclick = null;
      closeDialog(root);
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

export function requestNewPassword() {
  const root = openDialog(`
    <div class="otp-dialog-backdrop"></div>
    <div class="otp-dialog-card" role="dialog" aria-modal="true" aria-labelledby="passwordDialogTitle">
      <div class="otp-dialog-icon"><i class="fas fa-key"></i></div>
      <h3 id="passwordDialogTitle">Create new password</h3>
      <p class="otp-dialog-message">Enter your new password below.</p>
      <form id="passwordDialogForm" class="otp-dialog-form">
        <input
          id="passwordDialogNewPassword"
          class="otp-dialog-input"
          type="password"
          autocomplete="new-password"
          minlength="8"
          placeholder="New password"
          required
        />
        <input
          id="passwordDialogConfirmPassword"
          class="otp-dialog-input"
          type="password"
          autocomplete="new-password"
          minlength="8"
          placeholder="Confirm new password"
          required
        />
        <p id="passwordDialogError" class="otp-dialog-error hidden"></p>
        <div class="otp-dialog-actions">
          <button type="button" id="passwordDialogCancel" class="ghost-button">Cancel</button>
          <button type="submit" id="passwordDialogSubmit" class="action-button">Update Password</button>
        </div>
      </form>
    </div>
  `);

  const form = getElementById('passwordDialogForm');
  const newPasswordInput = getElementById('passwordDialogNewPassword');
  const confirmPasswordInput = getElementById('passwordDialogConfirmPassword');
  const cancelButton = getElementById('passwordDialogCancel');
  const error = getElementById('passwordDialogError');

  if (!form || !newPasswordInput || !confirmPasswordInput || !cancelButton || !error) {
    return Promise.reject(new Error('Password dialog failed to initialize.'));
  }

  error.textContent = '';
  error.classList.add('hidden');

  return new Promise((resolve, reject) => {
    const cleanup = () => {
      form.onsubmit = null;
      cancelButton.onclick = null;
      closeDialog(root);
    };

    cancelButton.onclick = () => {
      cleanup();
      reject(new Error('Password reset was cancelled.'));
    };

    form.onsubmit = (event) => {
      event.preventDefault();

      const newPassword = newPasswordInput.value;
      const confirmPassword = confirmPasswordInput.value;

      if (newPassword.length < 8) {
        error.textContent = 'New password must be at least 8 characters.';
        error.classList.remove('hidden');
        return;
      }

      if (newPassword !== confirmPassword) {
        error.textContent = 'Passwords do not match.';
        error.classList.remove('hidden');
        return;
      }

      cleanup();
      resolve({
        new_password: newPassword,
        confirm_password: confirmPassword
      });
    };

    requestAnimationFrame(() => newPasswordInput.focus());
  });
}
