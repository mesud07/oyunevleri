(() => {
  const page = document.querySelector('[data-sms-page]');
  if (!page || typeof talyaAjax !== 'function') {
    return;
  }

  const templateMessages = {};
  page.querySelectorAll('[data-sms-template-select] option[data-message]').forEach((option) => {
    templateMessages[option.value] = option.dataset.message || '';
  });

  function segmentCount(message) {
    const length = [...String(message || '')].length;
    if (length === 0) return 0;
    return length <= 155 ? 1 : Math.ceil(length / 149);
  }

  function updateCounter(textarea, target) {
    if (!textarea || !target) return;
    const length = [...textarea.value].length;
    target.textContent = `${length} karakter / ${segmentCount(textarea.value)} parca`;
  }

  const singleForm = page.querySelector('[data-sms-single-form]');
  const bulkForm = page.querySelector('[data-sms-bulk-form]');
  const reminderForm = page.querySelector('[data-sms-reminder-form]');
  const connectionForm = page.querySelector('[data-sms-connection-form]');
  const connectionMessage = page.querySelector('[data-sms-connection-message]');
  const connectionStatus = page.querySelector('[data-sms-connection-status]');
  const singleMessage = singleForm?.querySelector('[name="mesaj"]');
  const bulkMessage = bulkForm?.querySelector('[name="mesaj"]');
  const singleCounter = page.querySelector('[data-sms-counter]');
  const bulkCounter = page.querySelector('[data-sms-bulk-counter]');

  singleMessage?.addEventListener('input', () => updateCounter(singleMessage, singleCounter));
  bulkMessage?.addEventListener('input', () => updateCounter(bulkMessage, bulkCounter));
  page.querySelector('[data-sms-template-select]')?.addEventListener('change', (event) => {
    const message = templateMessages[event.target.value] || '';
    if (singleMessage && message) {
      singleMessage.value = message;
      updateCounter(singleMessage, singleCounter);
    }
  });

  function connectionValues() {
    const values = formValues(connectionForm);
    values.sms_enabled = connectionForm?.querySelector('[name="sms_enabled"]')?.checked ? '1' : '0';
    values.sms_test_mode = connectionForm?.querySelector('[name="sms_test_mode"]')?.checked ? '1' : '0';
    return values;
  }

  function updateConnectionStatus(result) {
    if (!connectionStatus || !result) return;
    const statusTarget = connectionStatus.querySelector('[data-sms-last-status]');
    const atTarget = connectionStatus.querySelector('[data-sms-last-at]');
    const messageTarget = connectionStatus.querySelector('[data-sms-last-message]');
    if (statusTarget) statusTarget.textContent = result.son_test_durumu || '-';
    if (atTarget) atTarget.textContent = result.son_test_tarihi || '-';
    if (messageTarget) messageTarget.textContent = result.son_test_mesaji || '-';
  }

  async function verifyConnection(button) {
    if (!connectionForm) return;
    button.disabled = true;
    if (connectionMessage) connectionMessage.textContent = 'NetGSM baglantisi dogrulaniyor...';
    try {
      const response = await talyaAjax('sms_baglanti_dogrula', connectionValues());
      if (connectionMessage) {
        const headers = response.veri?.basliklar?.length ? ` Basliklar: ${response.veri.basliklar.join(', ')}` : '';
        connectionMessage.textContent = `${response.mesaj || 'Baglanti dogrulandi.'}${headers}`;
      }
      updateConnectionStatus(response.veri?.durum);
    } catch (error) {
      if (connectionMessage) connectionMessage.textContent = error.message;
      alert(error.message);
    } finally {
      button.disabled = false;
    }
  }

  singleForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = singleForm.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
      const values = formValues(singleForm);
      await talyaAjax('sms_tekli_gonder', values);
      singleForm.reset();
      updateCounter(singleMessage, singleCounter);
      await loadSms();
    } catch (error) {
      alert(error.message);
    } finally {
      button.disabled = false;
    }
  });

  bulkForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = bulkForm.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
      const values = formValues(bulkForm);
      await talyaAjax('sms_toplu_gonder', values);
      bulkForm.reset();
      updateCounter(bulkMessage, bulkCounter);
      await loadSms();
    } catch (error) {
      alert(error.message);
    } finally {
      button.disabled = false;
    }
  });

  reminderForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = reminderForm.querySelector('button[type="submit"]');
    const message = page.querySelector('[data-sms-reminder-message]');
    button.disabled = true;
    if (message) message.textContent = 'Kaydediliyor...';
    try {
      const values = formValues(reminderForm);
      values.appointment_reminder_enabled = reminderForm.querySelector('[name="appointment_reminder_enabled"]')?.checked ? '1' : '0';
      const response = await talyaAjax('sms_hatirlatma_ayarlari_kaydet', values);
      if (message) message.textContent = response.mesaj || 'Hatirlatma ayarlari kaydedildi.';
    } catch (error) {
      if (message) message.textContent = error.message;
      alert(error.message);
    } finally {
      button.disabled = false;
    }
  });

  connectionForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = connectionForm.querySelector('button[type="submit"]');
    button.disabled = true;
    if (connectionMessage) connectionMessage.textContent = 'Kaydediliyor...';
    try {
      const response = await talyaAjax('sms_baglanti_ayarlari_kaydet', connectionValues());
      if (connectionMessage) connectionMessage.textContent = response.mesaj || 'NetGSM ayarlari kaydedildi.';
    } catch (error) {
      if (connectionMessage) connectionMessage.textContent = error.message;
      alert(error.message);
    } finally {
      button.disabled = false;
    }
  });

  page.querySelector('[data-netgsm-verify]')?.addEventListener('click', (event) => {
    verifyConnection(event.currentTarget);
  });

  page.querySelector('[data-netgsm-verify-form]')?.addEventListener('click', (event) => {
    verifyConnection(event.currentTarget);
  });

  const table = page.querySelector('[data-sms-table]');
  const search = page.querySelector('[data-sms-search]');
  const status = page.querySelector('[data-sms-status-filter]');
  let smsCurrentPage = 1;
  let smsCurrentLimit = 20;

  async function loadSms(pageNumber = smsCurrentPage) {
    smsCurrentPage = pageNumber;
    const sonuc = await talyaAjax('sms_kayitlarini_listele', {
      q: search?.value || '',
      durum: status?.value || '',
      sayfa: smsCurrentPage,
      limit: smsCurrentLimit
    });
    renderSmsTable(sonuc.veri?.kayitlar || [], sonuc.veri?.sayfalama || {});
  }

  function renderSmsPagination(paging) {
    const totalPage = Number(paging?.toplam_sayfa || 1);
    const pageNumber = Number(paging?.sayfa || 1);
    const currentLimit = Number(paging?.limit || 20);

    const pages = [];
    const start = Math.max(1, pageNumber - 2);
    const end = Math.min(totalPage, pageNumber + 2);
    for (let i = start; i <= end; i += 1) {
      pages.push(i);
    }

    return `
      <div class="table-pagination">
        <div class="table-pagination-nav">
          <button class="mini-btn" type="button" data-sms-page="1" ${pageNumber <= 1 ? 'disabled' : ''}>&laquo;</button>
          <button class="mini-btn" type="button" data-sms-page="${Math.max(1, pageNumber - 1)}" ${pageNumber <= 1 ? 'disabled' : ''}>&lsaquo;</button>
          ${totalPage <= 1 ? '<button class="mini-btn is-active" type="button" data-sms-page="1">1</button>' : ''}
          ${start > 1 ? '<span class="table-pagination-gap">...</span>' : ''}
          ${pages.map((page) => `<button class="mini-btn ${page === pageNumber ? 'is-active' : ''}" type="button" data-sms-page="${page}">${page}</button>`).join('')}
          ${end < totalPage ? '<span class="table-pagination-gap">...</span>' : ''}
          <button class="mini-btn" type="button" data-sms-page="${Math.min(totalPage, pageNumber + 1)}" ${pageNumber >= totalPage ? 'disabled' : ''}>&rsaquo;</button>
          <button class="mini-btn" type="button" data-sms-page="${Math.max(1, totalPage)}" ${pageNumber >= totalPage ? 'disabled' : ''}>&raquo;</button>
        </div>
        <label class="table-pagination-size">
          <span>Sayfa başına satır</span>
          <select data-sms-limit>
            <option value="20" ${currentLimit === 20 ? 'selected' : ''}>20</option>
            <option value="50" ${currentLimit === 50 ? 'selected' : ''}>50</option>
            <option value="100" ${currentLimit === 100 ? 'selected' : ''}>100</option>
          </select>
        </label>
      </div>
    `;
  }

  function renderSmsTable(rows, paging = {}) {
    if (!table) return;
    if (!rows.length) {
      table.innerHTML = '<div class="empty-state">SMS kaydi bulunamadi.</div>';
      return;
    }
    const start = ((Number(paging.sayfa || 1) - 1) * Number(paging.limit || 20)) + 1;
    table.innerHTML = `
      <table>
        <thead><tr><th>#</th><th>Tarih</th><th>Alici</th><th>Telefon</th><th>Mesaj</th><th>Durum</th><th>Islem</th></tr></thead>
        <tbody>
          ${rows.map((row, index) => `
            <tr>
              <td>${start + index}</td>
              <td>${escapeHtml(row.olusturulma_tarihi || '')}</td>
              <td>${escapeHtml(row.ogrenci || row.veli || row.alici_tipi || '-')}</td>
              <td>${escapeHtml(row.telefon || '')}</td>
              <td class="truncate-cell">${escapeHtml(row.mesaj || '')}</td>
              <td><span class="status-pill">${escapeHtml(row.durum || '')}</span></td>
              <td class="action-cell">
                <button class="mini-btn" type="button" data-sms-repeat="${row.id}">Tekrar</button>
                <button class="mini-btn danger" type="button" data-sms-cancel="${row.id}">Iptal</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
      ${renderSmsPagination(paging)}
    `;
  }

  let filterTimer = null;
  [search, status].forEach((element) => element?.addEventListener('input', () => {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => loadSms(1), 250);
  }));
  status?.addEventListener('change', () => loadSms(1));

  table?.addEventListener('click', async (event) => {
    const repeat = event.target.closest('[data-sms-repeat]');
    const cancel = event.target.closest('[data-sms-cancel]');
    const pageButton = event.target.closest('[data-sms-page]');
    if (pageButton && !pageButton.disabled) {
      loadSms(Number(pageButton.dataset.smsPage || 1)).catch((error) => alert(error.message));
      return;
    }
    if (!repeat && !cancel) return;
    try {
      if (repeat) {
        await talyaAjax('sms_tekrar_gonder', { id: repeat.dataset.smsRepeat });
      }
      if (cancel) {
        await talyaAjax('sms_iptal_et', { id: cancel.dataset.smsCancel });
      }
      await loadSms();
    } catch (error) {
      alert(error.message);
    }
  });

  table?.addEventListener('change', (event) => {
    const limitSelect = event.target.closest('[data-sms-limit]');
    if (!limitSelect) return;
    smsCurrentLimit = Number(limitSelect.value || 20);
    loadSms(1).catch((error) => alert(error.message));
  });

  const templateDialog = document.querySelector('#sms-template-dialog');
  const templateForm = templateDialog?.querySelector('[data-sms-template-form]');
  const templateTable = page.querySelector('[data-sms-template-table]');
  const templateTitle = templateDialog?.querySelector('[data-sms-template-title]');
  const templateSubmit = templateDialog?.querySelector('[data-sms-template-submit]');
  const templateCharCount = templateDialog?.querySelector('[data-template-char-count]');
  const templateMessage = templateForm?.querySelector('[name="mesaj"]');

  function openTemplateDialog(row = null) {
    if (!templateDialog || !templateForm) return;
    templateForm.reset();
    const keyInput = templateForm.querySelector('[name="anahtar"]');
    if (keyInput) keyInput.readOnly = Boolean(row);
    templateForm.querySelector('[name="aktif"]').value = '0';
    templateForm.querySelector('[name="otomatik_gonderim"]').value = '0';
    if (row) {
      Object.entries(row).forEach(([key, value]) => {
        const input = templateForm.querySelector(`[name="${key}"]`);
        if (input) input.value = value ?? '';
      });
      templateTitle.textContent = 'SMS Sablonu Duzenle';
      templateSubmit.textContent = 'SMS Sablonunu Guncelle';
    } else {
      templateTitle.textContent = 'SMS Sablonu Ekle';
      templateSubmit.textContent = 'SMS Sablonunu Ekle';
    }
    updateTemplateCounter();
    templateDialog.showModal();
  }

  function updateTemplateCounter() {
    if (!templateCharCount || !templateMessage) return;
    templateCharCount.textContent = `Karakter Sayisi: ${[...templateMessage.value].length}`;
  }

  templateMessage?.addEventListener('input', updateTemplateCounter);
  page.querySelector('[data-open-sms-template]')?.addEventListener('click', () => openTemplateDialog());
  templateDialog?.querySelectorAll('[data-close-dialog]').forEach((button) => {
    button.addEventListener('click', () => templateDialog.close());
  });

  async function loadTemplates() {
    const sonuc = await talyaAjax('sms_sablonlarini_listele');
    renderTemplates(sonuc.veri || []);
  }

  function renderTemplates(rows) {
    if (!templateTable) return;
    templateTable.innerHTML = `
      <table>
        <thead><tr><th>#</th><th>Sablon Adi</th><th>Sablon Icerik</th><th>Durum</th><th>Islem</th></tr></thead>
        <tbody>
          ${rows.map((row, index) => `
            <tr>
              <td>${index + 1}</td>
              <td>${escapeHtml(row.baslik)}</td>
              <td>${escapeHtml(row.mesaj)}</td>
              <td>${templateStatus(row)}</td>
              <td class="action-cell">
                <button class="mini-btn" type="button" data-template-edit='${escapeHtml(JSON.stringify(row))}'>Duzenle</button>
                ${row.onay_durumu !== 'kullanilabilir' ? `<button class="mini-btn" type="button" data-template-approve="${escapeHtml(row.anahtar)}">Onayla</button>` : ''}
                ${row.onay_durumu !== 'reddedildi' ? `<button class="mini-btn danger" type="button" data-template-reject="${escapeHtml(row.anahtar)}">Reddet</button>` : ''}
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  function templateStatus(row) {
    const status = row.onay_durumu || (Number(row.aktif) === 1 ? 'kullanilabilir' : 'incelemede');
    const labels = {
      kullanilabilir: 'Kullanilabilir',
      incelemede: 'Incelemede',
      reddedildi: 'Reddedildi'
    };
    return `<span class="sms-template-status is-${escapeHtml(status)}">${escapeHtml(labels[status] || status)}</span>`;
  }

  templateTable?.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-template-edit]');
    const approve = event.target.closest('[data-template-approve]');
    const reject = event.target.closest('[data-template-reject]');
    try {
      if (button) {
        openTemplateDialog(JSON.parse(button.dataset.templateEdit));
        return;
      }
      if (approve) {
        await talyaAjax('sms_sablon_onayla', { anahtar: approve.dataset.templateApprove });
        await loadTemplates();
        return;
      }
      if (reject) {
        await talyaAjax('sms_sablon_reddet', { anahtar: reject.dataset.templateReject });
        await loadTemplates();
      }
    } catch (error) {
      alert(error.message);
    }
  });

  templateForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const templateKey = templateForm.querySelector('[name="anahtar"]')?.value || '';
    const message = templateForm.querySelector('[name="mesaj"]')?.value || '';
    if (templateKey !== 'manuel_sms' && !message.includes('{kurum_adi}') && !message.includes('{klinik_adi}')) {
      alert('SMS iceriginde {kurum_adi} etiketi bulunmalidir.');
      return;
    }
    const button = templateForm.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
      await talyaAjax('sms_sablon_kaydet', formValues(templateForm));
      await loadTemplates();
      templateDialog?.close();
    } catch (error) {
      alert(error.message);
    } finally {
      button.disabled = false;
    }
  });

  page.querySelector('[data-netgsm-headers]')?.addEventListener('click', async () => {
    try {
      const sonuc = await talyaAjax('sms_netgsm_basliklari_listele');
      const basliklar = sonuc.veri?.basliklar || [];
      alert(basliklar.length ? `NetGSM basliklari: ${basliklar.join(', ')}` : 'NetGSM hesabinda onayli baslik bulunamadi.');
    } catch (error) {
      alert(error.message);
    }
  });

  updateCounter(singleMessage, singleCounter);
  updateCounter(bulkMessage, bulkCounter);
  loadSms().catch(() => {});
  loadTemplates().catch(() => {});
})();

(() => {
  const page = document.querySelector('[data-sms-report-page]');
  if (!page || typeof talyaAjax !== 'function') {
    return;
  }

  const form = page.querySelector('[data-sms-report-filter]');
  const table = page.querySelector('[data-sms-report-table]');
  const summary = page.querySelector('[data-sms-report-summary]');
  const pagination = page.querySelector('[data-sms-report-pagination]');
  let currentPage = 1;
  let filterTimer = null;

  const statusLabels = {
    bekliyor: 'Bekliyor',
    isleniyor: 'Isleniyor',
    gonderildi: 'Servise Gonderildi',
    teslim_edildi: 'SMS iletildi',
    basarisiz: 'Basarisiz',
    tekrar_bekliyor: 'Tekrar bekliyor',
    iptal: 'Iptal'
  };

  async function loadReports(pageNumber = 1) {
    currentPage = pageNumber;
    const values = formValues(form);
    values.sayfa = currentPage;
    values.limit = 20;
    if (table) table.innerHTML = '<div class="empty-state">SMS raporlari yukleniyor...</div>';
    const response = await talyaAjax('sms_raporlari_listele', values);
    renderReports(response.veri || {});
  }

  function renderReports(data) {
    renderSummary(data.ozet || {});
    renderTable(data.kayitlar || [], data.sayfalama || {});
    renderPagination(data.sayfalama || {});
  }

  function renderSummary(data) {
    if (!summary) return;
    const items = [
      ['Toplam', data.toplam || 0],
      ['Servise Gonderildi', data.gonderildi || 0],
      ['SMS iletildi', data.teslim_edildi || 0],
      ['Kuyrukta', data.kuyruk || 0],
      ['Basarisiz', data.basarisiz || 0]
    ];
    summary.innerHTML = items.map(([label, value]) => `
      <article>
        <span>${escapeHtml(label)}</span>
        <strong>${escapeHtml(value)}</strong>
      </article>
    `).join('');
  }

  function renderTable(rows, paging) {
    if (!table) return;
    if (!rows.length) {
      table.innerHTML = '<div class="empty-state">SMS raporu bulunamadi.</div>';
      return;
    }
    const start = ((Number(paging.sayfa || 1) - 1) * Number(paging.limit || 20)) + 1;
    table.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Ad Soyad</th>
            <th>Icerik</th>
            <th>Tarih</th>
            <th>Durum</th>
            <th>Kontrol</th>
          </tr>
        </thead>
        <tbody>
          ${rows.map((row, index) => `
            <tr>
              <td>${start + index}</td>
              <td class="sms-report-person">
                <strong>${escapeHtml(row.ogrenci || row.veli || row.alici_tipi || '-')}</strong>
                <small>${escapeHtml(row.telefon || '')}</small>
              </td>
              <td class="sms-report-message">${escapeHtml(row.mesaj || '')}</td>
              <td>${formatDateTime(row.gonderilme_tarihi || row.olusturulma_tarihi || '')}</td>
              <td>
                <span class="sms-report-status is-${escapeHtml(row.durum || 'bekliyor')}">
                  ${escapeHtml(statusLabels[row.durum] || row.durum || '-')}
                </span>
                ${row.hata_mesaji ? `<small class="sms-report-error">${escapeHtml(row.hata_mesaji)}</small>` : ''}
              </td>
              <td>
                ${row.provider_islem_no ? `<button class="mini-btn" type="button" data-sms-report-check="${row.id}">Kontrol Et</button>` : '<span class="muted">-</span>'}
                ${row.durum === 'teslim_edildi' ? '<small class="sms-report-delivered">SMS iletildi.</small>' : ''}
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  function renderPagination(paging) {
    if (!pagination) return;
    const totalPage = Number(paging.toplam_sayfa || 1);
    const pageNumber = Number(paging.sayfa || 1);
    if (totalPage <= 1) {
      pagination.innerHTML = '';
      return;
    }
    const pages = [];
    const start = Math.max(1, pageNumber - 2);
    const end = Math.min(totalPage, pageNumber + 2);
    for (let i = start; i <= end; i++) pages.push(i);
    pagination.innerHTML = `
      <button class="mini-btn" type="button" data-report-page="${Math.max(1, pageNumber - 1)}" ${pageNumber <= 1 ? 'disabled' : ''}>Onceki</button>
      ${start > 1 ? '<span>...</span>' : ''}
      ${pages.map((p) => `<button class="mini-btn ${p === pageNumber ? 'is-active' : ''}" type="button" data-report-page="${p}">${p}</button>`).join('')}
      ${end < totalPage ? '<span>...</span>' : ''}
      <button class="mini-btn" type="button" data-report-page="${Math.min(totalPage, pageNumber + 1)}" ${pageNumber >= totalPage ? 'disabled' : ''}>Sonraki</button>
    `;
  }

  function formatDateTime(value) {
    if (!value) return '-';
    const text = String(value).replace('T', ' ');
    const [date, time = ''] = text.split(' ');
    const parts = date.split('-');
    if (parts.length !== 3) return escapeHtml(value);
    return `${parts[2]}/${parts[1]}/${parts[0]}${time ? `<br>${escapeHtml(time.slice(0, 5))}` : ''}`;
  }

  form?.addEventListener('submit', (event) => {
    event.preventDefault();
    loadReports(1).catch((error) => alert(error.message));
  });

  form?.addEventListener('input', () => {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => loadReports(1).catch(() => {}), 300);
  });

  form?.addEventListener('change', () => {
    clearTimeout(filterTimer);
    loadReports(1).catch((error) => alert(error.message));
  });

  pagination?.addEventListener('click', (event) => {
    const button = event.target.closest('[data-report-page]');
    if (!button || button.disabled) return;
    loadReports(Number(button.dataset.reportPage || 1)).catch((error) => alert(error.message));
  });

  table?.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-sms-report-check]');
    if (!button) return;
    button.disabled = true;
    button.textContent = 'Kontrol ediliyor';
    try {
      await talyaAjax('sms_rapor_kontrol_et', { id: button.dataset.smsReportCheck });
      await loadReports(currentPage);
    } catch (error) {
      alert(error.message);
      button.disabled = false;
      button.textContent = 'Kontrol Et';
    }
  });

  loadReports(1).catch(() => {});
})();

(() => {
  const openButton = document.querySelector('[data-open-profile-sms-reports]');
  const dialog = document.querySelector('[data-profile-sms-dialog]');
  const table = dialog?.querySelector('[data-profile-sms-table]');
  if (!openButton || !dialog || !table || typeof talyaAjax !== 'function') {
    return;
  }

  const statusLabels = {
    bekliyor: 'Bekliyor',
    isleniyor: 'Isleniyor',
    gonderildi: 'Servise Gonderildi',
    teslim_edildi: 'SMS iletildi',
    basarisiz: 'Basarisiz',
    tekrar_bekliyor: 'Tekrar bekliyor',
    iptal: 'Iptal'
  };

  function formatDateTime(value) {
    if (!value) return '-';
    const text = String(value).replace('T', ' ');
    const [date, time = ''] = text.split(' ');
    const parts = date.split('-');
    if (parts.length !== 3) return escapeHtml(value);
    return `${parts[2]}/${parts[1]}/${parts[0]}${time ? ` ${escapeHtml(time.slice(0, 5))}` : ''}`;
  }

  function render(rows) {
    if (!rows.length) {
      table.innerHTML = '<div class="empty-state">Bu ogrenci icin SMS kaydi bulunamadi.</div>';
      return;
    }

    table.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Tarih</th>
            <th>Telefon</th>
            <th>Tip</th>
            <th>Mesaj</th>
            <th>Durum</th>
          </tr>
        </thead>
        <tbody>
          ${rows.map((row, index) => `
            <tr>
              <td>${index + 1}</td>
              <td>${formatDateTime(row.gonderilme_tarihi || row.olusturulma_tarihi || '')}</td>
              <td>${escapeHtml(row.telefon || row.telefon_orijinal || '')}</td>
              <td>${escapeHtml(row.olay_tipi || '-')}</td>
              <td class="sms-report-message">${escapeHtml(row.mesaj || '')}</td>
              <td>
                <span class="sms-report-status is-${escapeHtml(row.durum || 'bekliyor')}">${escapeHtml(statusLabels[row.durum] || row.durum || '-')}</span>
                ${row.hata_mesaji ? `<small class="sms-report-error">${escapeHtml(row.hata_mesaji)}</small>` : ''}
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  async function loadReports() {
    table.innerHTML = '<div class="empty-state">SMS raporlari yukleniyor...</div>';
    const response = await talyaAjax('sms_ogrenci_raporlari', {
      ogrenci_id: openButton.dataset.studentId || ''
    });
    render(response.veri?.kayitlar || []);
  }

  openButton.addEventListener('click', () => {
    dialog.showModal();
    loadReports().catch((error) => {
      table.innerHTML = `<div class="empty-state">${escapeHtml(error.message)}</div>`;
    });
  });

  dialog.querySelectorAll('[data-profile-sms-close]').forEach((button) => {
    button.addEventListener('click', () => dialog.close());
  });
})();

(() => {
  const dialog = document.querySelector('[data-sms-compose-dialog]');
  const form = dialog?.querySelector('[data-sms-compose-form]');
  if (!dialog || !form || typeof talyaAjax !== 'function') {
    return;
  }

  const templateSelect = form.querySelector('[data-sms-compose-template]');
  const recipient = dialog.querySelector('[data-sms-compose-recipient]');
  const message = form.querySelector('[name="mesaj"]');
  const counter = dialog.querySelector('[data-sms-compose-counter]');
  const formMessage = dialog.querySelector('[data-sms-compose-message]');
  let templates = [];
  let context = {};

  function segmentCount(text) {
    const length = [...String(text || '')].length;
    if (length === 0) return 0;
    return length <= 155 ? 1 : Math.ceil(length / 149);
  }

  function updateCounter() {
    if (!counter || !message) return;
    const length = [...message.value].length;
    counter.textContent = `${length} karakter / ${segmentCount(message.value)} parca`;
  }

  function openDialog(target) {
    if (typeof target.showModal === 'function') {
      target.showModal();
      return;
    }
    target.setAttribute('open', 'open');
  }

  function closeDialog(target) {
    if (typeof target.close === 'function') {
      target.close();
      return;
    }
    target.removeAttribute('open');
  }

  function replaceTemplate(text) {
    const clinicName = dialog.getAttribute('data-clinic-name') || 'Oyun Evleri Yönetim Sistemi';
    const values = {
      veli_adi: context.parentName || 'Velimiz',
      ogrenci_adi: context.studentName || '',
      kurum_adi: clinicName,
      klinik_adi: clinicName,
      kurum_telefonu: '',
      paket_adi: context.packageName || '',
      hizmet_adi: context.packageName || '',
      tarih: context.date || '',
      saat: context.time || '',
      kalan_ders: context.remaining || '',
      telafi: context.makeup || '',
      yenileme: context.renewal || '',
      mesaj: ''
    };

    return String(text || '').replace(/\{([a-zA-Z0-9_]+)\}/g, (match, key) => (
      Object.prototype.hasOwnProperty.call(values, key) ? values[key] : match
    ));
  }

  async function loadTemplates() {
    if (templates.length) {
      return;
    }
    templateSelect.innerHTML = '<option value="">Sablon yukleniyor...</option>';
    const result = await talyaAjax('sms_sablon_secimleri');
    templates = result.veri || [];
    templateSelect.innerHTML = templates.length
      ? '<option value="">Sablon seciniz</option>' + templates.map((template) => (
        `<option value="${escapeHtml(template.anahtar)}">${escapeHtml(template.baslik || template.anahtar)}</option>`
      )).join('')
      : '<option value="">Aktif sablon yok</option>';
  }

  function applyTemplate() {
    const template = templates.find((item) => String(item.anahtar) === String(templateSelect.value));
    form.elements.sablon_anahtari.value = template?.anahtar || 'manuel_sms';
    message.value = replaceTemplate(template?.mesaj || '');
    updateCounter();
  }

  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-open-sms-compose]');
    if (!button) {
      return;
    }

    context = { ...button.dataset };
    form.reset();
    form.elements.ogrenci_id.value = context.studentId || '';
    form.elements.veli_id.value = context.parentId || '';
    form.elements.telefon.value = context.phone || '';
    form.elements.sablon_anahtari.value = 'manuel_sms';
    if (recipient) {
      recipient.textContent = `${context.studentName || 'Ogrenci'} / ${context.parentName || 'Veli'} / ${context.phone || 'telefon yok'}`;
    }
    if (formMessage) {
      formMessage.textContent = '';
    }
    message.value = '';
    updateCounter();
    openDialog(dialog);

    try {
      await loadTemplates();
    } catch (error) {
      if (formMessage) {
        formMessage.textContent = error.message;
      }
    }
  });

  templateSelect?.addEventListener('change', applyTemplate);
  message?.addEventListener('input', updateCounter);

  dialog.querySelectorAll('[data-sms-compose-close]').forEach((button) => {
    button.addEventListener('click', () => closeDialog(dialog));
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    if (formMessage) {
      formMessage.textContent = 'Gonderiliyor...';
    }
    try {
      const result = await talyaAjax('sms_ogrenciye_gonder', formValues(form));
      if (formMessage) {
        formMessage.textContent = result.mesaj || 'SMS gonderildi.';
      }
      closeDialog(dialog);
    } catch (error) {
      if (formMessage) {
        formMessage.textContent = error.message;
      }
    } finally {
      button.disabled = false;
    }
  });
})();
