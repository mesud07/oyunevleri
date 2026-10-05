(function () {
  const page = document.querySelector('[data-institution-page]');
  if (!page) {
    return;
  }

  const table = page.querySelector('[data-institution-table]');
  const message = page.querySelector('[data-institution-message]');
  const dialog = page.querySelector('[data-institution-dialog]');
  const form = page.querySelector('[data-institution-form]');
  const formTitle = page.querySelector('[data-institution-form-title]');
  const formMessage = page.querySelector('[data-institution-form-message]');
  const newButton = document.querySelector('[data-institution-new]');
  const founderFields = form.querySelector('[data-institution-founder-fields]');
  const founderInputs = Array.from(founderFields?.querySelectorAll('input') || []);
  const founderStatus = form.querySelector('[data-institution-founder-status]');
  const founderDescription = form.querySelector('[data-institution-founder-description]');
  const moduleBoxes = Array.from(form.querySelectorAll('[name="moduller[]"]'));
  const pageBoxes = Array.from(form.querySelectorAll('[name="sayfalar[]"]'));
  const modulesToggle = form.querySelector('[data-institution-modules-toggle]');
  const managerStatus = form.querySelector('[data-institution-manager-status]');
  const logoSection = form.querySelector('[data-institution-logo-section]');
  const logoPreview = form.querySelector('[data-institution-logo-preview]');
  const logoInput = form.querySelector('[data-institution-logo-input]');
  const logoUpload = form.querySelector('[data-institution-logo-upload]');
  const logoMessage = form.querySelector('[data-institution-logo-message]');
  const portalSection = form.querySelector('[data-institution-portal-section]');
  const portalUrl = form.querySelector('[data-institution-portal-url]');
  const portalCopy = form.querySelector('[data-institution-portal-copy]');
  const portalOpen = form.querySelector('[data-institution-portal-open]');
  const portalMessage = form.querySelector('[data-institution-portal-message]');
  let rows = [];

  function syncModulePages(moduleBox) {
    const card = moduleBox.closest('[data-institution-module]');
    Array.from(card?.querySelectorAll('[data-institution-page-option]') || []).forEach((box) => {
      box.disabled = !moduleBox.checked;
    });
  }

  function syncModuleControls() {
    moduleBoxes.forEach(syncModulePages);
    if (modulesToggle) {
      modulesToggle.textContent = moduleBoxes.every((box) => box.checked) ? 'Tümünü Kaldır' : 'Tümünü Seç';
    }
  }

  function openDialog() {
    if (typeof dialog.showModal === 'function') {
      dialog.showModal();
      return;
    }
    dialog.setAttribute('open', 'open');
  }

  function closeDialog() {
    if (typeof dialog.close === 'function') {
      dialog.close();
      return;
    }
    dialog.removeAttribute('open');
  }

  function fillForm(row = null) {
    form.reset();
    form.elements.id.value = row?.id || '';
    form.elements.ad.value = row?.ad || '';
    form.elements.kod.value = row?.kod || '';
    form.elements.aktif.value = String(row?.aktif ?? 0);
    moduleBoxes.forEach((box) => {
      box.checked = row ? row.moduller?.[box.value] !== false : true;
    });
    pageBoxes.forEach((box) => {
      box.checked = row ? row.sayfalar?.[box.value] !== false : true;
    });
    syncModuleControls();
    form.elements.mudur_ad.value = row?.mudur_ad || '';
    form.elements.mudur_soyad.value = row?.mudur_soyad || '';
    form.elements.mudur_eposta.value = row?.mudur_eposta || '';
    form.elements.mudur_telefon.value = row?.mudur_telefon || '';
    form.elements.mudur_aktif.value = String(row?.mudur_aktif ?? 1);
    form.elements.mudur_sifre.value = '';
    form.elements.mudur_sifre.required = false;
    form.elements.mudur_sifre.placeholder = row?.mudur_id
      ? 'Değiştirmeyecekseniz boş bırakın'
      : 'Yeni hesap için en az 12 karakter';
    if (managerStatus) {
      managerStatus.textContent = row?.mudur_id ? (Number(row.mudur_aktif) === 1 ? 'Aktif' : 'Pasif') : 'Tanımlı değil';
      managerStatus.className = `status-pill ${row?.mudur_id && Number(row.mudur_aktif) === 1 ? 'is-success' : 'is-danger'}`;
    }
    if (logoSection) {
      logoSection.hidden = !row;
    }
    if (logoInput) {
      logoInput.value = '';
    }
    if (logoMessage) {
      logoMessage.textContent = '';
    }
    if (logoPreview) {
      logoPreview.innerHTML = row?.logo_yolu
        ? `<img src="${escapeHtml(row.logo_yolu)}?v=${Date.now()}" alt="${escapeHtml(row.ad || 'Kurum')} logosu">`
        : '<span>Logo yüklenmedi</span>';
    }
    const institutionPortalUrl = row?.veli_portal_anahtari
      ? `${window.location.origin}/veli-portal?k=${encodeURIComponent(row.veli_portal_anahtari)}`
      : '';
    if (portalSection) {
      portalSection.hidden = !row || !institutionPortalUrl;
    }
    if (portalUrl) {
      portalUrl.value = institutionPortalUrl;
    }
    if (portalOpen) {
      portalOpen.href = institutionPortalUrl || '#';
    }
    if (portalMessage) {
      portalMessage.textContent = '';
    }
    const founderRequired = !row || Number(row.kurucu_sayisi || 0) === 0;
    if (founderFields) {
      founderFields.hidden = false;
    }
    form.elements.kurucu_ad.value = row?.kurucu_ad || '';
    form.elements.kurucu_soyad.value = row?.kurucu_soyad || '';
    form.elements.kurucu_eposta.value = row?.kurucu_eposta || '';
    form.elements.kurucu_telefon.value = row?.kurucu_telefon || '';
    form.elements.kurucu_sifre.value = '';
    form.elements.kurucu_sifre.placeholder = founderRequired
      ? 'Yeni hesap için en az 12 karakter'
      : 'Şifre güvenlik nedeniyle gösterilmez';
    if (founderStatus) {
      founderStatus.textContent = row?.kurucu_id ? (Number(row.kurucu_aktif) === 1 ? 'Aktif' : 'Pasif') : 'Tanımlı değil';
      founderStatus.className = `status-pill ${row?.kurucu_id && Number(row.kurucu_aktif) === 1 ? 'is-success' : 'is-danger'}`;
    }
    if (founderDescription) {
      founderDescription.textContent = founderRequired
        ? 'Kurum için tüm panel yetkilerine sahip yeni kurucu hesabını tanımlayın.'
        : 'Mevcut kurucu hesabı görüntüleniyor. Şifre güvenlik nedeniyle gösterilmez.';
    }
    founderInputs.forEach((input) => {
      input.required = founderRequired && input.name !== 'kurucu_telefon';
      input.disabled = !founderRequired;
    });
    formTitle.textContent = row ? 'Kurumu Duzenle' : 'Yeni Kurum';
    formMessage.textContent = '';
  }

  function render() {
    if (!rows.length) {
      table.innerHTML = '<div class="empty-table">Kurum bulunamadi.</div>';
      return;
    }

    table.innerHTML = `
      <table>
        <thead>
            <tr><th>#</th><th>Logo</th><th>Kurum</th><th>Kod</th><th>Veli Portalı</th><th>Modül</th><th>Kullanici</th><th>Kurucu</th><th>Müdür</th><th>Ogrenci</th><th>Durum</th><th>Islem</th></tr>
        </thead>
        <tbody>
          ${rows.map((row, index) => `
            <tr>
              <td>${index + 1}</td>
              <td>${row.logo_yolu ? `<img class="institution-table-logo" src="${escapeHtml(row.logo_yolu)}" alt="">` : '<span class="muted">-</span>'}</td>
              <td><strong>${escapeHtml(row.ad || '-')}</strong><br><small>${escapeHtml(row.olusturulma_tarihi || '')}</small></td>
              <td><span class="status-pill">${escapeHtml(row.kod || '-')}</span></td>
              <td>${row.veli_portal_anahtari ? `<button class="mini-btn" type="button" data-institution-portal-copy-row="${escapeHtml(row.id)}">Linki Kopyala</button>` : '-'}</td>
              <td><span class="status-pill">${Object.values(row.moduller || {}).filter(Boolean).length}/${moduleBoxes.length}</span></td>
              <td>${escapeHtml(row.kullanici_sayisi || 0)}</td>
              <td>${row.kurucu_id ? `<strong>${escapeHtml(`${row.kurucu_ad || ''} ${row.kurucu_soyad || ''}`.trim())}</strong><br><small>${escapeHtml(row.kurucu_eposta || '')}</small><br><span class="status-pill ${Number(row.kurucu_aktif) === 1 ? 'is-success' : 'is-danger'}">${Number(row.kurucu_aktif) === 1 ? 'Aktif' : 'Pasif'}</span>` : '<span class="status-pill is-danger">Yok</span>'}</td>
              <td>${row.mudur_id ? `<strong>${escapeHtml(`${row.mudur_ad || ''} ${row.mudur_soyad || ''}`.trim())}</strong><br><small>${escapeHtml(row.mudur_eposta || '')}</small>` : '<span class="status-pill is-danger">Yok</span>'}</td>
              <td>${escapeHtml(row.ogrenci_sayisi || 0)}</td>
              <td><span class="status-pill ${Number(row.aktif) === 1 ? 'is-success' : 'is-danger'}">${Number(row.aktif) === 1 ? 'Aktif' : 'Pasif'}</span></td>
              <td><button class="mini-btn" type="button" data-institution-edit="${escapeHtml(row.id)}">Duzenle</button></td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  async function load() {
    table.innerHTML = '<div class="empty-table">Yukleniyor...</div>';
    const result = await talyaAjax('kurum_listele');
    rows = result.veri || [];
    render();
  }

  newButton?.addEventListener('click', () => {
    fillForm();
    openDialog();
  });

  modulesToggle?.addEventListener('click', () => {
    const shouldCheck = !moduleBoxes.every((box) => box.checked);
    moduleBoxes.forEach((box) => {
      box.checked = shouldCheck;
    });
    syncModuleControls();
  });

  moduleBoxes.forEach((box) => box.addEventListener('change', () => {
    syncModuleControls();
  }));

  page.addEventListener('click', (event) => {
    if (event.target.closest('[data-institution-dialog-close]')) {
      closeDialog();
      return;
    }
    const rowPortalCopy = event.target.closest('[data-institution-portal-copy-row]');
    if (rowPortalCopy) {
      const row = rows.find((item) => String(item.id) === String(rowPortalCopy.dataset.institutionPortalCopyRow));
      if (row?.veli_portal_anahtari) {
        const url = `${window.location.origin}/veli-portal?k=${encodeURIComponent(row.veli_portal_anahtari)}`;
        navigator.clipboard.writeText(url).then(() => {
          message.textContent = `${row.ad} veli portalı bağlantısı kopyalandı.`;
        }).catch(() => {
          window.prompt('Bağlantıyı kopyalayın:', url);
        });
      }
      return;
    }
    const edit = event.target.closest('[data-institution-edit]');
    if (!edit) {
      return;
    }
    const row = rows.find((item) => String(item.id) === String(edit.dataset.institutionEdit));
    fillForm(row || null);
    openDialog();
  });

  portalCopy?.addEventListener('click', () => {
    const url = portalUrl?.value || '';
    if (!url) {
      return;
    }
    navigator.clipboard.writeText(url).then(() => {
      portalMessage.textContent = 'Bağlantı kopyalandı.';
    }).catch(() => {
      portalUrl.focus();
      portalUrl.select();
      portalMessage.textContent = 'Bağlantı seçildi; kopyalamak için Ctrl/Cmd+C kullanın.';
    });
  });

  logoInput?.addEventListener('change', () => {
    const file = logoInput.files?.[0];
    if (!file || !logoPreview) {
      return;
    }
    const objectUrl = URL.createObjectURL(file);
    logoPreview.innerHTML = `<img src="${objectUrl}" alt="Seçilen logo">`;
    logoPreview.querySelector('img')?.addEventListener('load', () => URL.revokeObjectURL(objectUrl), { once: true });
    if (logoMessage) {
      logoMessage.textContent = 'Yüklemek için Logoyu Yükle butonuna basın.';
    }
  });

  logoUpload?.addEventListener('click', async () => {
    const kurumId = Number(form.elements.id.value || 0);
    const file = logoInput?.files?.[0];
    if (!kurumId || !file) {
      logoMessage.textContent = 'Önce bir logo dosyası seçin.';
      return;
    }

    const payload = new FormData();
    payload.append('csrf', window.talyaCsrfToken || '');
    payload.append('kurum_id', String(kurumId));
    payload.append('logo', file);
    logoUpload.disabled = true;
    logoMessage.textContent = 'Logo yükleniyor...';
    try {
      const response = await fetch('/panel/sistem/kurumlar/logo', {
        method: 'POST',
        credentials: 'same-origin',
        body: payload
      });
      const result = await response.json();
      if (!response.ok || !result.basari) {
        throw new Error(result.mesaj || 'Logo yüklenemedi.');
      }
      const row = rows.find((item) => String(item.id) === String(kurumId));
      if (row) {
        row.logo_yolu = result.veri.logo_yolu;
      }
      logoPreview.innerHTML = `<img src="${escapeHtml(result.veri.logo_yolu)}?v=${Date.now()}" alt="Kurum logosu">`;
      logoInput.value = '';
      logoMessage.textContent = result.mesaj;
      render();
    } catch (error) {
      logoMessage.textContent = error.message;
    } finally {
      logoUpload.disabled = false;
    }
  });

  form.addEventListener('input', (event) => {
    if (event.target.name === 'kod') {
      event.target.value = event.target.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '');
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    formMessage.textContent = 'Kaydediliyor...';
    try {
      const values = formValues(form);
      values.moduller = moduleBoxes.filter((box) => box.checked).map((box) => box.value);
      values.sayfalar = pageBoxes.filter((box) => box.checked).map((box) => box.value);
      const result = await talyaAjax('kurum_kaydet', values);
      message.textContent = result.mesaj;
      closeDialog();
      await load();
    } catch (error) {
      formMessage.textContent = error.message;
    }
  });

  load().catch((error) => {
    table.innerHTML = `<div class="empty-table">${escapeHtml(error.message)}</div>`;
  });
})();
