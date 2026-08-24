'use strict';

(function () {
  const page = document.querySelector('[data-theme-page]');
  if (!page) {
    return;
  }

  const table = page.querySelector('[data-theme-table]');
  const message = page.querySelector('[data-theme-message]');
  const dialog = page.querySelector('[data-theme-dialog]');
  const form = page.querySelector('[data-theme-form]');
  const formTitle = page.querySelector('[data-theme-form-title]');
  const formMessage = page.querySelector('[data-theme-form-message]');
  const activityList = page.querySelector('[data-theme-activity-list]');
  const activitySection = page.querySelector('[data-theme-activities-section]');
  const newButton = document.querySelector('[data-theme-new]');
  const ageGroups = JSON.parse(page.getAttribute('data-age-groups') || '[]');
  const groups = JSON.parse(page.getAttribute('data-groups') || '[]');
  let themes = [];

  function openDialog() {
    if (dialog && typeof dialog.showModal === 'function') {
      dialog.showModal();
    } else if (dialog) {
      dialog.setAttribute('open', 'open');
    }
  }

  function closeDialog() {
    if (dialog && typeof dialog.close === 'function') {
      dialog.close();
    } else if (dialog) {
      dialog.removeAttribute('open');
    }
  }

  function setMessage(text) {
    if (message) {
      message.textContent = text || '';
    }
  }

  function setFormMessage(text) {
    if (formMessage) {
      formMessage.textContent = text || '';
    }
  }

  function validationMessage(error) {
    const details = error?.hatalar || {};
    const messages = Object.values(details).filter(Boolean);
    return messages.length ? messages.join(' ') : error.message;
  }

  function formatDate(value) {
    if (!value) {
      return '-';
    }
    const parts = String(value).split('-');
    return parts.length === 3 ? `${parts[2]}.${parts[1]}.${parts[0]}` : value;
  }

  function weekEndFor(startValue) {
    const date = new Date(`${startValue}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
      return '';
    }
    const day = date.getDay() || 7;
    date.setDate(date.getDate() + (7 - day));
    return date.toISOString().slice(0, 10);
  }

  function groupCheckboxes(selected = []) {
    const selectedIds = selected.map(String);
    if (!groups.length) {
      return '<div class="empty-state compact-empty">Grup bulunamadi.</div>';
    }
    return groups.map((group) => `
      <label>
        <input type="checkbox" name="activity_group_ids[]" value="${escapeHtml(group.id)}" ${selectedIds.includes(String(group.id)) ? 'checked' : ''}>
        <span>${escapeHtml(group.ad || '-')} ${group.yas_araligi ? `<small>${escapeHtml(group.yas_araligi)}</small>` : ''}</span>
      </label>
    `).join('');
  }

  function addActivityRow(activity = {}) {
    const row = document.createElement('div');
    row.className = 'theme-activity-row';
    row.innerHTML = `
      <input type="hidden" name="activity_id" value="${escapeHtml(activity.id || '')}">
      <input type="hidden" name="activity_template_id" value="">
      <div class="theme-activity-fields">
        <label>
          <span>Etkinlik Basligi</span>
          <input name="activity_title" value="${escapeHtml(activity.title || '')}" required>
        </label>
      </div>
      <label class="theme-activity-description">
        <span>Etkinlik Aciklamasi</span>
        <textarea name="activity_description" rows="3">${escapeHtml(activity.description || '')}</textarea>
      </label>
      <div class="theme-activity-groups">
        <div class="theme-activity-group-head">
          <strong>Uygulanacak Gruplar</strong>
          <div>
            <button class="mini-btn" type="button" data-theme-activity-groups-all>Tumunu Sec</button>
            <button class="mini-btn" type="button" data-theme-activity-groups-clear>Temizle</button>
          </div>
        </div>
        <div class="check-list theme-group-list">
          ${groupCheckboxes(activity.group_ids || [])}
        </div>
      </div>
      <div class="theme-activity-actions">
        <button class="btn btn-danger" type="button" data-theme-activity-remove>Kaldir</button>
      </div>
    `;
    activityList.appendChild(row);
  }

  function fillForm(theme = null) {
    form.reset();
    setFormMessage('');
    activityList.innerHTML = '';
    if (activitySection) {
      activitySection.hidden = !theme;
    }
    form.elements.id.value = theme?.id || '';
    form.elements.title.value = theme?.title || '';
    form.elements.description.value = theme?.description || '';
    form.elements.week_start.value = theme?.week_start || '';
    form.elements.week_end.value = theme?.week_end || '';
    form.querySelectorAll('[name="age_group_ids[]"]').forEach((box) => {
      box.checked = (theme?.age_group_ids || []).map(String).includes(String(box.value));
    });
    (theme?.activities || []).forEach(addActivityRow);
    formTitle.textContent = theme ? 'Tema Duzenle' : 'Yeni Tema';
  }

  function renderTable() {
    if (!themes.length) {
      table.innerHTML = '<div class="empty-table">Tema bulunamadi.</div>';
      return;
    }
      table.innerHTML = `
      <table>
        <thead><tr><th>Tema adi</th><th>Tarih araligi</th><th>Yas gruplari</th><th>Gruplar</th><th>Etkinlik sayisi</th><th>Duzenle</th><th>Sil</th></tr></thead>
        <tbody>
          ${themes.map((theme) => `
            <tr>
              <td><strong>${escapeHtml(theme.title)}</strong></td>
              <td>${escapeHtml(formatDate(theme.week_start))} - ${escapeHtml(formatDate(theme.week_end))}</td>
              <td>${escapeHtml(theme.age_groups || '-')}</td>
              <td>${escapeHtml(theme.groups || '-')}</td>
              <td>${escapeHtml(theme.activity_count || 0)}</td>
              <td><button class="mini-btn" type="button" data-theme-edit="${escapeHtml(theme.id)}">Duzenle</button></td>
              <td><button class="btn btn-danger" type="button" data-theme-delete="${escapeHtml(theme.id)}">Sil</button></td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  async function loadThemes() {
    table.innerHTML = '<div class="empty-table">Yukleniyor...</div>';
    const result = await talyaAjax('haftalik_tema_listele');
    themes = result.veri || [];
    renderTable();
  }

  newButton?.addEventListener('click', () => {
    fillForm(null);
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
    if (event.target.closest('[data-theme-activity-add]')) {
      addActivityRow({});
      return;
    }
    if (event.target.closest('[data-theme-activity-remove]')) {
      event.target.closest('.theme-activity-row')?.remove();
      if (!activityList.children.length) {
        addActivityRow({});
      }
      return;
    }
    const groupAll = event.target.closest('[data-theme-activity-groups-all]');
    if (groupAll) {
      groupAll.closest('.theme-activity-row')?.querySelectorAll('[name="activity_group_ids[]"]').forEach((box) => { box.checked = true; });
      return;
    }
    const groupClear = event.target.closest('[data-theme-activity-groups-clear]');
    if (groupClear) {
      groupClear.closest('.theme-activity-row')?.querySelectorAll('[name="activity_group_ids[]"]').forEach((box) => { box.checked = false; });
      return;
    }
    const edit = event.target.closest('[data-theme-edit]');
    if (edit) {
      setMessage('Tema yukleniyor...');
      try {
        const result = await talyaAjax('haftalik_tema_detay', { id: edit.getAttribute('data-theme-edit') });
        fillForm(result.veri);
        setMessage('');
        openDialog();
      } catch (error) {
        setMessage(error.message);
      }
      return;
    }
    const deleteButton = event.target.closest('[data-theme-delete]');
    if (deleteButton) {
      if (!confirm('Tema silinsin mi? Bagli etkinlikler ve etkinlik kayitlari da silinebilir.')) {
        return;
      }
      try {
        const result = await talyaAjax('haftalik_tema_sil', { id: deleteButton.getAttribute('data-theme-delete') });
        setMessage(result.mesaj);
        await loadThemes();
      } catch (error) {
        setMessage(error.message);
      }
    }
  });

  page.addEventListener('change', (event) => {
    if (event.target === form?.elements.week_start) {
      const end = weekEndFor(form.elements.week_start.value);
      if (end && (!form.elements.week_end.value || form.elements.week_end.value < form.elements.week_start.value)) {
        form.elements.week_end.value = end;
      }
      return;
    }

  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    setFormMessage('Kaydediliyor...');
    const values = formValues(form);
    values.age_group_ids = Array.from(form.querySelectorAll('[name="age_group_ids[]"]:checked')).map((box) => box.value);
    values.activities = Array.from(activityList.querySelectorAll('.theme-activity-row')).map((row) => ({
      id: row.querySelector('[name="activity_id"]')?.value || '',
      activity_template_id: row.querySelector('[name="activity_template_id"]')?.value || '',
      title: row.querySelector('[name="activity_title"]')?.value || '',
      description: row.querySelector('[name="activity_description"]')?.value || '',
      group_ids: Array.from(row.querySelectorAll('[name="activity_group_ids[]"]:checked')).map((box) => box.value)
    }));

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

(function () {
  const page = document.querySelector('[data-template-page]');
  if (!page) {
    return;
  }

  const table = page.querySelector('[data-template-table]');
  const message = page.querySelector('[data-template-message]');
  const dialog = page.querySelector('[data-template-dialog]');
  const form = page.querySelector('[data-template-form]');
  const formTitle = page.querySelector('[data-template-form-title]');
  const formMessage = page.querySelector('[data-template-form-message]');
  const newButton = document.querySelector('[data-template-new]');
  let templates = [];

  function openDialog() {
    if (dialog && typeof dialog.showModal === 'function') {
      dialog.showModal();
    } else if (dialog) {
      dialog.setAttribute('open', 'open');
    }
  }

  function closeDialog() {
    if (dialog && typeof dialog.close === 'function') {
      dialog.close();
    } else if (dialog) {
      dialog.removeAttribute('open');
    }
  }

  function setMessage(text) {
    if (message) {
      message.textContent = text || '';
    }
  }

  function setFormMessage(text) {
    if (formMessage) {
      formMessage.textContent = text || '';
    }
  }

  function fillForm(row = null) {
    form.reset();
    setFormMessage('');
    form.elements.id.value = row?.id || '';
    form.elements.title.value = row?.title || '';
    form.elements.description.value = row?.description || '';
    form.elements.is_active.value = String(row?.is_active ?? 1);
    formTitle.textContent = row ? 'Sablon Duzenle' : 'Sablon Ekle';
  }

  function renderTable() {
    if (!templates.length) {
      table.innerHTML = '<div class="empty-table">Sablon bulunamadi.</div>';
      return;
    }
    table.innerHTML = `
      <table>
        <thead><tr><th>#</th><th>Sablon</th><th>Aciklama</th><th>Durum</th><th>Duzenle</th><th>Aktif/Pasif</th><th>Sil</th></tr></thead>
        <tbody>
          ${templates.map((row, index) => `
            <tr>
              <td>${index + 1}</td>
              <td><strong>${escapeHtml(row.title)}</strong></td>
              <td>${escapeHtml(row.description || '-')}</td>
              <td><span class="status-pill ${Number(row.is_active) === 1 ? 'is-success' : 'is-danger'}">${Number(row.is_active) === 1 ? 'Aktif' : 'Pasif'}</span></td>
              <td><button class="mini-btn" type="button" data-template-edit="${escapeHtml(row.id)}">Duzenle</button></td>
              <td><button class="btn btn-ghost" type="button" data-template-toggle="${escapeHtml(row.id)}" data-active="${Number(row.is_active) === 1 ? '0' : '1'}">${Number(row.is_active) === 1 ? 'Pasif Yap' : 'Aktif Yap'}</button></td>
              <td><button class="btn btn-danger" type="button" data-template-delete="${escapeHtml(row.id)}">Sil</button></td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  async function loadTemplates() {
    table.innerHTML = '<div class="empty-table">Yukleniyor...</div>';
    const result = await talyaAjax('etkinlik_sablon_listele');
    templates = result.veri || [];
    renderTable();
  }

  newButton?.addEventListener('click', () => {
    fillForm(null);
    openDialog();
  });

  page.addEventListener('click', async (event) => {
    if (event.target.closest('[data-template-dialog-close]')) {
      closeDialog();
      return;
    }
    const edit = event.target.closest('[data-template-edit]');
    if (edit) {
      fillForm(templates.find((item) => String(item.id) === String(edit.getAttribute('data-template-edit'))) || null);
      openDialog();
      return;
    }
    const toggle = event.target.closest('[data-template-toggle]');
    if (toggle) {
      try {
        const result = await talyaAjax('etkinlik_sablon_durum', {
          id: toggle.getAttribute('data-template-toggle'),
          is_active: toggle.getAttribute('data-active')
        });
        setMessage(result.mesaj);
        await loadTemplates();
      } catch (error) {
        setMessage(error.message);
      }
      return;
    }
    const deleteButton = event.target.closest('[data-template-delete]');
    if (deleteButton) {
      if (!confirm('Sablon silinsin mi? Gecmis etkinlikler silinmez.')) {
        return;
      }
      try {
        const result = await talyaAjax('etkinlik_sablon_sil', { id: deleteButton.getAttribute('data-template-delete') });
        setMessage(result.mesaj);
        await loadTemplates();
      } catch (error) {
        setMessage(error.message);
      }
    }
  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    setFormMessage('Kaydediliyor...');
    try {
      const result = await talyaAjax('etkinlik_sablon_kaydet', formValues(form));
      setMessage(result.mesaj);
      closeDialog();
      await loadTemplates();
    } catch (error) {
      setFormMessage(error.message);
    }
  });

  loadTemplates().catch((error) => setMessage(error.message));
})();

(function () {
  const page = document.querySelector('[data-group-preset-page]');
  if (!page) {
    return;
  }

  const groups = JSON.parse(page.getAttribute('data-groups') || '[]');
  const table = page.querySelector('[data-group-preset-table]');
  const message = page.querySelector('[data-group-preset-message]');
  const dialog = page.querySelector('[data-group-preset-dialog]');
  const form = page.querySelector('[data-group-preset-form]');
  const formTitle = page.querySelector('[data-group-preset-form-title]');
  const formMessage = page.querySelector('[data-group-preset-form-message]');
  const newButton = document.querySelector('[data-group-preset-new]');
  let presets = [];

  function openDialog() {
    if (dialog && typeof dialog.showModal === 'function') {
      dialog.showModal();
    } else if (dialog) {
      dialog.setAttribute('open', 'open');
    }
  }

  function closeDialog() {
    if (dialog && typeof dialog.close === 'function') {
      dialog.close();
    } else if (dialog) {
      dialog.removeAttribute('open');
    }
  }

  function setMessage(text) {
    if (message) {
      message.textContent = text || '';
    }
  }

  function setFormMessage(text) {
    if (formMessage) {
      formMessage.textContent = text || '';
    }
  }

  function groupNames(ids = []) {
    const set = ids.map(String);
    return groups
      .filter((group) => set.includes(String(group.id)))
      .map((group) => group.ad || '-')
      .join(', ') || '-';
  }

  function validationMessage(error) {
    const details = error?.hatalar || {};
    const messages = Object.values(details).filter(Boolean);
    return messages.length ? messages.join(' ') : error.message;
  }

  function fillForm(preset = null) {
    form.reset();
    setFormMessage('');
    form.elements.id.value = preset?.id || '';
    form.elements.title.value = preset?.title || '';
    const selected = (preset?.group_ids || []).map(String);
    form.querySelectorAll('[name="group_ids[]"]').forEach((box) => {
      box.checked = selected.includes(String(box.value));
    });
    formTitle.textContent = preset ? 'Secim Duzenle' : 'Yeni Secim';
  }

  function renderTable() {
    if (!presets.length) {
      table.innerHTML = '<div class="empty-table">Grup secimi bulunamadi.</div>';
      return;
    }

    table.innerHTML = `
      <table>
        <thead><tr><th>Secim Adi</th><th>Gruplar</th><th>Grup Sayisi</th><th>Duzenle</th><th>Sil</th></tr></thead>
        <tbody>
          ${presets.map((preset) => `
            <tr>
              <td><strong>${escapeHtml(preset.title || '-')}</strong></td>
              <td>${escapeHtml(groupNames(preset.group_ids || []))}</td>
              <td>${(preset.group_ids || []).length}</td>
              <td><button class="mini-btn" type="button" data-group-preset-edit="${escapeHtml(preset.id)}">Duzenle</button></td>
              <td><button class="btn btn-danger" type="button" data-group-preset-delete="${escapeHtml(preset.id)}">Sil</button></td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  async function loadPresets() {
    table.innerHTML = '<div class="empty-table">Yukleniyor...</div>';
    const result = await talyaAjax('tema_grup_secim_listele');
    presets = result.veri || [];
    renderTable();
  }

  newButton?.addEventListener('click', () => {
    fillForm(null);
    openDialog();
  });

  page.addEventListener('click', async (event) => {
    if (event.target.closest('[data-group-preset-dialog-close]')) {
      closeDialog();
      return;
    }
    if (event.target.closest('[data-group-preset-all]')) {
      form.querySelectorAll('[name="group_ids[]"]').forEach((box) => { box.checked = true; });
      return;
    }
    if (event.target.closest('[data-group-preset-clear]')) {
      form.querySelectorAll('[name="group_ids[]"]').forEach((box) => { box.checked = false; });
      return;
    }
    const edit = event.target.closest('[data-group-preset-edit]');
    if (edit) {
      const preset = presets.find((item) => String(item.id) === String(edit.getAttribute('data-group-preset-edit')));
      fillForm(preset || null);
      openDialog();
      return;
    }
    const deleteButton = event.target.closest('[data-group-preset-delete]');
    if (deleteButton) {
      if (!confirm('Grup secimi silinsin mi?')) {
        return;
      }
      try {
        const result = await talyaAjax('tema_grup_secim_sil', { id: deleteButton.getAttribute('data-group-preset-delete') });
        setMessage(result.mesaj);
        await loadPresets();
      } catch (error) {
        setMessage(error.message);
      }
    }
  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    setFormMessage('Kaydediliyor...');
    const values = formValues(form);
    values.group_ids = Array.from(form.querySelectorAll('[name="group_ids[]"]:checked')).map((box) => box.value);
    try {
      const result = await talyaAjax('tema_grup_secim_kaydet', values);
      setMessage(result.mesaj);
      closeDialog();
      await loadPresets();
    } catch (error) {
      setFormMessage(validationMessage(error));
    }
  });

  loadPresets().catch((error) => setMessage(error.message));
})();

(function () {
  const section = document.querySelector('[data-student-theme-records]');
  if (!section) {
    return;
  }

  const form = section.querySelector('[data-student-theme-form]');
  const themeSelect = section.querySelector('[data-student-theme-select]');
  const activitySelect = section.querySelector('[data-student-activity-select]');
  const message = section.querySelector('[data-student-theme-message]');
  const studentId = section.getAttribute('data-student-id') || '';
  const themes = JSON.parse(section.getAttribute('data-themes') || '[]');

  function setMessage(text) {
    if (message) {
      message.textContent = text || '';
    }
  }

  function renderActivities() {
    const theme = themes.find((item) => String(item.id) === String(themeSelect?.value || ''));
    const activities = theme?.activities || [];
    if (!activitySelect) {
      return;
    }
    activitySelect.disabled = activities.length === 0;
    activitySelect.innerHTML = activities.length
      ? '<option value="">Etkinlik seciniz</option>' + activities.map((item) => `<option value="${escapeHtml(item.id)}">${escapeHtml(item.title)}</option>`).join('')
      : '<option value="">Bu temada etkinlik yok</option>';
  }

  themeSelect?.addEventListener('change', renderActivities);

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    setMessage('Kaydediliyor...');
    const values = formValues(form);
    values.student_id = studentId;
    try {
      await talyaAjax('ogrenci_etkinlik_ekle', values);
      window.location.reload();
    } catch (error) {
      setMessage(error.message);
    }
  });

  section.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-student-theme-delete]');
    if (!button) {
      return;
    }
    if (!confirm('Etkinlik kaydi silinsin mi?')) {
      return;
    }
    setMessage('Siliniyor...');
    try {
      await talyaAjax('ogrenci_etkinlik_sil', {
        id: button.getAttribute('data-student-theme-delete'),
        student_id: studentId
      });
      window.location.reload();
    } catch (error) {
      setMessage(error.message);
    }
  });

  renderActivities();
})();
