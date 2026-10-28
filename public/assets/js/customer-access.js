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
      const next = event.key === 'Home' ? 0
        : event.key === 'End' ? tabs.length - 1
        : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
      activate(next, true);
    });
  });

  activate(0);
});
