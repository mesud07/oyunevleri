(function () {
  const page = document.querySelector('[data-user-page]');
  if (!page) {
    return;
  }

  const table = page.querySelector('[data-user-table]');
  const message = page.querySelector('[data-user-message]');
  const dialog = page.querySelector('[data-user-dialog]');
  const form = page.querySelector('[data-user-form]');
  const formTitle = page.querySelector('[data-user-form-title]');
  const formMessage = page.querySelector('[data-user-form-message]');
  const newButton = document.querySelector('[data-user-new]');
  const rolePage = document.querySelector('[data-role-page]');
  const roleTable = rolePage?.querySelector('[data-role-table]');
  const roleMessage = rolePage?.querySelector('[data-role-message]');
  const roleDialog = rolePage?.querySelector('[data-role-dialog]');
  const roleForm = rolePage?.querySelector('[data-role-form]');
  const roleTitle = rolePage?.querySelector('[data-role-form-title]');
  const roleFormMessage = rolePage?.querySelector('[data-role-form-message]');
  const roleNewButton = rolePage?.querySelector('[data-role-new]');
  const permissionMeta = JSON.parse(rolePage?.querySelector('[data-role-permission-map]')?.textContent || '[]');
  let rows = [];
  let roles = [];

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

  function setRoleMessage(text) {
    if (roleMessage) {
      roleMessage.textContent = text || '';
    }
  }

  function setRoleFormMessage(text) {
    if (roleFormMessage) {
      roleFormMessage.textContent = text || '';
    }
  }

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

  function openRoleDialog() {
    if (roleDialog && typeof roleDialog.showModal === 'function') {
      roleDialog.showModal();
    } else if (roleDialog) {
      roleDialog.setAttribute('open', 'open');
    }
  }

  function closeRoleDialog() {
    if (roleDialog && typeof roleDialog.close === 'function') {
      roleDialog.close();
    } else if (roleDialog) {
      roleDialog.removeAttribute('open');
    }
  }

  function fillForm(row = null) {
    form.reset();
    setFormMessage('');
    form.elements.id.value = row?.id || '';
    form.elements.ad.value = row?.ad || '';
    form.elements.soyad.value = row?.soyad || '';
    form.elements.eposta.value = row?.eposta || '';
    form.elements.telefon.value = row?.telefon || '';
    form.elements.rol_id.value = row?.rol_id || form.elements.rol_id.value;
    form.elements.aktif.value = String(row?.aktif ?? 1);
    form.elements.sifre.value = '';
    formTitle.textContent = row ? 'Kullanici Duzenle' : 'Kullanici Ekle';
  }

  function renderTable() {
    if (!rows.length) {
      table.innerHTML = '<div class="empty-table">Kullanici bulunamadi.</div>';
      return;
    }
    table.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Ad Soyad</th>
            <th>Kullanici Adi / E-posta</th>
            <th>Rol</th>
            <th>Telefon</th>
            <th>Son Giris</th>
            <th>Durum</th>
            <th>Islem</th>
          </tr>
        </thead>
        <tbody>
          ${rows.map((row, index) => `
            <tr>
              <td>${index + 1}</td>
              <td><strong>${escapeHtml(`${row.ad || ''} ${row.soyad || ''}`.trim())}</strong></td>
              <td>${escapeHtml(row.eposta || '-')}</td>
              <td>${escapeHtml(row.rol_adi || '-')}</td>
              <td>${escapeHtml(row.telefon || '-')}</td>
              <td>${escapeHtml(row.son_giris_tarihi || '-')}</td>
              <td><span class="status-pill ${Number(row.aktif) === 1 ? 'is-success' : 'is-danger'}">${Number(row.aktif) === 1 ? 'Aktif' : 'Pasif'}</span></td>
              <td><button class="mini-btn" type="button" data-user-edit="${escapeHtml(row.id)}">Duzenle</button></td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  function refreshRoleOptions() {
    const select = form?.elements.rol_id;
    if (!select) {
      return;
    }
    const selected = select.value;
    select.innerHTML = roles.map((role) => `<option value="${escapeHtml(role.id)}">${escapeHtml(role.ad)}</option>`).join('');
    if (selected) {
      select.value = selected;
    }
  }

  function renderRoleTable() {
    if (!roleTable) {
      return;
    }
    if (!roles.length) {
      roleTable.innerHTML = '<div class="empty-table">Kullanici tipi bulunamadi.</div>';
      return;
    }
    roleTable.innerHTML = `
      <table>
        <thead><tr><th>#</th><th>Tip</th><th>Kod</th><th>Menu</th><th>Islem</th><th>Toplam</th><th>Islem</th></tr></thead>
        <tbody>
          ${roles.map((role, index) => `
            <tr>
              <td>${index + 1}</td>
              <td><strong>${escapeHtml(role.ad || '-')}</strong></td>
              <td>${escapeHtml(role.kod || '-')}</td>
              <td>${permissionCount(role, 'menu')}</td>
              <td>${permissionCount(role, 'islem')}</td>
              <td>${(role.yetkiler || []).length}</td>
              <td><button class="mini-btn" type="button" data-role-edit="${escapeHtml(role.id)}">Duzenle</button></td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  function permissionCount(role, type) {
    const permissions = new Set(role?.yetkiler || []);
    return permissionMeta.filter((item) => item.kategori === type && permissions.has(item.kod)).length;
  }

  function syncRoleGroupToggles() {
    if (!roleForm) return;
    roleForm.querySelectorAll('[data-role-group-toggle]').forEach((toggle) => {
      const group = toggle.dataset.roleGroupToggle || '';
      const kind = toggle.dataset.roleGroupKind || '';
      const boxes = Array.from(roleForm.querySelectorAll(`[data-permission-group="${CSS.escape(group)}"][data-permission-kind="${CSS.escape(kind)}"] input[type="checkbox"]`));
      toggle.checked = boxes.length > 0 && boxes.every((box) => box.checked);
      toggle.indeterminate = boxes.some((box) => box.checked) && !boxes.every((box) => box.checked);
    });
  }

  function setSectionSelection(kind, checked) {
    if (!roleForm) return;
    roleForm.querySelectorAll(`[data-permission-kind="${CSS.escape(kind)}"] input[type="checkbox"]`).forEach((box) => {
      box.checked = checked;
    });
    syncRoleGroupToggles();
  }

  function fillRoleForm(role = null) {
    roleForm.reset();
    setRoleFormMessage('');
    roleForm.elements.id.value = role?.id || '';
    roleForm.elements.ad.value = role?.ad || '';
    roleForm.elements.kod.value = role?.kod || '';
    roleForm.elements.kod.readOnly = Boolean(role);
    roleForm.querySelectorAll('[name="yetkiler[]"]').forEach((box) => {
      box.checked = (role?.yetkiler || []).includes(box.value);
    });
    roleTitle.textContent = role ? 'Kullanici Tipi Duzenle' : 'Kullanici Tipi Ekle';
    syncRoleGroupToggles();
  }

  async function loadUsers() {
    table.innerHTML = '<div class="empty-table">Yukleniyor...</div>';
    const result = await talyaAjax('kullanici_listele');
    rows = result.veri || [];
    renderTable();
  }

  async function loadRoles() {
    if (!rolePage) {
      return;
    }
    const result = await talyaAjax('kullanici_rolleri');
    roles = result.veri?.roller || [];
    renderRoleTable();
    refreshRoleOptions();
  }

  newButton?.addEventListener('click', () => {
    fillForm(null);
    openDialog();
  });

  page.addEventListener('click', (event) => {
    if (event.target.closest('[data-user-dialog-close]')) {
      closeDialog();
      return;
    }
    const edit = event.target.closest('[data-user-edit]');
    if (edit) {
      const row = rows.find((item) => String(item.id) === String(edit.getAttribute('data-user-edit')));
      fillForm(row || null);
      openDialog();
    }
  });

  roleNewButton?.addEventListener('click', () => {
    fillRoleForm(null);
    openRoleDialog();
  });

  rolePage?.addEventListener('click', (event) => {
    if (event.target.closest('[data-role-dialog-close]')) {
      closeRoleDialog();
      return;
    }
    const sectionToggle = event.target.closest('[data-role-toggle-section]');
    if (sectionToggle) {
      const kind = sectionToggle.getAttribute('data-role-toggle-section');
      const targetBoxes = Array.from(roleForm?.querySelectorAll(`[data-permission-kind="${CSS.escape(kind || '')}"] input[type="checkbox"]`) || []);
      const shouldCheck = targetBoxes.some((box) => !box.checked);
      setSectionSelection(kind || '', shouldCheck);
      sectionToggle.textContent = shouldCheck
        ? (kind === 'menu' ? 'Tum menuleri kaldir' : 'Tum islemleri kaldir')
        : (kind === 'menu' ? 'Tum menuleri sec' : 'Tum islemleri sec');
      return;
    }
    const edit = event.target.closest('[data-role-edit]');
    if (edit) {
      const role = roles.find((item) => String(item.id) === String(edit.getAttribute('data-role-edit')));
      fillRoleForm(role || null);
      openRoleDialog();
    }
  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    setFormMessage('Kaydediliyor...');
    try {
      const values = formValues(form);
      const result = await talyaAjax('kullanici_kaydet', values);
      setMessage(result.mesaj);
      closeDialog();
      await loadUsers();
    } catch (error) {
      setFormMessage(error.message);
    }
  });

  roleForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    setRoleFormMessage('Kaydediliyor...');
    try {
      const values = formValues(roleForm);
      values.yetkiler = Array.from(roleForm.querySelectorAll('[name="yetkiler[]"]:checked')).map((box) => box.value);
      const result = await talyaAjax('kullanici_rol_kaydet', values);
      setRoleMessage(result.mesaj);
      closeRoleDialog();
      await loadRoles();
    } catch (error) {
      setRoleFormMessage(error.message);
    }
  });

  roleForm?.addEventListener('change', (event) => {
    const groupToggle = event.target.closest('[data-role-group-toggle]');
    if (groupToggle) {
      const group = groupToggle.dataset.roleGroupToggle || '';
      const kind = groupToggle.dataset.roleGroupKind || '';
      roleForm.querySelectorAll(`[data-permission-group="${CSS.escape(group)}"][data-permission-kind="${CSS.escape(kind)}"] input[type="checkbox"]`).forEach((box) => {
        box.checked = groupToggle.checked;
      });
      syncRoleGroupToggles();
      return;
    }

    if (event.target.matches('[name="yetkiler[]"]')) {
      syncRoleGroupToggles();
    }
  });

  Promise.all([loadRoles(), loadUsers()]).catch((error) => setMessage(error.message));
})();
