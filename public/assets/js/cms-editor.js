document.addEventListener('DOMContentLoaded', () => {
  const textForm = document.querySelector('.cms-text-form');
  if (textForm) {
    const inputs = [...textForm.querySelectorAll('.cms-text-field input, .cms-text-field textarea')];
    const initial = new Map(inputs.map((input) => [input.name, input.value]));
    const saveButton = document.querySelector('#cms-save-texts');
    const status = document.querySelector('#cms-text-feedback');
    const filter = document.querySelector('#cms-field-filter');
    const fields = [...document.querySelectorAll('.cms-text-field')];
    const count = document.querySelector('#cms-field-total');
    let saving = false;

    const changed = () => inputs.filter((input) => input.value !== initial.get(input.name));
    const refresh = () => {
      const pending = changed().length;
      saveButton.disabled = saving || pending === 0;
      status.textContent = pending ? pending + ' alteração(ões) pendente(s)' : 'Nenhuma alteração pendente';
    };

    inputs.forEach((input) => input.addEventListener('input', refresh));
    filter?.addEventListener('input', () => {
      const term = filter.value.normalize('NFD').replace(/[\\u0300-\\u036f]/g, '').toLowerCase().trim();
      let visible = 0;
      fields.forEach((field) => {
        const words = (field.dataset.fieldSearch + ' ' + field.querySelector('input,textarea').value)
          .normalize('NFD').replace(/[\\u0300-\\u036f]/g, '').toLowerCase();
        field.hidden = !words.includes(term);
        if (!field.hidden) visible++;
      });
      count.textContent = visible + ' de ' + fields.length;
      document.querySelector('#cms-field-empty').hidden = visible !== 0;
    });

    textForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (saving || changed().length === 0) return;
      saving = true;
      saveButton.disabled = true;
      status.textContent = 'Salvando textos...';
      try {
        const response = await fetch(location.href, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
          body: new FormData(textForm),
          credentials: 'same-origin',
        });
        const data = await response.json();
        if (!response.ok || !data.ok) throw Error(data.message || 'Não foi possível salvar.');
        inputs.forEach((input) => initial.set(input.name, input.value));
        refresh();
        status.textContent = 'Textos salvos com sucesso.';
      } catch (error) {
        status.textContent = error.message;
      } finally {
        saving = false;
        saveButton.disabled = changed().length === 0;
      }
    });

    window.addEventListener('beforeunload', (event) => {
      if (changed().length && !saving) {
        event.preventDefault();
        event.returnValue = '';
      }
    });
    refresh();
  }

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
      form.querySelector('.cms-file-action').textContent = 'Enviar nova foto';
    } catch (error) {
      message.textContent = error.message;
    } finally {
      button.disabled = !form.querySelector('input[type=file]').files.length;
      button.textContent = text;
    }
  }
  forms.forEach((form) => {
    const file = form.querySelector('input[type=file]');
    const submit = form.querySelector('button[type=submit]');
    submit.disabled = true;
    file.addEventListener('change', () => {
      submit.disabled = !file.files.length;
      const label = form.querySelector('.cms-file-action');
      if (file.files.length) label.textContent = file.files[0].name;
    });
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      if (file.files.length) send(form);
    });
  });
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
