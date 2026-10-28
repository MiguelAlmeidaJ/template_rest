document.addEventListener('DOMContentLoaded', () => {
  const chat = document.querySelector('#conversa .customer-chat');
  if (!chat) return;
  let busy = false;
  async function refresh() {
    if (document.hidden || busy) return;
    busy = true;
    try {
      const response = await fetch(location.href, {
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!response.ok) return;
      const page = new DOMParser().parseFromString(await response.text(), 'text/html');
      const source = page.querySelector('#conversa .customer-chat');
      if (!source) return;
      if (chat.innerHTML !== source.innerHTML) chat.innerHTML = source.innerHTML;
    } catch (error) {
      /* Retain existing messages and retry. */
    } finally {
      busy = false;
    }
  }
  const timer = setInterval(refresh, 8000);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) refresh();
  });
  window.addEventListener('pagehide', () => clearInterval(timer), { once: true });
});
