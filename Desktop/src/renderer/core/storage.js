const AUTH_TOKEN_KEY = 'skilllink.desktop.authToken';
const USER_KEY = 'skilllink.desktop.user';

export function saveSession(token, user) {
  window.localStorage.setItem(AUTH_TOKEN_KEY, token);
  window.localStorage.setItem(USER_KEY, JSON.stringify(user || {}));
}

export function getSession() {
  const token = window.localStorage.getItem(AUTH_TOKEN_KEY);
  const userJson = window.localStorage.getItem(USER_KEY);

  return {
    token,
    user: userJson ? JSON.parse(userJson) : null
  };
}

export function clearSession() {
  window.localStorage.removeItem(AUTH_TOKEN_KEY);
  window.localStorage.removeItem(USER_KEY);
}
