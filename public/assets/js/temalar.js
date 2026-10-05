'use strict';

(function () {
  const page = document.querySelector('[data-theme-page]');
  if (!page) return;

  const table = page.querySelector('[data-theme-table]');
  const message = page.querySelector('[data-theme-message]');
  const dialog = page.querySelector('[data-theme-dialog]');
  const form = page.querySelector('[data-theme-form]');
  const formTitle = page.querySelector('[data-theme-form-title]');
  const formMessage = page.querySelector('[data-theme-form-message]');
  const newButton = document.querySelector('[data-theme-new]');
  let themes = [];

  function openDialog() {
    if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
    else dialog?.setAttribute('open', 'open');
  }

  function closeDialog() {
    if (dialog && typeof dialog.close === 'function') dialog.close();
    else dialog?.removeAttribute('open');
  }

  function setMessage(text) {
    if (message) message.textContent = text || '';
  }

  function setFormMessage(text) {
    if (formMessage) formMessage.textContent = text || '';
  }

  function validationMessage(error) {
    const details = Object.values(error?.hatalar || {}).filter(Boolean);
    return details.length ? details.join(' ') : error.message;
  }

  function formatDate(value) {
    if (!value) return '-';
    const parts = String(value).split('-');
    return parts.length === 3 ? `${parts[2]}.${parts[1]}.${parts[0]}` : value;
  }

  function weekEndFor(startValue) {
    const date = new Date(`${startValue}T00:00:00`);
    if (Number.isNaN(date.getTime())) return '';
    const day = date.getDay() || 7;
    date.setDate(date.getDate() + (7 - day));
    return date.toISOString().slice(0, 10);
  }

  function fillForm(theme = null) {
    form.reset();
    setFormMessage('');
    form.elements.id.value = theme?.id || '';
    form.elements.title.value = theme?.title || '';
    form.elements.description.value = theme?.description || '';
    form.elements.week_start.value = theme?.week_start || '';
    form.elements.week_end.value = theme?.week_end || '';
    const selected = (theme?.age_group_ids || []).map(String);
    form.querySelectorAll('[name="age_group_ids[]"]').forEach((box) => {
      box.checked = selected.includes(String(box.value));
    });
    formTitle.textContent = theme ? 'Tema Düzenle' : 'Yeni Tema';
  }

  function renderTable() {
    if (!themes.length) {
      table.innerHTML = '<div class="empty-table">Tema bulunamadı.</div>';
      return;
    }
    table.innerHTML = `
      <table>
        <thead><tr><th>Tema adı</th><th>Tarih aralığı</th><th>Yaş grupları</th><th>Açıklama</th><th>Düzenle</th><th>Sil</th></tr></thead>
        <tbody>
          ${themes.map((theme) => `
            <tr>
              <td><strong>${escapeHtml(theme.title)}</strong></td>
              <td>${escapeHtml(formatDate(theme.week_start))} - ${escapeHtml(formatDate(theme.week_end))}</td>
              <td>${escapeHtml(theme.age_groups || '-')}</td>
              <td>${escapeHtml(theme.description || '-')}</td>
              <td><button class="mini-btn" type="button" data-theme-edit="${escapeHtml(theme.id)}">Düzenle</button></td>
              <td><button class="btn btn-danger" type="button" data-theme-delete="${escapeHtml(theme.id)}">Sil</button></td>
            </tr>
          `).join('')}
        </tbody>
      </table>`;
  }

  async function loadThemes() {
    table.innerHTML = '<div class="empty-table">Yükleniyor...</div>';
    const result = await talyaAjax('haftalik_tema_listele');
    themes = result.veri || [];
    renderTable();
  }

  newButton?.addEventListener('click', () => {
    fillForm();
    openDialog();
  });

  page.addEventListener('click', async (event) => {
    if (event.target.closest('[data-theme-dialog-close]')) {
      closeDialog();
      return;
    }
    if (event.target.closest('[data-theme-age-all]')) {
      form.querySelectorAll('[name="age_group_ids[]"]').forEach((box) => { box.checked = true; });
      return;
    }
    if (event.target.closest('[data-theme-age-clear]')) {
      form.querySelectorAll('[name="age_group_ids[]"]').forEach((box) => { box.checked = false; });
      return;
    }

    const editButton = event.target.closest('[data-theme-edit]');
    if (editButton) {
      setMessage('Tema yükleniyor...');
      try {
        const result = await talyaAjax('haftalik_tema_detay', { id: editButton.dataset.themeEdit });
        fillForm(result.veri);
        setMessage('');
        openDialog();
      } catch (error) {
        setMessage(error.message);
      }
      return;
    }

    const deleteButton = event.target.closest('[data-theme-delete]');
    if (!deleteButton || !confirm('Tema silinsin mi?')) return;
    try {
      const result = await talyaAjax('haftalik_tema_sil', { id: deleteButton.dataset.themeDelete });
      setMessage(result.mesaj);
      await loadThemes();
    } catch (error) {
      setMessage(error.message);
    }
  });

  form.elements.week_start?.addEventListener('change', () => {
    const end = weekEndFor(form.elements.week_start.value);
    if (end && (!form.elements.week_end.value || form.elements.week_end.value < form.elements.week_start.value)) {
      form.elements.week_end.value = end;
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    setFormMessage('Kaydediliyor...');
    const values = formValues(form);
    values.age_group_ids = Array.from(form.querySelectorAll('[name="age_group_ids[]"]:checked')).map((box) => box.value);
    try {
      const result = await talyaAjax('haftalik_tema_kaydet', values);
      setMessage(result.mesaj);
      closeDialog();
      await loadThemes();
    } catch (error) {
      setFormMessage(validationMessage(error));
    }
  });

  loadThemes().catch((error) => setMessage(error.message));
})();
