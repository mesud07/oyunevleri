(function () {
  const form = document.querySelector('[data-cari-form]');
  if (!form) return;

  const results = form.querySelector('[data-cari-parent-results]');
  const search = form.querySelector('[data-cari-parent-search]');
  const type = form.querySelector('[data-cari-type]');
  const miscCustomer = form.querySelector('[data-misc-customer]');
  const cariDialog = document.querySelector('#cari-dialog');
  const invoiceDialog = document.querySelector('#cari-fatura-dialog');
  const invoiceForm = document.querySelector('[data-cari-invoice-form]');
  let timer;

  const miscDefaults = {
    vkn_tckn: '11111111111',
    il: 'Antalya',
    ilce: 'Muratpaşa',
    adres: 'Muratpaşa / Antalya'
  };

  function syncType() {
    const corporate = type.value === 'kurumsal';
    form.querySelectorAll('[data-cari-person]').forEach((element) => {
      element.hidden = corporate;
    });
    form.querySelectorAll('[data-cari-company]').forEach((element) => {
      element.hidden = !corporate;
    });
    form.elements.ad.required = !corporate;
    form.elements.soyad.required = !corporate;
    form.elements.unvan.required = corporate;
    form.elements.vkn_tckn.maxLength = corporate ? 10 : 11;
  }

  function setSubmitting(targetForm, submitting) {
    targetForm.dataset.submitting = submitting ? '1' : '';
    targetForm.querySelectorAll('button[type="submit"]').forEach((button) => {
      button.disabled = submitting;
    });
  }

  function syncMiscCustomer(clearDefaults = false) {
    const enabled = miscCustomer.checked;
    search.disabled = enabled;
    type.disabled = enabled;

    Object.entries(miscDefaults).forEach(([name, value]) => {
      const field = form.elements[name];
      if (enabled) field.value = value;
      else if (clearDefaults && field.value === value) field.value = '';
      field.readOnly = enabled;
    });

    if (enabled) {
      form.elements.veli_id.value = '';
      form.elements.profil_turu.value = 'bireysel';
      form.elements.ad.value = '';
      form.elements.soyad.value = '';
      search.value = '';
      results.innerHTML = '<span class="status-pill is-success">Muhtelif müşteri bilgileri hazır</span>';
    } else if (clearDefaults) {
      results.innerHTML = '';
    }
    syncType();
  }

  function openInvoice(current) {
    if (!invoiceForm || !current?.id) return;
    const name = current.name || (current.profil_turu === 'kurumsal'
      ? current.unvan
      : `${current.ad || ''} ${current.soyad || ''}`.trim());
    invoiceForm.reset();
    setSubmitting(invoiceForm, false);
    invoiceForm.elements.cari_id.value = current.id;
    invoiceForm.querySelector('[data-form-message]').textContent = '';
    document.querySelector('[data-cari-invoice-name]').textContent = name;
    if (typeof invoiceDialog.showModal === 'function') invoiceDialog.showModal();
    else invoiceDialog.setAttribute('open', 'open');
  }

  type.addEventListener('change', syncType);
  miscCustomer.addEventListener('change', () => syncMiscCustomer(true));
  syncType();
  syncMiscCustomer();

  search.addEventListener('input', () => {
    clearTimeout(timer);
    form.elements.veli_id.value = '';
    timer = setTimeout(async () => {
      const q = search.value.trim();
      if (q.length < 2) {
        results.innerHTML = '';
        return;
      }

      results.innerHTML = '<span class="muted">Aranıyor...</span>';
      try {
        const response = await talyaAjax('cari_veli_ara', { q });
        results.innerHTML = (response.veri || []).map((parent) => (
          `<button type="button" class="mini-btn" data-parent='${escapeHtml(JSON.stringify(parent))}'>`
          + `${escapeHtml(`${parent.ad} ${parent.soyad}`)} · `
          + `${escapeHtml(parent.tc_kimlik_no ? `***${parent.tc_kimlik_no.slice(-4)}` : 'TCKN yok')}</button>`
        )).join('') || '<span class="muted">Eşleşen veli yok; manuel cari açabilirsiniz.</span>';
      } catch (error) {
        results.textContent = error.message;
      }
    }, 250);
  });

  results.addEventListener('click', (event) => {
    const button = event.target.closest('[data-parent]');
    if (!button) return;

    const parent = JSON.parse(button.dataset.parent);
    form.elements.veli_id.value = parent.id || '';
    form.elements.profil_turu.value = 'bireysel';
    ['ad', 'soyad', 'telefon', 'eposta', 'il', 'ilce', 'adres'].forEach((key) => {
      form.elements[key].value = parent[key] || '';
    });
    form.elements.vkn_tckn.value = parent.tc_kimlik_no || '';
    search.value = `${parent.ad} ${parent.soyad}`;
    syncType();
    results.innerHTML = '<span class="status-pill is-success">Veli bilgileri aktarıldı</span>';
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (form.dataset.submitting === '1') return;

    const message = form.querySelector('[data-form-message]');
    message.textContent = 'Cari kaydediliyor...';
    setSubmitting(form, true);
    try {
      const response = await talyaAjax('cari_kaydet', formValues(form));
      message.textContent = response.mesaj;
      setSubmitting(form, false);
      if (cariDialog?.open) cariDialog.close();
      openInvoice(response.veri);
    } catch (error) {
      message.textContent = error.message;
      setSubmitting(form, false);
    }
  });

  document.addEventListener('click', (event) => {
    const newCariButton = event.target.closest('[data-open-dialog="#cari-dialog"]');
    if (newCariButton) {
      form.reset();
      setSubmitting(form, false);
      form.querySelector('[data-form-message]').textContent = '';
      syncMiscCustomer();
      return;
    }

    const button = event.target.closest('[data-cari-invoice]');
    if (!button || !invoiceForm) return;

    openInvoice(JSON.parse(button.dataset.cariInvoice));
  });

  invoiceForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (invoiceForm.dataset.submitting === '1') return;

    const message = invoiceForm.querySelector('[data-form-message]');
    message.textContent = 'NES üzerinde taslak oluşturuluyor...';
    setSubmitting(invoiceForm, true);
    try {
      const response = await talyaAjax('cari_fatura_olustur', formValues(invoiceForm));
      window.location.href = `/panel/faturalar/detay?id=${encodeURIComponent(response.veri.id)}`;
    } catch (error) {
      message.textContent = error.message;
      setSubmitting(invoiceForm, false);
    }
  });
})();
