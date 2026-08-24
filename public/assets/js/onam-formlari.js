(() => {
  const dialog = document.querySelector('[data-consent-dialog]');
  const form = dialog?.querySelector('[data-consent-form]');
  if (!dialog || !form) {
    return;
  }

  const steps = [...form.querySelectorAll('[data-consent-step]')];
  const actions = [...form.querySelectorAll('[data-consent-actions]')];
  const stepLabel = form.querySelector('[data-consent-step-label]');
  const nextButton = form.querySelector('[data-consent-next]');
  const templateRadio = form.querySelector('[name="sablon_kodu"]');
  const messages = [...form.querySelectorAll('[data-consent-message]')];
  const labels = {
    selection: '1 / 3 · Form Seçimi',
    details: '2 / 3 · Bilgileri Düzenle',
    preview: '3 / 3 · Form Önizleme'
  };

  function setMessage(message = '', isError = false) {
    messages.forEach((item) => {
      item.textContent = message;
      item.classList.toggle('is-error', isError);
    });
  }

  function showStep(name) {
    steps.forEach((step) => {
      step.hidden = step.getAttribute('data-consent-step') !== name;
    });
    actions.forEach((row) => {
      row.hidden = row.getAttribute('data-consent-actions') !== name;
    });
    if (stepLabel) {
      stepLabel.textContent = labels[name] || '';
    }
    form.querySelector('.consent-dialog-body')?.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function open() {
    form.reset();
    form.querySelectorAll('.consent-template-card.is-selected').forEach((card) => card.classList.remove('is-selected'));
    const formDate = form.querySelector('[name="form_tarihi"]');
    if (formDate) {
      const now = new Date();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      formDate.value = `${now.getFullYear()}-${month}-${day}`;
    }
    if (nextButton) {
      nextButton.disabled = true;
    }
    setMessage();
    showStep('selection');
    dialog.showModal();
  }

  function close() {
    if (typeof dialog.close === 'function') {
      dialog.close();
    }
  }

  function formatValue(name, value) {
    if (!value) {
      return '-';
    }
    if (name.includes('tarihi')) {
      const parts = value.split('-');
      return parts.length === 3 ? `${parts[2]}.${parts[1]}.${parts[0]}` : value;
    }
    return value;
  }

  function fillPreview() {
    form.querySelectorAll('[data-consent-preview-value]').forEach((element) => {
      const name = element.getAttribute('data-consent-preview-value');
      const field = form.elements.namedItem(name);
      element.textContent = formatValue(name, field?.value?.trim() || '');
    });
  }

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-open-consent-form]')) {
      open();
      return;
    }
    if (event.target.closest('[data-consent-close]')) {
      close();
      return;
    }
    if (event.target.closest('[data-consent-next]')) {
      if (templateRadio?.checked) {
        showStep('details');
      }
      return;
    }
    if (event.target.closest('[data-consent-back]')) {
      showStep('selection');
      return;
    }
    if (event.target.closest('[data-consent-edit]')) {
      showStep('details');
      return;
    }
    if (event.target.closest('[data-consent-preview]')) {
      if (!form.reportValidity()) {
        return;
      }
      fillPreview();
      showStep('preview');
    }
  });

  templateRadio?.addEventListener('change', () => {
    if (nextButton) {
      nextButton.disabled = !templateRadio.checked;
    }
    templateRadio.closest('.consent-template-card')?.classList.toggle('is-selected', templateRadio.checked);
  });

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      close();
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!form.reportValidity() || form.dataset.submitting === '1') {
      return;
    }

    const pdfWindow = window.open('about:blank', '_blank');
    if (pdfWindow) {
      pdfWindow.document.write('<p style="font-family:sans-serif;padding:24px">PDF hazırlanıyor...</p>');
    }

    const payload = Object.fromEntries(new FormData(form).entries());
    payload.bilgiler_dogrulandi = form.elements.namedItem('bilgiler_dogrulandi')?.checked ? 1 : 0;
    form.dataset.submitting = '1';
    form.querySelectorAll('button').forEach((button) => { button.disabled = true; });
    setMessage('Form ve PDF hazırlanıyor...');

    try {
      const result = await talyaAjax('onam_formu_olustur', payload);
      setMessage(result.mesaj || 'Onam formu oluşturuldu.');
      if (pdfWindow) {
        pdfWindow.location.href = result.veri.pdf_url;
      }
      window.setTimeout(() => window.location.reload(), 700);
    } catch (error) {
      pdfWindow?.close();
      setMessage(error.message || 'Onam formu oluşturulamadı.', true);
      showStep('details');
    } finally {
      form.dataset.submitting = '0';
      form.querySelectorAll('button').forEach((button) => { button.disabled = false; });
      if (nextButton) {
        nextButton.disabled = !templateRadio?.checked;
      }
    }
  });
})();
