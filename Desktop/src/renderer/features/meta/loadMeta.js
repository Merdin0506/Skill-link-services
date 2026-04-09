import { getElementById, updateText } from '../../core/dom.js';
import { BACKEND_BASE_URL } from '../../config/appConfig.js';
import { getDesktopBridge } from '../../services/desktopBridge.js';

export async function loadMeta() {
  const metaElement = getElementById('metaInfo');

  if (!metaElement) {
    return;
  }

  updateText(metaElement, `Desktop ready | API ${BACKEND_BASE_URL}`);

  try {
    const bridge = getDesktopBridge();
    if (!bridge) {
      return;
    }

    const result = await bridge.pingBackend();
    if (result?.ok) {
      updateText(metaElement, `Desktop ready | API ${BACKEND_BASE_URL}`);
    }
  } catch (error) {
    updateText(metaElement, `Desktop ready | API ${BACKEND_BASE_URL}`);
    console.warn('Backend connection check failed:', error?.message || error);
  }
}
