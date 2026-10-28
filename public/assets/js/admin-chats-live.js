document.addEventListener('DOMContentLoaded', () => {
  const list = document.querySelector('.admin-alert-list');
  if (!list) return;
  let working = false;
  async function update() {
    if (working || document.hidden) return;
    working = true;
    try {
      const response = await fetch('/admin/chats.php', {
        cache: 'no-store',
        credentials: 'same-origin',
      });
      if (!response.ok) return;
      const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
      const newer = doc.querySelector('.admin-alert-list');
      if (newer && newer.innerHTML !== list.innerHTML) list.innerHTML = newer.innerHTML;
    } catch (e) {
      /* Keep the existing conversations if connection fails. */
    } finally {
      working = false;
    }
  }
  const timer = setInterval(update, 8000);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) update();
  });
  window.addEventListener('pagehide', () => clearInterval(timer), { once: true });
});
