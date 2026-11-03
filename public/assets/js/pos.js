document.addEventListener('DOMContentLoaded', () => {
  const initial = JSON.parse(document.querySelector('#pos-initial').textContent);
  const csrf = initial.csrf;
  let orders = initial.orders || [];
  let selectedId = null;
  let refreshing = false;
  let submitting = false;

  const $ = (selector) => document.querySelector(selector);
  const money = (cents) => (Number(cents || 0) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const paymentLabels = { pix: 'Pix', card: 'Cartão', cash: 'Dinheiro' };
  const statuses = { pending: 'Novo', accepted: 'Novo', preparing: 'Preparação', out_for_delivery: 'Entrega' };
  const steps = { pending: ['preparing', 'Iniciar preparo'], accepted: ['preparing', 'Iniciar preparo'], preparing: ['out_for_delivery', 'Saiu para entrega'], out_for_delivery: ['delivered', 'Marcar entregue'] };

  function node(tag, className, content) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (content !== undefined) element.textContent = String(content);
    return element;
  }

  function toast(message, error = false) {
    const target = $('#pos-toast');
    target.textContent = message;
    target.classList.toggle('error', error);
    target.hidden = false;
    clearTimeout(toast.timer);
    toast.timer = setTimeout(() => { target.hidden = true; }, 4500);
  }

  async function action(fields) {
    const form = new FormData();
    Object.entries(fields).forEach(([key, value]) => form.set(key, String(value)));
    form.set('csrf', csrf);
    const response = await fetch('/admin/pos-api.php', { method: 'POST', body: form, credentials: 'same-origin', headers: { Accept: 'application/json' } });
    const payload = await response.json();
    if (!response.ok || !payload.ok) throw Error(payload.error || 'Falha ao salvar.');
    return payload;
  }

  function card(order) {
    const article = node('article', 'pos-order-card');
    const top = node('div', 'pos-order-top');
    top.append(node('strong', '', '#' + order.id), node('span', '', new Date(String(order.created_at).replace(' ', 'T')).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })));
    const customer = node('h3', '', order.customer_name);
    const sub = node('p', 'pos-order-neighborhood', (order.neighborhood || 'Retirada a confirmar') + ' · ' + order.quantity + (Number(order.quantity) === 1 ? ' quentinha' : ' quentinhas'));
    const food = node('div', 'pos-order-food');
    for (const box of (order.boxes || [])) {
      const row = node('p');
      const strong = node('strong', '', box.name || 'Quentinha');
      row.append(strong, document.createTextNode(' · ' + (box.items || []).join(', ')));
      food.append(row);
    }
    const bottom = node('div', 'pos-order-bottom');
    bottom.append(node('strong', '', money(order.total_cents)));
    const label = node('span', 'pos-payment-tag ' + (order.payment_status === 'paid' ? 'paid' : ''), order.payment_status === 'paid' ? 'Pago' : 'Pagamento pendente');
    bottom.append(label);
    const buttons = node('div', 'pos-card-buttons');
    const view = node('button', 'pos-secondary', 'Ver detalhes');
    view.type = 'button';
    view.addEventListener('click', () => openDetails(Number(order.id)));
    const next = steps[order.status];
    const advance = node('button', 'pos-card-advance', next ? next[1] : 'Concluído');
    advance.type = 'button';
    advance.disabled = !next;
    if (next) advance.addEventListener('click', () => move(Number(order.id), next[0], advance));
    buttons.append(view, advance);
    article.append(top, customer, sub, food, bottom, buttons);
    return article;
  }

  function renderBoard() {
    const lanes = { new: [], preparing: [], delivering: [] };
    orders.forEach((order) => {
      const lane = ['pending', 'accepted'].includes(order.status) ? 'new'
        : order.status === 'preparing' ? 'preparing'
        : order.status === 'out_for_delivery' ? 'delivering' : null;
      if (lane) lanes[lane].push(order);
    });
    Object.entries(lanes).forEach(([name, items]) => {
      const holder = $('#pos-lane-' + name);
      holder.replaceChildren(...(items.length ? items.map(card) : [node('p', 'pos-empty', 'Nenhum pedido nesta etapa.')]));
      $('#pos-lane-count-' + name).textContent = String(items.length);
      $('#pos-count-' + name).textContent = String(items.length);
    });
  }

  function renderFinance(data) {
    $('#pos-finance-total').textContent = money(data.grand_total_cents);
    $('#pos-sales-total').textContent = money(data.grand_total_cents);
    $('#pos-sales-local').textContent = money(data.local.total_cents);
    $('#pos-sales-online').textContent = money(data.online.received_cents);
    $('#pos-sales-count').textContent = Number(data.local.sales_count) + ' venda(s)';
    const history = $('#pos-sale-history');
    const sales = data.sales || [];
    history.replaceChildren(...(sales.length ? sales.map((sale) => {
      const row = node('div', 'pos-sale-row');
      const description = node('span', '', '#' + sale.id + ' · ' + sale.description + ' (' + sale.quantity + 'x)');
      row.append(description, node('span', '', paymentLabels[sale.payment_method] || sale.payment_method), node('strong', '', money(sale.total_cents)));
      return row;
    }) : [node('p', 'pos-empty', 'Nenhuma venda presencial registrada hoje.')]));
  }

  async function refresh(force = false) {
    if (refreshing || (!force && document.hidden)) return;
    refreshing = true;
    try {
      const response = await fetch('/admin/pos-api.php', { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
      if (!response.ok) throw Error('Sem conexão com o servidor');
      const data = await response.json();
      if (!data.ok) throw Error('Falha ao atualizar');
      const priorIds = new Set(orders.map((order) => order.id));
      const incoming = data.orders.some((order) => !priorIds.has(order.id));
      orders = data.orders;
      renderBoard();
      renderFinance(data.finance);
      $('#pos-last-refresh').textContent = 'Atualizado às ' + new Date().toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'});
      $('#pos-connection').classList.remove('offline');
      $('#pos-connection').lastChild.textContent = ' Conectado';
      if (incoming && !document.hidden) toast('Novo pedido recebido!');
    } catch (error) {
      $('#pos-connection').classList.add('offline');
      $('#pos-connection').lastChild.textContent = ' Sem conexão';
    } finally { refreshing = false; }
  }

  async function move(id, next, button) {
    if (submitting) return;
    submitting = true;
    button.disabled = true;
    try {
      const result = await action({ action: 'next', id, next });
      toast(result.message);
      $('#pos-order-dialog').close();
      await refresh(true);
    } catch (error) { toast(error.message, true); }
    finally { submitting = false; button.disabled = false; }
  }

  async function markPaid(id, button) {
    if (submitting) return;
    submitting = true;
    button.disabled = true;
    try {
      const result = await action({ action: 'paid', id });
      toast(result.message);
      $('#pos-order-dialog').close();
      await refresh(true);
    } catch (error) { toast(error.message, true); }
    finally { submitting = false; button.disabled = false; }
  }

  function openDetails(id) {
    const order = orders.find((value) => Number(value.id) === id);
    if (!order) { toast('Pedido não encontrado.', true); return; }
    selectedId = id;
    $('#pos-order-title').textContent = 'Pedido #' + id;
    $('#pos-order-subtitle').textContent = order.customer_name + ' · ' + statuses[order.status];
    const target = $('#pos-order-content');
    const blocks = [];
    const delivery = node('div', 'pos-detail-block');
    delivery.append(node('h3', '', 'Cliente e entrega'), node('p', '', order.customer_name), node('p', '', order.customer_phone),
      node('p', '', [order.address, order.neighborhood].filter(Boolean).join(' · ')),
      node('p', '', 'Referência: ' + (order.reference_point || 'Não informada')));
    blocks.push(delivery);
    const food = node('div', 'pos-detail-block');
    food.append(node('h3', '', 'Quentinhas (' + order.quantity + ')'));
    for (const [i, box] of (order.boxes || []).entries()) {
      const row = node('div', 'pos-detail-box');
      row.append(node('strong', '', (i + 1) + '. ' + (box.name || 'Quentinha')),
        node('p', '', (box.items || []).join(' · ')));
      food.append(row);
    }
    blocks.push(food);
    const pay = node('div', 'pos-detail-block');
    pay.append(node('h3', '', 'Pagamento'), node('p', '', (paymentLabels[order.payment_method] || order.payment_method) + ' · ' + money(order.total_cents)));
    if (order.payment_status === 'pending') {
      const confirm = node('button', 'pos-secondary', 'Confirmar pagamento recebido');
      confirm.type = 'button';
      confirm.addEventListener('click', () => {
        if (window.confirm('Confirma que o pagamento deste pedido foi recebido?')) markPaid(id, confirm);
      });
      pay.append(confirm);
    } else pay.append(node('p', 'pos-paid', 'Pagamento confirmado'));
    blocks.push(pay);
    target.replaceChildren(...blocks);
    const next = steps[order.status];
    const button = $('#pos-order-next');
    button.hidden = !next;
    if (next) {
      button.textContent = next[1] + ' →';
      button.onclick = () => move(id, next[0], button);
    }
    $('#pos-order-dialog').showModal();
  }

  document.querySelectorAll('[data-close-dialog]').forEach((button) => {
    button.addEventListener('click', () => button.closest('dialog').close());
  });
  $('#pos-new-sale').addEventListener('click', () => {
    $('#pos-sale-form').reset();
    $('#pos-quantity').value = '1';
    $('#pos-sale-error').textContent = '';
    $('#pos-sale-total').textContent = money(0);
    $('#pos-sale-dialog').showModal();
    $('#pos-item').focus();
  });

  function parseCents(value) {
    const input = String(value).trim().replace(/\s/g, '').replace(/^R\$/i, '');
    // Brazilian format (20,50) or plain decimal with dot (20.50).
    if (!/^\d+(?:[,.]\d{1,2})?$/.test(input)) return null;
    const parts = input.replace(',', '.').split('.');
    const cents = Number(parts[0]) * 100 + Number((parts[1] || '').padEnd(2, '0'));
    return Number.isSafeInteger(cents) && cents > 0 ? cents : null;
  }
  function updateTotal() {
    const cents = parseCents($('#pos-price').value);
    const qty = Number($('#pos-quantity').value);
    $('#pos-sale-total').textContent = money(cents && Number.isInteger(qty) && qty > 0 ? cents * qty : 0);
  }
  $('#pos-price').addEventListener('input', updateTotal);
  $('#pos-quantity').addEventListener('input', updateTotal);
  $('#pos-sale-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    if (submitting) return;
    const form = event.currentTarget;
    const cents = parseCents($('#pos-price').value);
    if (!cents) { $('#pos-sale-error').textContent = 'Informe um preço válido, como 20,00.'; $('#pos-price').focus(); return; }
    submitting = true;
    const button = $('#pos-sale-submit');
    button.disabled = true;
    $('#pos-sale-error').textContent = '';
    try {
      const data = Object.fromEntries(new FormData(form));
      data.unit_price_cents = cents;
      const result = await action(data);
      $('#pos-sale-dialog').close();
      toast(result.message);
      await refresh(true);
    } catch (error) { $('#pos-sale-error').textContent = error.message; }
    finally { button.disabled = false; submitting = false; }
  });

  renderBoard();
  renderFinance(initial.finance);
  refresh(true);
  const timer = setInterval(refresh, 6000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(true); });
  window.addEventListener('pagehide', () => clearInterval(timer), { once: true });
});
