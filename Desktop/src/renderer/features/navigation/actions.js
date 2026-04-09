import { getElementById } from '../../core/dom.js';
import { setStatus } from '../../core/status.js';

async function openRoute(navigate) {
  const result = await navigate();
  if (!result?.ok) {
    setStatus(result?.message || 'Unable to open backend route.', 'error');
  }
}

export function wireNavigationActions(bridge) {
  const openLoginBtn = getElementById('openLoginBtn');
  const openDashboardBtn = getElementById('openDashboardBtn');

  if (openLoginBtn) {
    openLoginBtn.addEventListener('click', async () => {
      setStatus('Opening backend login...');
      await openRoute(() => bridge.openBackendLogin());
    });
  }

  if (openDashboardBtn) {
    openDashboardBtn.addEventListener('click', async () => {
      setStatus('Opening backend dashboard...');
      await openRoute(() => bridge.openBackendDashboard());
    });
  }
}
