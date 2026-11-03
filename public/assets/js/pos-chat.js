document.addEventListener('DOMContentLoaded', () => {
  const dialog = document.querySelector('#pos-order-dialog');
  const panel = document.querySelector('#pos-chat-panel');
  const messages = document.querySelector('#pos-chat-messages');
  const form = document.querySelector('#pos-chat-form');
  const feedback = document.querySelector('#pos-chat-feedback');
  let orderId = 0,
    busy = false,
    fingerprint = '';

  async function request(url, opts = {}) {
    const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...opts });
    const data = await response.json();
    if (!response.ok || !data.ok) throw Error(data.error || 'Não foi possível atualizar.');
    return data;
  }
  function draw(rows) {
    const ids = rows.map((row) => row.id).join(',');
    if (ids === fingerprint) return;
    fingerprint = ids;
    const nearBottom = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 90;
    const elements = rows.map((row) => {
      const el = document.createElement('article');
      el.className = 'pos-chat-bubble ' + (row.sender === 'restaurant' ? 'outgoing' : 'incoming');
      const title = document.createElement('strong');
      title.textContent = row.sender === 'restaurant' ? 'Restaurante' : 'Cliente';
      const body = document.createElement('p');
      body.textContent = row.body;
      const time = document.createElement('small');
      time.textContent = row.created_at;
      el.append(title, body, time);
      return el;
    });
    if (!elements.length) {
      const empty = document.createElement('p');
      empty.textContent = 'Nenhuma mensagem neste pedido.';
      elements.push(empty);
    }
    messages.replaceChildren(...elements);
    if (nearBottom) messages.scrollTop = messages.scrollHeight;
  }
  async function refresh() {
    if (!dialog.open || !orderId || busy || document.hidden) return;
    busy = true;
    const id = orderId;
    try {
      const data = await request('/admin/pos-api.php?type=chat&order_id=' + encodeURIComponent(id));
      if (orderId === id && dialog.open) draw(data.messages || []);
    } catch (error) {
      if (orderId === id) feedback.textContent = error.message;
    } finally {
      busy = false;
    }
  }
  async function opened() {
    const match = document.querySelector('#pos-order-title').textContent.match(/\d+/);
    if (!match) return;
    const id = Number(match[0]);
    orderId = id;
    fingerprint = '';
    messages.replaceChildren();
    panel.hidden = true;
    feedback.textContent = '';
    const unavailable = document.querySelector('#pos-chat-unavailable');
    unavailable.hidden = true;
    try {
      const data = await request('/admin/pos-api.php');
      if (!dialog.open || orderId !== id) return;
      const order = data.orders.find((item) => Number(item.id) === id);
      if (!order) return;
      const phone = String(order.customer_phone || '').replace(/\D/g, '');
      const valid = [10, 11].includes(phone.length)
        ? '55' + phone
        : [12, 13].includes(phone.length) && phone.startsWith('55')
          ? phone
          : null;
      const whatsapp = document.querySelector('#pos-order-whatsapp');
      whatsapp.hidden = !valid;
      if (valid) whatsapp.href = 'https://wa.me/' + valid;
      panel.hidden = !order.customer_id;
      unavailable.hidden = !!order.customer_id;
      if (order.customer_id) refresh();
    } catch (error) {
      feedback.textContent = error.message;
      panel.hidden = false;
    }
  }
  new MutationObserver(() => {
    if (dialog.open) opened();
    else orderId = 0;
  }).observe(dialog, { attributes: true, attributeFilter: ['open'] });
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!orderId || busy) return;
    const input = document.querySelector('#pos-chat-body');
    const body = input.value.trim();
    if (!body) return;
    const button = document.querySelector('#pos-chat-submit');
    button.disabled = true;
    const id = orderId;
    const payload = new FormData();
    payload.set('csrf', JSON.parse(document.querySelector('#pos-initial').textContent).csrf);
    payload.set('action', 'chat_send');
    payload.set('order_id', String(id));
    payload.set('body', body);
    try {
      await request('/admin/pos-api.php', { method: 'POST', body: payload });
      if (orderId === id) input.value = '';
      feedback.textContent = 'Mensagem enviada.';
      refresh();
    } catch (error) {
      feedback.textContent = error.message;
    } finally {
      button.disabled = false;
    }
  });
  setInterval(refresh, 5000);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) refresh();
  });
});
