export function getElementById(id) {
  return document.getElementById(id);
}

export function updateText(element, text) {
  if (!element) {
    return;
  }

  element.textContent = text;
}
