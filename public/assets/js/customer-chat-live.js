document.addEventListener('DOMContentLoaded', () => {
  const list = document.querySelector('#conversa .customer-chat');
  const form = document.querySelector('#customer-chat-form');
  if (!list || !form) return;

  const id = list.dataset.orderId;
  const feedback = document.querySelector('#customer-chat-feedback');
  const status = document.querySelector('#customer-order-status');
  const stateLabels = {
    pending: 'Novo pedido',
    accepted: 'Novo pedido',
    preparing: 'Em preparação',
    out_for_delivery: 'Saindo para entrega',
    delivered: 'Pedido entregue',
    cancelled: 'Cancelado',
  };
  let lastSignature = '';
  let busy = false;

  async function exchange(method = 'GET', body = null) {
    const response = await fetch('/api/customer-order-chat.php?order=' + encodeURIComponent(id), {
      method,
      cache: 'no-store',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        ...(body ? { 'Content-Type': 'application/json' } : {}),
      },
      ...(body ? { body: JSON.stringify(body) } : {}),
    });
    const data = await response.json();
    if (!response.ok || !data.ok) throw Error(data.error || 'Não foi possível atualizar.');
    status.textContent = stateLabels[data.status] || data.status;
    const messages = data.messages || [];
    const signature = messages.map((message) => message.id).join(',');
    if (signature === lastSignature) return;
    lastSignature = signature;
    const wasAtBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 100;
    const elements = messages.map((message) => {
      const article = document.createElement('article');
      article.className = message.sender === 'customer' ? 'from-customer' : 'from-restaurant';
      const title = document.createElement('strong');
      title.textContent = message.sender === 'customer' ? 'Você' : 'Restaurante da Bia';
      const text = document.createElement('p');
      text.textContent = message.body;
      const time = document.createElement('small');
      time.textContent = message.created_at;
      article.append(title, text, time);
      return article;
    });
    if (!elements.length) {
      const empty = document.createElement('p');
      empty.textContent = 'Ainda não há mensagens neste pedido.';
      elements.push(empty);
    }
    list.replaceChildren(...elements);
    if (wasAtBottom) list.scrollTop = list.scrollHeight;
  }

  async function refresh() {
    if (busy || document.hidden) return;
    busy = true;
    try {
      await exchange();
      if (feedback.textContent === 'Sem conexão. Tentaremos novamente.') feedback.textContent = '';
    } catch (error) {
      feedback.textContent = 'Sem conexão. Tentaremos novamente.';
    } finally {
      busy = false;
    }
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (busy) return;
    busy = true;
    const button = form.querySelector('button[type="submit"]');
    const body = form.elements.body.value.trim();
    if (!body) {
      busy = false;
      return;
    }
    button.disabled = true;
    feedback.textContent = 'Enviando...';
    try {
      await exchange('POST', { csrf: form.elements.csrf.value, body });
      form.reset();
      feedback.textContent = 'Mensagem enviada.';
    } catch (error) {
      feedback.textContent = error.message;
    } finally {
      busy = false;
      button.disabled = false;
    }
  });

  refresh();
  const timer = setInterval(refresh, 5000);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) refresh();
  });
  window.addEventListener('pagehide', () => clearInterval(timer), { once: true });
});
