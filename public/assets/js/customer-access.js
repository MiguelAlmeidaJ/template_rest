document.addEventListener('DOMContentLoaded', () => {
  const loginTab = document.querySelector('#customer-login-tab');
  const registerTab = document.querySelector('#customer-register-tab');
  if (!loginTab || !registerTab) return;

  const tabs = [loginTab, registerTab];
  const panels = [
    document.querySelector('#customer-login-panel'),
    document.querySelector('#customer-register-panel'),
  ];

  function activate(index, moveFocus = false) {
    tabs.forEach((tab, i) => {
      const active = i === index;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', String(active));
      tab.tabIndex = active ? 0 : -1;
      panels[i].hidden = !active;
    });

    if (moveFocus) tabs[index].focus();
  }

  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activate(index));
    tab.addEventListener('keydown', (event) => {
      if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      const next =
        event.key === 'Home'
          ? 0
          : event.key === 'End'
            ? tabs.length - 1
            : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
      activate(next, true);
    });
  });

  // Preserve the selected tab after a failed registration.
  activate(registerTab.getAttribute('aria-selected') === 'true' ? 1 : 0);

  document.querySelectorAll('[data-customer-password]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.customerPassword);
      if (!input) return;
      const visible = input.type === 'password';
      input.type = visible ? 'text' : 'password';
      button.setAttribute('aria-label', visible ? 'Ocultar senha' : 'Mostrar senha');
      button.setAttribute('aria-pressed', String(visible));
      const icon = button.querySelector('i');
      if (icon) icon.className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
      input.focus({ preventScroll: true });
    });
  });

  document.querySelectorAll('.customer-login-form').forEach((form) => {
    form.addEventListener('submit', () => {
      if (!form.checkValidity()) return;
      const button = form.querySelector('button[type="submit"]');
      if (!button) return;
      button.disabled = true;
      const label = button.querySelector('span');
      if (label) label.textContent = 'Aguarde...';
    });
  });
});
