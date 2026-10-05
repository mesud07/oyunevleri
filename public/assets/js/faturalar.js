(function () {
  const message = document.querySelector('[data-invoice-page-message]');
  const nesTest = document.querySelector('[data-nes-test]');
  const cancelDialog = document.querySelector('[data-invoice-cancel-dialog]');
  const cancelForm = document.querySelector('[data-invoice-cancel-form]');
  const bulkBoxes = Array.from(document.querySelectorAll('[data-invoice-select]'));
  const bulkSelectAll = document.querySelector('[data-invoice-select-all]');
  const bulkApprove = document.querySelector('[data-invoice-bulk-approve]');
  const bulkCancel = document.querySelector('[data-invoice-bulk-cancel]');
  const bulkDownload = document.querySelector('[data-invoice-bulk-download]');
  const bulkCount = document.querySelector('[data-invoice-selected-count]');
  const bulkCancelDialog = document.querySelector('[data-invoice-bulk-cancel-dialog]');
  const bulkCancelForm = document.querySelector('[data-invoice-bulk-cancel-form]');
  const bulkCancelCount = document.querySelector('[data-invoice-bulk-cancel-count]');
  const bulkDownloadForm = document.querySelector('[data-invoice-bulk-download-form]');
  const rowMenuToggles = Array.from(document.querySelectorAll('[data-invoice-row-menu-toggle]'));
  const rowMenus = Array.from(document.querySelectorAll('[data-invoice-row-menu]'));

  function setMessage(value) { if (message) message.textContent = value || ''; }
  function selectedBoxes() { return bulkBoxes.filter((box) => box.checked); }
  function eligibleBoxes(field) { return selectedBoxes().filter((box) => box.dataset[field] === '1'); }
  function selectedIds(field) { return eligibleBoxes(field).map((box) => Number(box.value)).filter((id) => id > 0); }
  function updateBulkControls() {
    const selected = selectedBoxes();
    if (bulkCount) bulkCount.textContent = `${selected.length} fatura seçildi`;
    if (bulkApprove) bulkApprove.disabled = eligibleBoxes('invoiceApprovable').length === 0;
    if (bulkCancel) bulkCancel.disabled = eligibleBoxes('invoiceCancelable').length === 0;
    if (bulkDownload) bulkDownload.disabled = eligibleBoxes('invoiceDownloadable').length === 0;
    if (bulkSelectAll) {
      bulkSelectAll.checked = bulkBoxes.length > 0 && selected.length === bulkBoxes.length;
      bulkSelectAll.indeterminate = selected.length > 0 && selected.length < bulkBoxes.length;
    }
  }

  function closeRowMenu(menu) {
    if (!menu) return;
    menu.hidden = true;
    menu.removeAttribute('data-open');
    menu.style.removeProperty('left');
    menu.style.removeProperty('top');
    const owner = menu._invoiceMenuOwner;
    if (owner && menu.parentElement !== owner) owner.appendChild(menu);
    const toggle = rowMenuToggles.find((item) => item.getAttribute('aria-controls') === menu.id);
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  }

  function closeAllRowMenus(except) {
    rowMenus.forEach((menu) => { if (menu !== except) closeRowMenu(menu); });
  }

  function openRowMenu(toggle, menu) {
    closeAllRowMenus(menu);
    menu._invoiceMenuOwner ||= menu.parentElement;
    document.body.appendChild(menu);
    menu.hidden = false;
    menu.setAttribute('data-open', '');
    const toggleRect = toggle.getBoundingClientRect();
    const menuRect = menu.getBoundingClientRect();
    const margin = 10;
    const left = Math.min(window.innerWidth - menuRect.width - margin, Math.max(margin, toggleRect.right - menuRect.width));
    const fitsBelow = toggleRect.bottom + menuRect.height + margin <= window.innerHeight;
    const top = fitsBelow ? toggleRect.bottom + 6 : Math.max(margin, toggleRect.top - menuRect.height - 6);
    menu.style.left = `${left}px`;
    menu.style.top = `${top}px`;
    toggle.setAttribute('aria-expanded', 'true');
    menu.querySelector('a, button')?.focus({ preventScroll: true });
  }

  rowMenuToggles.forEach((toggle) => toggle.addEventListener('click', (event) => {
    event.stopPropagation();
    const menu = document.getElementById(toggle.getAttribute('aria-controls') || '');
    if (!menu) return;
    if (menu.hasAttribute('data-open')) closeRowMenu(menu); else openRowMenu(toggle, menu);
  }));

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-invoice-row-menu]')) return;
    closeAllRowMenus();
  });
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeAllRowMenus(); });
  window.addEventListener('resize', () => closeAllRowMenus());
  window.addEventListener('scroll', () => closeAllRowMenus(), true);

  bulkSelectAll?.addEventListener('change', () => {
    bulkBoxes.forEach((box) => { box.checked = bulkSelectAll.checked; });
    updateBulkControls();
  });
  bulkBoxes.forEach((box) => box.addEventListener('change', updateBulkControls));
  updateBulkControls();

  bulkApprove?.addEventListener('click', async () => {
    const ids = selectedIds('invoiceApprovable');
    const total = selectedBoxes().length;
    if (!ids.length) return;
    const skipped = total - ids.length;
    const detail = skipped > 0 ? ` Seçili ${skipped} uygun olmayan kayıt atlanacak.` : '';
    if (!window.confirm(`${ids.length} taslak fatura resmîleştirilmek üzere NES'e gönderilecek.${detail}\n\nBu toplu işlem için son onayı veriyor musunuz?`)) return;
    bulkApprove.disabled = true;
    setMessage(`${ids.length} taslak sırayla onaylanıyor...`);
    try {
      const result = await talyaAjax('fatura_toplu_onayla', { ids, onay: 'TOPLU_ONAYLA' });
      window.alert(result.mesaj);
      window.location.reload();
    } catch (error) {
      setMessage(error.message);
      bulkApprove.disabled = false;
    }
  });

  bulkCancel?.addEventListener('click', () => {
    const count = selectedIds('invoiceCancelable').length;
    if (!count || !bulkCancelDialog) return;
    if (bulkCancelCount) bulkCancelCount.textContent = String(count);
    if (typeof bulkCancelDialog.showModal === 'function') bulkCancelDialog.showModal(); else bulkCancelDialog.setAttribute('open', 'open');
  });

  bulkDownload?.addEventListener('click', async () => {
    const ids = selectedIds('invoiceDownloadable');
    const total = selectedBoxes().length;
    if (!ids.length || !bulkDownloadForm) return;
    const skipped = total - ids.length;
    const detail = skipped > 0 ? ` Seçili ${skipped} taslak veya uygun olmayan kayıt atlanacak.` : '';
    if (!window.confirm(`${ids.length} kesilmiş faturanın PDF ve XML dosyaları tek ZIP içinde hazırlanacak.${detail}\n\nToplu indirmeyi onaylıyor musunuz?`)) return;
    bulkDownload.disabled = true;
    setMessage('Fatura ZIP arşivi hazırlanıyor...');
    const idsField = bulkDownloadForm.querySelector('[data-invoice-bulk-download-ids]');
    if (idsField) idsField.value = JSON.stringify(ids);
    try {
      const response = await fetch(bulkDownloadForm.action, { method: 'POST', credentials: 'same-origin', body: new FormData(bulkDownloadForm) });
      if (!response.ok) throw new Error((await response.text()) || 'Toplu indirme hazırlanamadı.');
      const blob = await response.blob();
      const disposition = response.headers.get('Content-Disposition') || '';
      const match = disposition.match(/filename="?([^";]+)"?/i);
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = match?.[1] || 'faturalar.zip';
      document.body.appendChild(link); link.click(); link.remove();
      window.setTimeout(() => URL.revokeObjectURL(link.href), 1000);
      setMessage(`${ids.length} fatura için ZIP indirmesi hazırlandı.`);
    } catch (error) {
      setMessage(error.message);
    } finally {
      bulkDownload.disabled = false;
    }
  });

  nesTest?.addEventListener('click', async () => {
    const form = nesTest.closest('form'); const formMessage = form?.querySelector('[data-form-message]');
    nesTest.disabled = true; if (formMessage) formMessage.textContent = 'NES bağlantısı sınanıyor...';
    try { const result = await talyaAjax('nes_baglanti_test', {}); if (formMessage) formMessage.textContent = result.mesaj; }
    catch (error) { if (formMessage) formMessage.textContent = error.message; } finally { nesTest.disabled = false; }
  });

  document.addEventListener('click', async (event) => {
    const archive = event.target.closest('[data-invoice-archive]');
    if (archive) {
      archive.disabled = true; setMessage('PDF ve XML yerel sunucuya arşivleniyor...');
      try { const result = await talyaAjax('fatura_arsivle', { id: archive.dataset.invoiceArchive }); setMessage(result.mesaj); window.setTimeout(() => window.location.reload(), 700); }
      catch (error) { setMessage(error.message); archive.disabled = false; }
      return;
    }
    const approve = event.target.closest('[data-invoice-approve]');
    if (approve) {
      if (!window.confirm('Taslak kontrol edildi mi? Onaydan sonra belge resmî fatura olarak NES üzerinden gönderilecektir. Devam edilsin mi?')) return;
      approve.disabled = true; setMessage('Taslak onaylanıyor ve fatura resmileştiriliyor...');
      try { const result = await talyaAjax('fatura_onayla', { id: approve.dataset.invoiceApprove }); setMessage(result.mesaj); window.setTimeout(() => window.location.reload(), 700); }
      catch (error) { setMessage(error.message); approve.disabled = false; }
      return;
    }
    const status = event.target.closest('[data-invoice-status]');
    if (status) { status.disabled = true; setMessage('Durum güncelleniyor...'); try { const result = await talyaAjax('fatura_durum_guncelle', { id: status.dataset.invoiceStatus }); setMessage(result.mesaj); window.setTimeout(() => window.location.reload(), 500); } catch (error) { setMessage(error.message); } finally { status.disabled = false; } return; }
    const cancel = event.target.closest('[data-invoice-cancel]');
    if (!cancel || !cancelDialog) return;
    if (typeof cancelDialog.showModal === 'function') cancelDialog.showModal(); else cancelDialog.setAttribute('open', 'open');
  });

  document.addEventListener('click', (event) => { if (!event.target.closest('[data-invoice-cancel-close]') || !cancelDialog) return; if (typeof cancelDialog.close === 'function') cancelDialog.close(); else cancelDialog.removeAttribute('open'); });
  document.addEventListener('click', (event) => { if (!event.target.closest('[data-invoice-bulk-cancel-close]') || !bulkCancelDialog) return; if (typeof bulkCancelDialog.close === 'function') bulkCancelDialog.close(); else bulkCancelDialog.removeAttribute('open'); });
  cancelForm?.addEventListener('submit', async (event) => {
    event.preventDefault(); if (cancelForm.dataset.submitting === '1') return;
    const formMessage = cancelForm.querySelector('[data-form-message]'); cancelForm.dataset.submitting = '1'; if (formMessage) formMessage.textContent = 'İptal işlemi NES sistemine iletiliyor...';
    try { const result = await talyaAjax('fatura_iptal', formValues(cancelForm)); if (formMessage) formMessage.textContent = result.mesaj; window.setTimeout(() => window.location.reload(), 700); }
    catch (error) { if (formMessage) formMessage.textContent = error.message; } finally { cancelForm.dataset.submitting = '0'; }
  });

  bulkCancelForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (bulkCancelForm.dataset.submitting === '1') return;
    const ids = selectedIds('invoiceCancelable');
    if (!ids.length) { setMessage('İptal edilebilecek seçili e-Arşiv faturası kalmadı.'); return; }
    if (!window.confirm(`${ids.length} kesilmiş e-Arşiv faturası iptal edilmek üzere NES'e gönderilecek.\n\nBu işlem için son ve kesin onayı veriyor musunuz?`)) return;
    const formMessage = bulkCancelForm.querySelector('[data-form-message]');
    bulkCancelForm.dataset.submitting = '1';
    if (formMessage) formMessage.textContent = 'Faturalar sırayla iptal ediliyor...';
    try {
      const result = await talyaAjax('fatura_toplu_iptal', { ...formValues(bulkCancelForm), ids, onay: 'TOPLU_IPTAL' });
      window.alert(result.mesaj);
      window.location.reload();
    } catch (error) {
      if (formMessage) formMessage.textContent = error.message;
    } finally {
      bulkCancelForm.dataset.submitting = '0';
    }
  });
})();
