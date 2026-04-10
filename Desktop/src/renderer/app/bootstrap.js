import { loadMeta } from '../features/meta/loadMeta.js';
import { renderAuthView } from '../features/auth/authView.js';
import { renderRegisterView } from '../features/auth/registerView.js';
import { renderDashboardView } from '../features/dashboard/dashboardView.js';
import { bindAuthNavigation } from '../features/auth/navigation.js';
import { getSession } from '../core/storage.js';

async function bootstrap() {
  document.body.classList.add('auth-mode');

  try {
    renderAuthView(async (currentSession) => {
      await renderDashboardView(currentSession);
    });
  } catch (error) {
    console.error('Auth view failed to initialize:', error);
  }

  try {
    renderRegisterView(async () => {
      // Registration success returns the user to the login form.
    });
  } catch (error) {
    console.error('Register view failed to initialize:', error);
  }

  bindAuthNavigation();

  void loadMeta().catch(() => {
    // Keep the auth UI usable even if backend status cannot be checked.
  });

  const session = getSession();
  if (session.token) {
    await renderDashboardView(session);
  }
}

bootstrap();
