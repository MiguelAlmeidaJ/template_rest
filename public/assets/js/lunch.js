document.addEventListener('DOMContentLoaded', () => {
  const phone = document.querySelector('#customer-phone');
  if (phone) {
    const maskPhone = (value) => {
      const digits = String(value).replace(/\D/g, '').slice(0, 11);
      if (digits.length <= 2) return digits.length ? '(' + digits : '';
      if (digits.length <= 6) return '(' + digits.slice(0, 2) + ') ' + digits.slice(2);
      const local = digits.slice(2);
      const split = digits.length > 10 ? 5 : 4;
      return '(' + digits.slice(0, 2) + ') ' + local.slice(0, split) + '-' + local.slice(split);
    };
    phone.value = maskPhone(phone.value);
    phone.addEventListener('input', () => {
      phone.value = maskPhone(phone.value);
    });
  }

  const noticeDialog = document.querySelector('#lunch-notice-dialog');
  const noticeTitle = document.querySelector('#lunch-notice-title');
  const noticeText = document.querySelector('#lunch-notice-text');
  const noticeInputWrap = document.querySelector('#lunch-notice-input-wrap');
  const noticeInput = document.querySelector('#lunch-notice-input');
  const noticeCancel = document.querySelector('#lunch-notice-cancel');
  const noticeConfirm = document.querySelector('#lunch-notice-confirm');
  const noticeClose = document.querySelector('#lunch-notice-close');

  function openNotice({ title, message, type = 'notice', placeholder = '', defaultValue = '' }) {
    return new Promise((resolve) => {
      noticeTitle.textContent = title;
      noticeText.textContent = message;
      noticeInputWrap.hidden = type !== 'prompt';
      noticeInput.value = defaultValue;
      noticeInput.placeholder = placeholder;
      noticeCancel.hidden = type === 'notice';
      noticeConfirm.textContent = type === 'notice' ? 'Entendi' : 'Confirmar';
      noticeDialog.showModal();

      let completed = false;
      const finish = (value) => {
        if (completed) return;
        completed = true;
        noticeDialog.close();
        resolve(value);
      };
      const ok = () => {
        if (type === 'prompt' && !noticeInput.value.trim()) {
          noticeInput.focus();
          return;
        }
        finish(type === 'prompt' ? noticeInput.value.trim() : true);
      };
      const cancel = () => finish(type === 'notice' ? true : null);
      noticeConfirm.addEventListener('click', ok, { once: true, signal: controller.signal });
      noticeCancel.addEventListener('click', cancel, { once: true, signal: controller.signal });
      noticeClose.addEventListener('click', cancel, { once: true, signal: controller.signal });
      noticeDialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        cancel();
      }, { once: true, signal: controller.signal });
      const controller = new AbortController();
      noticeDialog.addEventListener('close', () => {
        controller.abort();
        if (!completed) {
          completed = true;
          resolve(type === 'notice' ? true : null);
        }
      }, { once: true, signal: controller.signal });
      if (type === 'prompt') noticeInput.focus();
      else noticeConfirm.focus();
    });
  }
  const awaitNotice = (message, title = 'Atenção') => openNotice({ title, message });
  const askText = (title, message, placeholder = '', defaultValue = '') =>
    openNotice({ title, message, type: 'prompt', placeholder, defaultValue });
  const askConfirm = (title, message) => openNotice({ title, message, type: 'confirm' });

  const config = JSON.parse(document.querySelector('#lunch-config').textContent);
  const { items, settings } = config,
    price = settings.price_cents,
    max = settings.max_choices,
    mode = settings.limit_mode,
    limits = settings.category_limits || {};
  const boxes = document.querySelector('#lunch-boxes'),
    form = document.querySelector('#lunch-form'),
    groupsArea = document.querySelector('#lunch-groups'),
    groupList = document.querySelector('#lunch-group-list');
  const feedback = document.querySelector('#lunch-feedback'),
    tabs = document.querySelector('#lunch-tabs'),
    tabsNav = document.querySelector('#lunch-tabs-nav');
  let qty = 1,
    groups = [{ id: 1, count: 1, name: '', items: [] }],
    nextId = 2,
    activeGroupId = 1;
  const money = (n) => (n / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const total = () => groups.reduce((sum, g) => sum + g.count, 0);
  function makeGroup() {
    return { id: nextId++, count: 1, name: '', items: [] };
  }
  function syncCounts() {
    qty = Math.max(1, Math.min(20, qty));
    while (total() > qty) {
      const last = groups[groups.length - 1];
      if (last.count > 1) last.count--;
      else if (groups.length > 1) groups.pop();
      else break;
    }
    if (total() < qty) groups[groups.length - 1].count += qty - total();
    if (!groups.some((g) => g.id === activeGroupId)) activeGroupId = groups[0].id;
  }
  function renderTabs() {
    tabsNav.replaceChildren();
    tabs.hidden = groups.length <= 1;
    groups.forEach((g, index) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'lunch-tab';
      button.id = 'lunch-tab-' + g.id;
      button.setAttribute('role', 'tab');
      button.setAttribute('aria-controls', 'lunch-panel-' + g.id);
      button.setAttribute('aria-selected', String(g.id === activeGroupId));
      button.tabIndex = g.id === activeGroupId ? 0 : -1;
      button.textContent =
        g.count > 1
          ? 'Grupo ' + (index + 1) + ' · ' + g.count + ' iguais'
          : 'Quentinha ' + (index + 1);
      button.addEventListener('click', () => activateTab(g.id));
      button.addEventListener('keydown', (e) => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) return;
        e.preventDefault();
        let i = groups.findIndex((x) => x.id === g.id);
        if (e.key === 'ArrowRight') i = (i + 1) % groups.length;
        if (e.key === 'ArrowLeft') i = (i - 1 + groups.length) % groups.length;
        if (e.key === 'Home') i = 0;
        if (e.key === 'End') i = groups.length - 1;
        activateTab(groups[i].id);
        tabsNav.querySelector('#lunch-tab-' + groups[i].id)?.focus();
      });
      tabsNav.append(button);
    });
  }
  function activateTab(id) {
    activeGroupId = id;
    tabsNav.querySelectorAll('[role=tab]').forEach((btn) => {
      const selected = btn.id === 'lunch-tab-' + id;
      btn.classList.toggle('active', selected);
      btn.setAttribute('aria-selected', String(selected));
      btn.tabIndex = selected ? 0 : -1;
    });
    boxes.querySelectorAll('.lunch-box').forEach((panel) => {
      panel.hidden = panel.dataset.groupId !== String(id);
    });
  }
  function summary() {
    document.querySelector('#lunch-quantity').textContent = qty;
    document.querySelector('#lunch-count-summary').textContent =
      qty + ' ' + (qty === 1 ? 'quentinha' : 'quentinhas');
    document.querySelector('#lunch-total').textContent = money(qty * price);
  }
  function renderGroups() {
    groupList.replaceChildren();
    groupsArea.hidden = qty < 2;
    groups.forEach((g, index) => {
      const row = document.createElement('div');
      row.className = 'lunch-group-row';
      const label = document.createElement('label');
      label.textContent = 'Grupo ' + (index + 1);
      const select = document.createElement('select');
      select.setAttribute('aria-label', 'Quantidade de quentinhas iguais no grupo ' + (index + 1));
      for (let n = 1; n <= qty; n++) {
        const o = document.createElement('option');
        o.value = n;
        o.textContent = n + ' ' + (n === 1 ? 'quentinha' : 'quentinhas');
        if (n === g.count) o.selected = true;
        select.append(o);
      }
      select.addEventListener('change', () => {
        const requested = Number(select.value),
          others = total() - g.count;
        if (requested + others > qty) {
          feedback.textContent = 'A soma dos grupos não pode superar ' + qty + ' quentinhas.';
          renderGroups();
          return;
        }
        g.count = requested;
        const spare = qty - total();
        if (spare > 0) {
          const other = groups.find((x) => x !== g);
          if (other) other.count += spare;
          else g.count += spare;
        }
        render();
      });
      label.append(select);
      row.append(label);
      if (groups.length > 1) {
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'lunch-group-remove';
        remove.textContent = 'Remover grupo';
        remove.addEventListener('click', () => {
          groups = groups.filter((x) => x !== g);
          groups[0].count += g.count;
          render();
        });
        row.append(remove);
      }
      groupList.append(row);
    });
    const add = document.querySelector('#lunch-add-group');
    add.disabled = groups.length >= qty || !groups.some((g) => g.count > 1);
  }
  function renderBox(g, index) {
    const section = document.createElement('fieldset');
    section.className = 'lunch-box';
    section.dataset.groupId = String(g.id);
    section.id = 'lunch-panel-' + g.id;
    section.setAttribute('role', 'tabpanel');
    section.setAttribute('aria-labelledby', 'lunch-tab-' + g.id);
    const legend = document.createElement('legend');
    legend.textContent =
      g.count > 1
        ? 'Grupo ' + (index + 1) + ' · ' + g.count + ' quentinhas iguais'
        : 'Quentinha ' + (index + 1);
    section.append(legend);
    const description = document.createElement('p');
    description.className = 'lunch-group-description';
    description.textContent =
      g.count > 1
        ? 'Escolha os pratos uma vez. A mesma montagem será aplicada a ' + g.count + ' quentinhas.'
        : 'Escolha os pratos desta quentinha.';
    section.append(description);
    const nameLabel = document.createElement('label');
    nameLabel.className = 'lunch-box-name';
    nameLabel.textContent = g.count > 1 ? 'Identificação do grupo' : 'Nome na quentinha';
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'lunch-box-recipient';
    input.placeholder = g.count > 1 ? 'Ex.: Equipe da loja' : 'Ex.: Maria';
    input.maxLength = 80;
    input.minLength = 2;
    input.required = true;
    input.value = g.name;
    input.addEventListener('input', () => (g.name = input.value));
    nameLabel.append(input);
    section.append(nameLabel);
    const categories = {};
    items.forEach((x) => (categories[x.category] ??= []).push(x));
    Object.entries(categories).forEach(([category, list]) => {
      const limit = mode === 'category' ? Number(limits[category] ?? 0) : max;
      const heading = document.createElement('h4');
      heading.textContent = category;
      if (mode === 'category') {
        const note = document.createElement('small');
        note.className = 'lunch-limit-note';
        note.textContent = ' · até ' + limit;
        heading.append(note);
      }
      section.append(heading);
      if (mode === 'category' && limit === 0) return;
      const grid = document.createElement('div');
      grid.className = 'lunch-options';
      list.forEach((item) => {
        const label = document.createElement('label'),
          checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.value = item.id;
        checkbox.dataset.category = category;
        checkbox.checked = g.items.includes(Number(item.id));
        checkbox.addEventListener('change', () => {
          if (checkbox.checked) {
            const chosen = [...section.querySelectorAll('input[type=checkbox]:checked')];
            const exceeds =
              mode === 'category'
                ? chosen.filter((x) => x.dataset.category === category).length > limit
                : chosen.length > max;
            if (exceeds) {
              checkbox.checked = false;
              awaitNotice(
                mode === 'category'
                  ? 'Nesta categoria você pode escolher até ' + limit + ' prato(s).'
                  : 'Você já selecionou o máximo de ' + max + ' pratos por quentinha.',
                'Limite de escolhas',
              );
              return;
            }
          }
          g.items = [...section.querySelectorAll('input[type=checkbox]:checked')].map((x) =>
            Number(x.value),
          );
          feedback.textContent = '';
        });
        label.append(checkbox, document.createTextNode(' ' + item.name));
        grid.append(label);
      });
      section.append(grid);
    });
    return section;
  }
  function render() {
    syncCounts();
    renderGroups();
    boxes.replaceChildren();
    groups.forEach((g, i) => boxes.append(renderBox(g, i)));
    renderTabs();
    activateTab(activeGroupId);
    summary();
  }
  document.querySelector('#lunch-plus').addEventListener('click', () => {
    if (qty < 20) {
      qty++;
      groups[groups.length - 1].count++;
      render();
    }
  });
  document.querySelector('#lunch-minus').addEventListener('click', () => {
    if (qty > 1) {
      qty--;
      syncCounts();
      render();
    }
  });
  document.querySelector('#lunch-add-group').addEventListener('click', () => {
    const donor = groups.find((g) => g.count > 1);
    if (!donor) return;
    donor.count--;
    const added = makeGroup();
    groups.push(added);
    activeGroupId = added.id;
    render();
  });
  const account = config.account || {};
  let authenticated = !!account.authenticated;
  let addresses = account.addresses || [];
  let favorites = account.favorites || [];
  const API = '/api/customer.php';
  async function customerAction(action, fields = {}) {
    const response = await fetch(API, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action, csrf: account.csrf, ...fields }),
    });
    const data = await response.json();
    if (!response.ok || !data.ok) throw Error(data.error || 'Não foi possível concluir.');
    authenticated = true;
    addresses = data.addresses || [];
    favorites = data.favorites || [];
    refreshPreferences();
    return data;
  }
  function refreshPreferences() {
    document.querySelector('#lunch-address-toolbar').hidden = !authenticated;
    document.querySelector('#lunch-favorites-toolbar').hidden = !authenticated;
    const addressSelect = document.querySelector('#lunch-address-select');
    const previousAddress = addressSelect.value;
    addressSelect.replaceChildren(new Option('Preencher manualmente', ''));
    addresses.forEach((address) => {
      addressSelect.add(
        new Option(
          address.label + (Number(address.is_default) ? ' · principal' : ''),
          String(address.id),
        ),
      );
    });
    if (addresses.some((item) => String(item.id) === previousAddress))
      addressSelect.value = previousAddress;
    const favoriteSelect = document.querySelector('#lunch-favorite-select');
    favoriteSelect.replaceChildren(new Option('Selecione um pedido padrão...', ''));
    favorites.forEach((item) => favoriteSelect.add(new Option(item.name, String(item.id))));
  }
  function deliveryFields() {
    return {
      recipient: form.elements.customer_name.value,
      phone: form.elements.customer_phone.value,
      address: form.elements.address.value,
      neighborhood: form.elements.neighborhood.value,
      reference_point: form.elements.reference_point.value,
    };
  }
  function applyAddress(address) {
    const map = deliveryFields();
    Object.keys(map).forEach((key) => {
      const inputName =
        key === 'recipient' ? 'customer_name' : key === 'phone' ? 'customer_phone' : key;
      form.elements[inputName].value = address[key] || '';
    });
    if (phone) phone.dispatchEvent(new Event('input', { bubbles: true }));
  }
  document.querySelector('#lunch-address-select')?.addEventListener('change', (event) => {
    const selected = addresses.find((item) => String(item.id) === event.target.value);
    if (selected) applyAddress(selected);
  });
  document.querySelector('#lunch-save-address')?.addEventListener('click', async () => {
    const label = await askText('Salvar endereço', 'Como deseja identificar este endereço?', 'Ex.: Casa, Trabalho', 'Casa');
    if (label === null) return;
    const defaultChoice = await askConfirm('Endereço principal', 'Deseja usar este endereço como principal em novos pedidos?');
    if (defaultChoice === null) return;
    try {
      await customerAction('save_address', {
        ...deliveryFields(),
        label,
        is_default: defaultChoice ? '1' : '0',
      });
      feedback.textContent = 'Endereço salvo na sua conta.';
    } catch (error) {
      feedback.textContent = error.message;
    }
  });
  document.querySelector('#lunch-save-favorite')?.addEventListener('click', async () => {
    const name = await askText('Salvar pedido padrão', 'Dê um nome para reconhecer esta montagem na próxima compra.', 'Ex.: Minha quentinha de sempre');
    if (name === null) return;
    const boxes = groups.flatMap((g) =>
      Array.from({ length: g.count }, (_, index) => ({
        name: g.count > 1 ? g.name.trim() + ' ' + (index + 1) : g.name.trim(),
        items: [...g.items],
      })),
    );
    try {
      await customerAction('save_favorite', { name, boxes });
      feedback.textContent = 'Pedido padrão salvo na sua conta.';
    } catch (error) {
      feedback.textContent = error.message;
    }
  });
  document.querySelector('#lunch-favorite-select')?.addEventListener('change', (event) => {
    const found = favorites.find((item) => String(item.id) === event.target.value);
    if (!found) return;
    const stored =
      typeof found.boxes_json === 'string' ? JSON.parse(found.boxes_json) : found.boxes_json;
    const allowed = new Set(items.map((item) => Number(item.id)));
    const valid = stored
      .filter((box) => Array.isArray(box.items))
      .map((box) => ({
        id: nextId++,
        count: 1,
        name: box.name || 'Minha quentinha',
        items: box.items.map(Number).filter((id) => allowed.has(id)),
      }))
      .filter((box) => box.items.length);
    if (!valid.length) {
      feedback.textContent = 'Os pratos deste pedido padrão não estão disponíveis hoje.';
      return;
    }
    groups = valid;
    qty = valid.length;
    activeGroupId = groups[0].id;
    render();
    feedback.textContent =
      valid.length < stored.length
        ? 'Alguns pratos não estão disponíveis e foram ignorados. Confira a montagem.'
        : 'Montagem carregada. Confira os pratos antes de confirmar.';
  });
  const dialog = document.querySelector('#lunch-account-dialog');
  let accountMode = 'register';
  document
    .querySelector('#lunch-open-account')
    ?.addEventListener('click', () => dialog.showModal());
  document.querySelector('#lunch-close-account')?.addEventListener('click', () => dialog.close());
  document.querySelectorAll('[data-account-mode]').forEach((button) =>
    button.addEventListener('click', () => {
      accountMode = button.dataset.accountMode;
      document
        .querySelectorAll('[data-account-mode]')
        .forEach((tab) => tab.classList.toggle('active', tab === button));
      document.querySelector('#lunch-account-submit').textContent =
        accountMode === 'register' ? 'Criar minha conta' : 'Entrar na minha conta';
    }),
  );
  document.querySelector('#lunch-account-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submit = document.querySelector('#lunch-account-submit');
    submit.disabled = true;
    try {
      await customerAction(accountMode, {
        email: document.querySelector('#lunch-account-email').value,
        password: document.querySelector('#lunch-account-password').value,
      });
      document.querySelector('#lunch-account-banner').innerHTML =
        '<i class="bi bi-person-check"></i> Você está conectado. <a href="/cliente/">Acompanhar meus pedidos</a>';
      dialog.close();
      feedback.textContent = 'Conta conectada. Sua montagem foi preservada.';
    } catch (error) {
      document.querySelector('#lunch-account-feedback').textContent = error.message;
    } finally {
      submit.disabled = false;
    }
  });
  document.querySelector('#lunch-find-address')?.addEventListener('click', () => {
    const status = document.querySelector('#lunch-location-feedback');
    if (!navigator.geolocation) {
      status.textContent = 'Localização não disponível neste navegador.';
      return;
    }
    status.textContent = 'Solicitando permissão de localização...';
    navigator.geolocation.getCurrentPosition(
      async ({ coords }) => {
        try {
          // OpenStreetMap Nominatim: used only after the customer explicitly requests a lookup.
          const response = await fetch(
            'https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=18&addressdetails=1&lat=' +
              encodeURIComponent(coords.latitude) +
              '&lon=' +
              encodeURIComponent(coords.longitude),
          );
          if (!response.ok) throw Error('Endereço não encontrado.');
          const found = await response.json();
          const a = found.address || {};
          form.elements.address.value = [a.road || a.pedestrian || a.street, a.house_number]
            .filter(Boolean)
            .join(', ');
          form.elements.neighborhood.value = a.suburb || a.neighbourhood || a.city_district || '';
          status.textContent =
            'Endereço aproximado. Confira número, complemento e referência antes de pedir.';
        } catch (error) {
          status.textContent = 'Não foi possível identificar a rua. Preencha manualmente.';
        }
      },
      () => {
        status.textContent = 'Permissão negada ou localização indisponível. Digite o endereço.';
      },
      { enableHighAccuracy: false, timeout: 12000 },
    );
  });
  refreshPreferences();

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submit = document.querySelector('#lunch-submit');
    const payload = [];
    for (const g of groups) {
      if (g.name.trim().length < 2 || !g.items.length) {
        activeGroupId = g.id;
        activateTab(g.id);
        feedback.textContent =
          'Preencha a identificação e selecione ao menos um prato em cada grupo.';
        return;
      }
      for (let i = 0; i < g.count; i++)
        payload.push({
          name: g.count > 1 ? g.name.trim() + ' ' + (i + 1) : g.name.trim(),
          items: [...g.items],
        });
    }
    if (payload.length !== qty) {
      feedback.textContent = 'A quantidade dos grupos não corresponde ao total de quentinhas.';
      return;
    }
    const data = Object.fromEntries(new FormData(form));
    data.boxes = payload;
    submit.disabled = true;
    feedback.textContent = 'Registrando seu pedido...';
    try {
      const response = await fetch('/api/lunch/orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      const result = await response.json();
      if (!response.ok) throw Error(result.error || 'Não foi possível registrar o pedido');
      feedback.textContent =
        'Pedido #' +
        result.order_id +
        ' recebido! Total: ' +
        money(result.total_cents) +
        '. Aguarde a confirmação do restaurante.';
      form.reset();
      qty = 1;
      groups = [{ id: nextId++, count: 1, name: '', items: [] }];
      activeGroupId = groups[0].id;
      render();
    } catch (err) {
      feedback.textContent = err.message;
    } finally {
      submit.disabled = false;
    }
  });
  render();
});
