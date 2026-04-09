import { setStatus } from '../../core/status.js';

export async function autoOpenLoginWhenBackendReady(bridge) {
  const ping = await bridge.pingBackend();

  if (ping && ping.ok) {
    setStatus('Backend detected. Opening login...');
    await bridge.openBackendLogin();
    return;
  }

  setStatus('Backend UI unavailable. Start backend server, then click Open Backend Login.', 'error');
}
