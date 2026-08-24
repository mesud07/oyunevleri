(function () {
  const page = document.querySelector('[data-kasa-page]');
  if (!page) {
    return;
  }

  const table = page.querySelector('[data-cashbox-table]');
  const summary = page.querySelector('[data-cashbox-summary]');
  const historyTable = page.querySelector('[data-cashbox-history]');
  const dialog = page.querySelector('#kasa-dialog');
  const form = page.querySelector('[data-cashbox-form]');
  const movementDialog = page.querySelector('#kasa-hareket-dialog');
  const movementForm = page.querySelector('[data-cashbox-movement-form]');
  const title = page.querySelector('[data-cashbox-form-title]');
  let rows = [];
  let summaryRows = [];
  let historyRows = [];

  function money(value, currency) {
    return `${Number(value || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency || 'TRY'}`;
  }

  function typeLabel(value) {
    const labels = { nakit: 'Nakit', banka: 'Banka Hesabi', altin: 'Altin', diger: 'Diger' };
    return labels[value] || value;
  }

  function statusLabel(value) {
    return String(value) === '1' ? 'Aktif' : 'Pasif';
  }

  function openDialog(target = dialog) {
    if (target && typeof target.showModal === 'function') {
      target.showModal();
    } else if (target) {
      target.setAttribute('open', 'open');
    }
  }

  function closeDialog(target = dialog) {
    if (target && typeof target.close === 'function') {
      target.close();
    } else if (target) {
      target.removeAttribute('open');
    }
  }

  function resetForm() {
    form.reset();
    form.elements.id.value = '';
    form.elements.para_birimi.value = 'TRY';
    form.elements.acilis_bakiyesi.value = '0';
    if (title) {
      title.textContent = 'Kasa Ekle';
    }
    const message = form.querySelector('[data-form-message]');
    if (message) {
      message.textContent = '';
    }
  }

  function fillForm(row) {
    resetForm();
    form.elements.id.value = row.id || '';
    form.elements.ad.value = row.ad || '';
    form.elements.tur.value = row.tur || 'nakit';
    form.elements.para_birimi.value = row.para_birimi || 'TRY';
    form.elements.acilis_bakiyesi.value = Number(row.acilis_bakiyesi || 0);
    form.elements.aktif.value = String(row.aktif ?? '1');
    form.elements.aciklama.value = row.aciklama || '';
    if (title) {
      title.textContent = 'Kasa Duzenle';
    }
    openDialog();
  }

  function renderSummary() {
    if (!summary) {
      return;
    }
    if (!summaryRows.length) {
      summary.innerHTML = '<article><span>Toplam Bakiye</span><strong>0,00 TRY</strong></article>';
      return;
    }

    summary.innerHTML = summaryRows.map((row) => `
      <article>
        <span>Toplam Bakiye (${escapeHtml(row.para_birimi)})</span>
        <strong>${escapeHtml(money(row.bakiye, row.para_birimi))}</strong>
      </article>
    `).join('');
  }

  function render() {
    if (!rows.length) {
      table.innerHTML = '<div class="empty-table">Kasa bulunamadi.</div>';
      return;
    }

    table.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>Kasa</th>
            <th>Tur</th>
            <th>Acilis</th>
            <th>Tahsilat</th>
            <th>Manuel Giris</th>
            <th>Manuel Cikis</th>
            <th>Gider</th>
            <th>Bakiye</th>
            <th>Durum</th>
            <th>Islem</th>
          </tr>
        </thead>
        <tbody>
          ${rows.map((row) => `
            <tr>
              <td><strong>${escapeHtml(row.ad)}</strong><br><small>${escapeHtml(row.aciklama || '')}</small></td>
              <td>${escapeHtml(typeLabel(row.tur))}</td>
              <td>${escapeHtml(money(row.acilis_bakiyesi, row.para_birimi))}</td>
              <td>${escapeHtml(money(row.tahsilat, row.para_birimi))}</td>
              <td>${escapeHtml(money(row.manuel_giris, row.para_birimi))}</td>
              <td>${escapeHtml(money(row.manuel_cikis, row.para_birimi))}</td>
              <td>${escapeHtml(money(row.gider, row.para_birimi))}</td>
              <td><strong>${escapeHtml(money(row.bakiye, row.para_birimi))}</strong></td>
              <td><span class="status-pill ${String(row.aktif) === '1' ? 'is-success' : 'is-danger'}">${escapeHtml(statusLabel(row.aktif))}</span></td>
              <td>
                <div class="row-actions">
                  <button type="button" data-cashbox-edit="${escapeHtml(row.id)}">Duzenle</button>
                  <button type="button" data-cashbox-delete="${escapeHtml(row.id)}">Sil</button>
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  function renderHistory() {
    if (!historyTable) {
      return;
    }
    if (!historyRows.length) {
      historyTable.innerHTML = '<div class="empty-table">Kasa hareketi bulunamadi.</div>';
      return;
    }

    historyTable.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>Tarih</th>
            <th>Kasa</th>
            <th>Kaynak</th>
            <th>Islem</th>
            <th>Tutar</th>
            <th>Aciklama</th>
          </tr>
        </thead>
        <tbody>
          ${historyRows.map((row) => `
            <tr>
              <td>${escapeHtml(row.tarih)}</td>
              <td>${escapeHtml(row.kasa)}</td>
              <td>${escapeHtml(row.kaynak)}</td>
              <td><span class="status-pill ${row.tur === 'giris' ? 'is-success' : 'is-danger'}">${escapeHtml(row.tur === 'giris' ? 'Giris' : 'Cikis')}</span></td>
              <td><strong>${escapeHtml(money(row.tutar, row.para_birimi))}</strong></td>
              <td>${escapeHtml(row.aciklama || '-')}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  function syncCashboxSelects() {
    const selects = page.querySelectorAll('[data-cashbox-select]');
    selects.forEach((select) => {
      const current = select.value;
      select.innerHTML = `
        <option value="">Kasa secin</option>
        ${rows.filter((row) => String(row.aktif) === '1').map((row) => `
          <option value="${escapeHtml(row.id)}">${escapeHtml(row.ad)} - ${escapeHtml(row.para_birimi)}</option>
        `).join('')}
      `;
      select.value = current;
    });
  }

  async function load() {
    table.innerHTML = '<div class="empty-table">Yukleniyor...</div>';
    try {
      const result = await talyaAjax('kasa_listele');
      const payload = result.veri || {};
      rows = Array.isArray(payload) ? payload : (payload.kasalar || []);
      summaryRows = Array.isArray(payload) ? [] : (payload.ozet || []);
      historyRows = Array.isArray(payload) ? [] : (payload.hareketler || []);
      syncCashboxSelects();
      renderSummary();
      render();
      renderHistory();
    } catch (error) {
      table.innerHTML = `<div class="empty-table">${escapeHtml(error.message)}</div>`;
    }
  }

  page.addEventListener('click', async (event) => {
    const add = event.target.closest('[data-open-dialog="#kasa-dialog"]');
    if (add) {
      resetForm();
      return;
    }

    const movementAdd = event.target.closest('[data-open-dialog="#kasa-hareket-dialog"]');
    if (movementAdd && movementForm) {
      movementForm.reset();
      movementForm.elements.tarih.value = new Date().toISOString().slice(0, 10);
      const message = movementForm.querySelector('[data-form-message]');
      if (message) {
        message.textContent = '';
      }
      syncCashboxSelects();
      return;
    }

    const edit = event.target.closest('[data-cashbox-edit]');
    if (edit) {
      const row = rows.find((item) => String(item.id) === String(edit.dataset.cashboxEdit));
      if (row) {
        fillForm(row);
      }
      return;
    }

    const del = event.target.closest('[data-cashbox-delete]');
    if (del) {
      if (!window.confirm('Bu kasa silinsin mi? Bagli hareket varsa pasife alinir.')) {
        return;
      }
      await talyaAjax('kasa_sil', { id: del.dataset.cashboxDelete });
      await load();
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (form.dataset.submitting === '1') {
      return;
    }

    const message = form.querySelector('[data-form-message]');
    const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
    form.dataset.submitting = '1';
    submitButtons.forEach((button) => {
      button.disabled = true;
    });
    if (message) {
      message.textContent = 'Kaydediliyor...';
    }

    try {
      const values = formValues(form);
      const islem = values.id ? 'kasa_guncelle' : 'kasa_ekle';
      const result = await talyaAjax(islem, values);
      if (message) {
        message.textContent = result.mesaj;
      }
      closeDialog(dialog);
      await load();
    } catch (error) {
      if (message) {
        message.textContent = error.message;
      }
    } finally {
      form.dataset.submitting = '0';
      submitButtons.forEach((button) => {
        button.disabled = false;
      });
    }
  });

  movementForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (movementForm.dataset.submitting === '1') {
      return;
    }

    const message = movementForm.querySelector('[data-form-message]');
    const submitButtons = Array.from(movementForm.querySelectorAll('button[type="submit"], input[type="submit"]'));
    movementForm.dataset.submitting = '1';
    submitButtons.forEach((button) => {
      button.disabled = true;
    });
    if (message) {
      message.textContent = 'Kaydediliyor...';
    }

    try {
      const result = await talyaAjax('kasa_hareket_ekle', formValues(movementForm));
      if (message) {
        message.textContent = result.mesaj;
      }
      closeDialog(movementDialog);
      await load();
    } catch (error) {
      if (message) {
        message.textContent = error.message;
      }
    } finally {
      movementForm.dataset.submitting = '0';
      submitButtons.forEach((button) => {
        button.disabled = false;
      });
    }
  });

  load();
})();
