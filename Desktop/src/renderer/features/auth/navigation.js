import { getElementById } from '../../core/dom.js';

export function bindAuthNavigation() {
  const authSection = getElementById('authSection');
  const registerSection = getElementById('registerSection');
  const root = document.body;

  function showAuthSection(sectionToShow) {
    const showRegister = sectionToShow === 'register';
    authSection?.classList.toggle('hidden', showRegister);
    registerSection?.classList.toggle('hidden', !showRegister);
  }

  root?.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) {
      return;
    }

    const registerTrigger = target.closest('[data-auth-target="register"], #registerLink');
    const loginTrigger = target.closest('[data-auth-target="login"], #loginLink');

    if (registerTrigger) {
      event.preventDefault();
      showAuthSection('register');
      return;
    }

    if (loginTrigger) {
      event.preventDefault();
      showAuthSection('login');
    }
  });

  return showAuthSection;
}
