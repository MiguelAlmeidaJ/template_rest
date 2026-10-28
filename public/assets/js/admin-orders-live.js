document.addEventListener('DOMContentLoaded', () => {
  const list = document.querySelector('#admin-orders-list');
  if (!list) return;
  const filters = new URLSearchParams(location.search);
  const labels = {
    pending: 'Novo pedido',
    accepted: 'Novo pedido',
    preparing: 'Em preparação',
    out_for_delivery: 'Saindo para entrega',
    delivered: 'Entregue',
    cancelled: 'Cancelado',
  };
  let lastIds = new Set(
    [...list.querySelectorAll('.orders-list-number')].map((el) => el.textContent.trim()),
  );
  let busy = false;
  function itemNode(order) {
    const link = document.createElement('a');
    link.className = 'orders-list-item';
    link.href = '/admin/lunch-order.php?id=' + encodeURIComponent(order.id);
    const number = document.createElement('span');
    number.className = 'orders-list-number';
    number.textContent = '#' + order.id;
    const customer = document.createElement('span');
    customer.className = 'orders-list-customer';
    const name = document.createElement('strong');
    name.textContent = order.customer_name;
    const detail = document.createElement('small');
    detail.textContent = (order.neighborhood || '') + ' · ' + order.created_at;
    customer.append(name, detail);
    const count = document.createElement('span');
    count.className = 'orders-list-count';
    count.textContent =
      order.quantity + ' ' + (Number(order.quantity) === 1 ? 'quentinha' : 'quentinhas');
    const value = document.createElement('span');
    value.className = 'orders-list-price';
    value.textContent = (Number(order.total_cents) / 100).toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL',
    });
    const state = document.createElement('span');
    state.className = 'orders-state orders-state-' + order.status;
    state.textContent = labels[order.status] || order.status;
    const arrow = document.createElement('i');
    arrow.className = 'bi bi-chevron-right';
    arrow.setAttribute('aria-hidden', 'true');
    link.append(number, customer, count, value, state, arrow);
    return link;
  }
  async function refresh() {
    if (busy || document.hidden) return;
    busy = true;
    try {
      const url =
        '/admin/order-feed.php?type=orders&status=' +
        encodeURIComponent(filters.get('status') || '');
      const response = await fetch(url, {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { Accept: 'application/json' },
      });
      if (!response.ok) return;
      const data = await response.json();
      if (!data.ok) return;
      const current = new Set(data.orders.map((order) => '#' + order.id));
      const incoming = data.orders.some((order) => !lastIds.has('#' + order.id));
      const nodes = data.orders.map(itemNode);
      list.replaceChildren(
        ...(nodes.length
          ? nodes
          : [
              Object.assign(document.createElement('div'), {
                className: 'orders-list-empty',
                textContent: 'Nenhum pedido encontrado neste filtro.',
              }),
            ]),
      );
      if (incoming) {
        const mark = document.querySelector('[data-admin-alert-count]');
        if (mark) mark.parentElement.classList.add('has-new-activity');
      }
      lastIds = current;
    } catch (error) {
      // Keep the last successfully loaded list when connectivity fails.
    } finally {
      busy = false;
    }
  }
  const timer = setInterval(refresh, 10000);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) refresh();
  });
  window.addEventListener('pagehide', () => clearInterval(timer), { once: true });
});
