document.addEventListener('DOMContentLoaded', () => {
  const forms = [...document.querySelectorAll('.js-image-upload')];
  function preview(form, url) {
    const target = form.querySelector('[data-preview]');
    const img = document.createElement('img');
    img.src = url;
    img.alt = 'Imagem atualizada';
    target.replaceChildren(img);
  }
  async function send(form) {
    const button = form.querySelector('button[type=submit]'),
      message = form.querySelector('.cms-upload-feedback');
    button.disabled = true;
    const text = button.textContent;
    button.textContent = 'Enviando...';
    message.textContent = '';
    try {
      const response = await fetch(window.location.href, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        body: new FormData(form),
        credentials: 'same-origin',
      });
      const result = await response.json();
      if (!response.ok || !result.ok) throw Error(result.message || 'Não foi possível enviar');
      preview(form, result.url);
      message.textContent = 'Imagem aplicada e salva na biblioteca.';
      form.querySelector('input[type=file]').value = '';
    } catch (error) {
      message.textContent = error.message;
    } finally {
      button.disabled = false;
      button.textContent = text;
    }
  }
  forms.forEach((form) =>
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      if (form.querySelector('input[type=file]').files.length) send(form);
    }),
  );
  const dialog = document.querySelector('#cms-library-dialog');
  if (!dialog) return;
  const feedback = dialog.querySelector('#cms-library-feedback'),
    search = dialog.querySelector('#cms-library-search');
  let selected = null;
  document.querySelectorAll('[data-library-slot]').forEach((button) =>
    button.addEventListener('click', () => {
      const slot = button.dataset.librarySlot;
      selected = forms.find((form) => form.querySelector('[name=slot]').value === slot);
      feedback.textContent = '';
      search.value = '';
      dialog.querySelectorAll('.cms-library-photo').forEach((item) => (item.hidden = false));
      dialog.querySelector('.cms-library-empty').hidden =
        !!dialog.querySelector('.cms-library-photo');
      dialog.showModal();
      search.focus();
    }),
  );
  dialog
    .querySelectorAll('.cms-library-close')
    .forEach((button) => button.addEventListener('click', () => dialog.close()));
  search.addEventListener('input', () => {
    let shown = 0;
    const query = search.value
      .toLocaleLowerCase('pt-BR')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
    dialog.querySelectorAll('.cms-library-photo').forEach((item) => {
      const label = item.dataset.search.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
      item.hidden = !label.includes(query);
      if (!item.hidden) shown++;
    });
    dialog.querySelector('.cms-library-empty').hidden = shown !== 0;
  });
  dialog.querySelectorAll('.cms-library-photo').forEach((button) =>
    button.addEventListener('click', async () => {
      if (!selected) return;
      feedback.textContent = 'Aplicando imagem...';
      dialog.querySelectorAll('.cms-library-photo').forEach((item) => (item.disabled = true));
      try {
        const data = new FormData();
        data.set('csrf', selected.querySelector('[name=csrf]').value);
        data.set('action', 'library');
        data.set('slot', selected.querySelector('[name=slot]').value);
        data.set('image_id', button.dataset.imageId);
        const response = await fetch(location.href, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
          body: data,
          credentials: 'same-origin',
        });
        const result = await response.json();
        if (!response.ok || !result.ok) throw Error(result.message || 'Erro ao vincular imagem');
        preview(selected, result.url);
        selected.querySelector('.cms-upload-feedback').textContent =
          'Imagem selecionada da biblioteca.';
        dialog.close();
      } catch (error) {
        feedback.textContent = error.message;
      } finally {
        dialog.querySelectorAll('.cms-library-photo').forEach((item) => (item.disabled = false));
      }
    }),
  );
});
