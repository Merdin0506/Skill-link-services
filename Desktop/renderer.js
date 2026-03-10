const state = {
  token: null
};

function setStatus(message, type) {
  const status = document.getElementById('status');
  if (!status) {
    return;
  }

  status.className = '';
  if (type) {
    status.classList.add(type);
  }
  status.textContent = message || '';
}

function renderProfile(user) {
  const profileGrid = document.getElementById('profileGrid');
  const profileSection = document.getElementById('profileSection');
  const authSection = document.getElementById('authSection');

  if (!profileGrid || !profileSection || !authSection) {
    return;
  }

  const entries = [
    ['Name', `${user.first_name || ''} ${user.last_name || ''}`.trim() || '-'],
    ['Email', user.email || '-'],
    ['User Type', user.user_type || '-'],
    ['Status', user.status || '-'],
    ['Phone', user.phone || '-']
  ];

  profileGrid.innerHTML = entries
    .map(([key, value]) => `<div><div class="k">${key}</div><div class="v">${value}</div></div>`)
    .join('');

  authSection.classList.add('hidden');
  profileSection.classList.remove('hidden');
}

function showLoginForm() {
  const profileSection = document.getElementById('profileSection');
  const authSection = document.getElementById('authSection');
  const loginForm = document.getElementById('loginForm');

  if (profileSection) {
    profileSection.classList.add('hidden');
  }

  if (authSection) {
    authSection.classList.remove('hidden');
  }

  if (loginForm) {
    loginForm.reset();
  }
}

async function loadMeta() {
  const [version, apiBaseUrl] = await Promise.all([
    window.desktopApp.getVersion(),
    window.desktopApp.getApiBaseUrl()
  ]);

  const metaInfo = document.getElementById('metaInfo');
  if (metaInfo) {
    metaInfo.textContent = `Desktop v${version} | API ${apiBaseUrl}`;
  }
}

async function handleLoginSubmit(event) {
  event.preventDefault();

  const emailInput = document.getElementById('email');
  const passwordInput = document.getElementById('password');
  const loginButton = document.getElementById('loginButton');

  const email = emailInput ? emailInput.value.trim() : '';
  const password = passwordInput ? passwordInput.value : '';

  if (!email || !password) {
    setStatus('Please provide both email and password.', 'error');
    return;
  }

  if (loginButton) {
    loginButton.disabled = true;
    loginButton.textContent = 'Signing in...';
  }

  setStatus('Authenticating...', null);

  try {
    const response = await window.desktopApp.login({ email, password });
    const token = response?.data?.token;

    if (!token) {
      throw new Error('Login response did not include a token.');
    }

    state.token = token;
    const profileResponse = await window.desktopApp.getProfile(token);
    renderProfile(profileResponse?.data || {});
    setStatus('Login successful.', 'success');
  } catch (error) {
    state.token = null;
    showLoginForm();
    setStatus(error.message || 'Login failed.', 'error');
  } finally {
    if (loginButton) {
      loginButton.disabled = false;
      loginButton.textContent = 'Sign in';
    }
  }
}

function handleLogout() {
  state.token = null;
  showLoginForm();
  setStatus('Logged out locally.', null);
}

function boot() {
  const loginForm = document.getElementById('loginForm');
  const logoutButton = document.getElementById('logoutButton');

  if (loginForm) {
    loginForm.addEventListener('submit', handleLoginSubmit);
  }

  if (logoutButton) {
    logoutButton.addEventListener('click', handleLogout);
  }

  loadMeta().catch((error) => {
    setStatus(`Failed to load app info: ${error.message}`, 'error');
  });
}

boot();
