document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('#customer-preferences-data');
  if (!root) return;
  const initial = JSON.parse(root.textContent);
  let addresses = initial.addresses;
  const dialog = document.querySelector('#customer-address-dialog');
  const form = document.querySelector('#customer-address-form');
  const status = document.querySelector('#customer-address-feedback');

  function maskPhone(value) {
    const digits = String(value || '').replace(/\D/g, '').slice(0, 11);
    if (digits.length <= 2) return digits ? '(' + digits : '';
    const local = digits.slice(2);
    if (local.length <= 4) return '(' + digits.slice(0, 2) + ') ' + local;
    const split = local.length > 8 ? 5 : 4;
    return '(' + digits.slice(0, 2) + ') ' + local.slice(0, split) + '-' + local.slice(split);
  }

  function maskCpf(value) {
    const digits = String(value || '').replace(/\D/g, '').slice(0, 11);
    return digits.replace(/^(\d{3})(\d)/, '$1.$2').replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
      .replace(/\.(\d{3})(\d)/, '.$1-$2');
  }

  document.querySelectorAll('[data-br-phone]').forEach((input) => {
    const update = () => { input.value = maskPhone(input.value); };
    input.addEventListener('input', update);
    update();
  });
  document.querySelectorAll('[data-br-cpf]').forEach((input) => {
    const update = () => { input.value = maskCpf(input.value); };
    input.addEventListener('input', update);
    update();
  });
  const profileForm = document.querySelector('#customer-profile-form');
  profileForm?.addEventListener('submit', (event) => {
    const input = profileForm.elements.phone;
    const digits = input.value.replace(/\D/g, '');
    if (digits && digits.length !== 10 && digits.length !== 11) {
      event.preventDefault();
      input.setCustomValidity('Informe um telefone com DDD.');
      input.reportValidity();
    }
  });
  profileForm?.elements.phone.addEventListener('input', () => profileForm.elements.phone.setCustomValidity(''));
  async function action(type, payload = {}) {
    const response = await fetch('/api/customer.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: type, csrf: initial.csrf, ...payload }),
    });
    const data = await response.json();
    if (!response.ok || !data.ok) throw Error(data.error || 'Não foi possível concluir.');
    return data;
  }
  document.querySelector('#customer-new-address')?.addEventListener('click', () => {
    form.reset();
    form.elements.id.value = '';
    status.textContent = '';
    document.querySelector('#customer-address-title').textContent = 'Cadastrar endereço';
    dialog.showModal();
    dialog.querySelector('.customer-address-dialog-body').scrollTop = 0;
  });
  document.querySelectorAll('[data-address-edit]').forEach((button) =>
    button.addEventListener('click', () => {
      const selected = addresses.find((item) => String(item.id) === button.dataset.addressEdit);
      if (!selected) return;
      form.reset();
      for (const key of [
        'id',
        'label',
        'recipient',
        'phone',
        'address',
        'neighborhood',
        'reference_point',
        'number',
        'complement',
      ]) {
        form.elements[key].value = selected[key] || '';
      }
      form.elements.phone.value = maskPhone(form.elements.phone.value);
      form.elements.is_default.checked = Number(selected.is_default) === 1;
      status.textContent = '';
      document.querySelector('#customer-address-title').textContent = 'Editar endereço';
      dialog.showModal();
    }),
  );
  document.querySelector('#customer-address-close')?.addEventListener('click', () => dialog.close());
  document
    .querySelector('#customer-address-cancel')
    ?.addEventListener('click', () => dialog.close());
  form?.elements.phone.addEventListener('input', () => form.elements.phone.setCustomValidity(''));
  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submit = form.querySelector('button[type=submit]');
    const digits = form.elements.phone.value.replace(/\D/g, '');
    if (digits.length !== 10 && digits.length !== 11) {
      form.elements.phone.setCustomValidity('Informe telefone com DDD.');
      form.elements.phone.reportValidity();
      return;
    }
    form.elements.phone.setCustomValidity('');
    submit.disabled = true;
    const payload = Object.fromEntries(new FormData(form));
    payload.is_default = form.elements.is_default.checked ? '1' : '0';
    try {
      const data = await action('save_address', payload);
      addresses = data.addresses;
      dialog.close();
      location.reload();
    } catch (error) {
      status.textContent = error.message;
    } finally {
      submit.disabled = false;
    }
  });
  document.querySelectorAll('[data-address-delete]').forEach((button) =>
    button.addEventListener('click', async () => {
      if (!confirm('Excluir este endereço salvo?')) return;
      try {
        await action('delete_address', { id: button.dataset.addressDelete });
        location.reload();
      } catch (error) {
        alert(error.message);
      }
    }),
  );
  document.querySelectorAll('[data-favorite-delete]').forEach((button) =>
    button.addEventListener('click', async () => {
      if (!confirm('Excluir este pedido padrão?')) return;
      try {
        await action('delete_favorite', { id: button.dataset.favoriteDelete });
        location.reload();
      } catch (error) {
        alert(error.message);
      }
    }),
  );
});
