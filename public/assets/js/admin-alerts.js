document.addEventListener('DOMContentLoaded', () => {
  const chat = document.querySelector('[data-admin-chat-count]'),
    alerts = document.querySelector('[data-admin-alert-count]');
  if (!chat || !alerts) return;
  let stopped = false;
  async function update() {
    if (document.hidden || stopped) return;
    try {
      const r = await fetch('/admin/alerts-status.php', {
        cache: 'no-store',
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      if (!r.ok) return;
      const data = await r.json();
      if (!data.ok) return;
      const c = data.counts || {};
      const show = (el, n) => {
        n = Math.max(0, Number(n) || 0);
        el.hidden = n < 1;
        el.textContent = n > 99 ? '99+' : String(n);
        el.parentElement.setAttribute(
          'aria-label',
          n
            ? n + ' notificações ' + (el === chat ? 'de mensagens' : 'de pedidos')
            : el === chat
              ? 'Mensagens dos clientes'
              : 'Notificações de pedidos',
        );
      };
      show(chat, c.messages);
      show(alerts, c.orders || 0);
    } catch (e) {
      /* silent network failure */
    }
  }
  update();
  const timer = setInterval(update, 7000);
  window.addEventListener('pagehide', () => {
    stopped = true;
    clearInterval(timer);
  });
});
