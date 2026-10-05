(function () {
  const financePanel = document.querySelector('[data-student-tab-panel="finans"]');
  const profilePanel = document.querySelector('[data-student-tab-panel="profil"]');
  const tabs = Array.from(document.querySelectorAll('[data-student-tab]'));

  if (!financePanel || !profilePanel || !tabs.length) {
    return;
  }

  function activate(panelName, activeTab, scrollTarget) {
    const showFinance = panelName === 'finans';
    financePanel.hidden = !showFinance;
    profilePanel.hidden = showFinance;
    tabs.forEach((tab) => tab.classList.toggle('is-active', tab === activeTab));

    if (scrollTarget && !showFinance) {
      window.setTimeout(() => document.querySelector(scrollTarget)?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 0);
    }
  }

  tabs.forEach((tab) => {
    tab.addEventListener('click', (event) => {
      event.preventDefault();
      const panelName = tab.dataset.studentTab || 'profil';
      const hash = tab.getAttribute('href') || (panelName === 'finans' ? '#finans' : '#randevular');
      activate(panelName, tab, hash);
      window.history.replaceState(null, '', hash);
    });
  });

  document.querySelector('[data-finance-profile-back]')?.addEventListener('click', () => {
    const appointmentTab = tabs.find((tab) => tab.getAttribute('href') === '#randevular') || tabs.find((tab) => tab.dataset.studentTab === 'profil');
    activate('profil', appointmentTab, '#randevular');
    window.history.replaceState(null, '', '#randevular');
  });

  document.querySelector('[data-finance-refresh]')?.addEventListener('click', () => {
    window.location.reload();
  });

  if (window.location.hash === '#finans') {
    const financeTab = tabs.find((tab) => tab.dataset.studentTab === 'finans');
    activate('finans', financeTab);
  }

  const dialog = document.querySelector('#profil-fatura-kes-dialog');
  const form = document.querySelector('[data-profile-invoice-form]');
  const paymentSelect = form?.querySelector('[data-profile-invoice-payment]');
  if (!dialog || !form || !paymentSelect) {
    return;
  }

  function openDialog() {
    if (dialog.open) return;
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', 'open');
  }

  function closeDialog() {
    if (typeof dialog.close === 'function') dialog.close();
    else dialog.removeAttribute('open');
  }

  function syncProfileType() {
    const corporate = form.elements.profil_turu.value === 'kurumsal';
    form.querySelectorAll('[data-profile-individual-field]').forEach((node) => { node.hidden = corporate; });
    form.querySelectorAll('[data-profile-corporate-field]').forEach((node) => { node.hidden = !corporate; });
    form.elements.ad.required = !corporate;
    form.elements.soyad.required = !corporate;
    form.elements.unvan.required = corporate;
  }

  async function prepareInvoice(paymentId) {
    if (!paymentId) return;
    paymentSelect.value = String(paymentId);
    form.elements.odeme_id.value = String(paymentId);
    const message = form.querySelector('[data-form-message]');
    if (message) message.textContent = 'Fatura bilgileri yükleniyor...';
    openDialog();

    try {
      const result = await talyaAjax('fatura_odeme_hazirlik', { odeme_id: paymentId });
      const profile = result.veri?.profil || {};
      const payment = result.veri?.odeme || {};
      const paymentFields = { vkn_tckn: 'tc_kimlik_no', ad: 'veli_ad', soyad: 'veli_soyad', adres: 'adres', il: 'il', ilce: 'ilce', eposta: 'eposta', telefon: 'telefon' };

      ['profil_turu', 'vkn_tckn', 'ad', 'soyad', 'unvan', 'vergi_dairesi', 'adres', 'il', 'ilce', 'ulke', 'eposta', 'telefon'].forEach((field) => {
        if (!form.elements[field]) return;
        const fallback = field === 'ulke' ? 'TÜRKİYE' : (field === 'profil_turu' ? 'bireysel' : '');
        form.elements[field].value = profile[field] ?? payment[paymentFields[field]] ?? fallback;
      });
      if (form.elements.kdv_orani) {
        form.elements.kdv_orani.value = payment.kdv_orani ?? '';
        form.elements.kdv_orani.readOnly = payment.kdv_orani !== null && payment.kdv_orani !== undefined;
      }
      const info = dialog.querySelector('[data-profile-invoice-info]');
      if (info) info.textContent = `${payment.paket_adi || 'Tahsilat'} · ${Number(payment.tutar || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} TL`;
      syncProfileType();
      if (message) message.textContent = '';
    } catch (error) {
      if (message) message.textContent = error.message;
    }
  }

  document.querySelector('[data-profile-invoice-start]')?.addEventListener('click', () => {
    const firstPayment = paymentSelect.querySelector('option[value]:not([value=""])')?.value || '';
    prepareInvoice(firstPayment);
  });

  document.querySelectorAll('[data-profile-payment-invoice]').forEach((button) => {
    button.addEventListener('click', () => prepareInvoice(button.dataset.profilePaymentInvoice || ''));
  });
  paymentSelect.addEventListener('change', () => prepareInvoice(paymentSelect.value));
  form.querySelector('[data-profile-invoice-type]')?.addEventListener('change', syncProfileType);
  dialog.querySelectorAll('[data-profile-invoice-close]').forEach((button) => button.addEventListener('click', closeDialog));

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (form.dataset.submitting === '1') return;
    const message = form.querySelector('[data-form-message]');
    const submit = form.querySelector('button[type="submit"]');
    form.dataset.submitting = '1';
    if (submit) submit.disabled = true;
    if (message) message.textContent = 'Mükellef sorgulanıyor ve fatura taslağı oluşturuluyor...';
    try {
      const result = await talyaAjax('fatura_olustur', Object.fromEntries(new FormData(form).entries()));
      window.location.href = `/panel/faturalar/detay?id=${encodeURIComponent(result.veri.id)}`;
    } catch (error) {
      if (message) message.textContent = error.message;
    } finally {
      form.dataset.submitting = '0';
      if (submit) submit.disabled = false;
    }
  });

  syncProfileType();
})();
