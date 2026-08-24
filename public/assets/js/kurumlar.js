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
      founderFields.hidden = !founderRequired;
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
            <tr><th>#</th><th>Logo</th><th>Kurum</th><th>Kod</th><th>Veli Portalı</th><th>Kullanici</th><th>Kurucu</th><th>Ogrenci</th><th>Durum</th><th>Islem</th></tr>
        </thead>
        <tbody>
          ${rows.map((row, index) => `
            <tr>
              <td>${index + 1}</td>
              <td>${row.logo_yolu ? `<img class="institution-table-logo" src="${escapeHtml(row.logo_yolu)}" alt="">` : '<span class="muted">-</span>'}</td>
              <td><strong>${escapeHtml(row.ad || '-')}</strong><br><small>${escapeHtml(row.olusturulma_tarihi || '')}</small></td>
              <td><span class="status-pill">${escapeHtml(row.kod || '-')}</span></td>
              <td>${row.veli_portal_anahtari ? `<button class="mini-btn" type="button" data-institution-portal-copy-row="${escapeHtml(row.id)}">Linki Kopyala</button>` : '-'}</td>
              <td>${escapeHtml(row.kullanici_sayisi || 0)}</td>
              <td><span class="status-pill ${Number(row.kurucu_sayisi) > 0 ? 'is-success' : 'is-danger'}">${Number(row.kurucu_sayisi) > 0 ? 'Var' : 'Yok'}</span></td>
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
      const result = await talyaAjax('kurum_kaydet', formValues(form));
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
