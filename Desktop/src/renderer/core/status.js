import { getElementById, updateText } from './dom.js';

export function setStatus(message, type = null) {
  const statusElement = getElementById('status');
  if (!statusElement) {
    return;
  }

  statusElement.className = '';
  if (type) {
    statusElement.classList.add(type);
  }

  updateText(statusElement, message || '');
}
