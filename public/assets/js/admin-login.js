document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('#bia-show-password');
  const password = document.querySelector('#password');
  const form = document.querySelector('#bia-admin-login-form');
  const submit = document.querySelector('#bia-login-submit');

  toggle?.addEventListener('click', () => {
    if (!password) return;
    const visible = password.type === 'password';
    password.type = visible ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', String(visible));
    toggle.setAttribute('aria-label', visible ? 'Ocultar senha' : 'Mostrar senha');
    const icon = toggle.querySelector('i');
    if (icon) icon.className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
    password.focus({ preventScroll: true });
  });

  form?.addEventListener('submit', () => {
    if (!form.checkValidity() || !submit) return;
    submit.disabled = true;
    const label = submit.querySelector('span');
    if (label) label.textContent = 'Entrando...';
  });
});
