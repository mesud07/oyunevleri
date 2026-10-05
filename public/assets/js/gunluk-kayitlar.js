(() => {
  const dialog = document.querySelector('[data-ai-report-dialog]');
  if (!dialog) return;

  const form = dialog.querySelector('[data-ai-report-form]');
  const setup = dialog.querySelector('[data-ai-report-setup]');
  const editor = dialog.querySelector('[data-ai-report-editor]');
  const message = dialog.querySelector('[data-ai-report-message]');
  const generateButton = dialog.querySelector('[data-ai-report-generate]');
  const pdfButton = dialog.querySelector('[data-ai-report-pdf]');
  const backButton = dialog.querySelector('[data-ai-report-back]');
  const pdfForm = document.querySelector('[data-ai-report-pdf-form]');
  let report = null;

  function setMessage(text, state = '') {
    message.textContent = text || '';
    message.dataset.state = state;
  }

  function setBusy(busy) {
    generateButton.disabled = busy;
    generateButton.textContent = busy ? 'Notlar inceleniyor...' : 'Taslak Olustur';
    form.setAttribute('aria-busy', busy ? 'true' : 'false');
  }

  function showSetup() {
    setup.hidden = false;
    editor.hidden = true;
    generateButton.hidden = false;
    pdfButton.hidden = true;
    backButton.hidden = true;
    report = null;
    setMessage('');
  }

  function showEditor(data) {
    report = data;
    setup.hidden = true;
    editor.hidden = false;
    generateButton.hidden = true;
    pdfButton.hidden = false;
    backButton.hidden = false;
    dialog.querySelector('[data-ai-report-student]').textContent = data.ogrenci_adi || 'Ogrenci';
    dialog.querySelector('[data-ai-report-period]').textContent = `${formatDate(data.baslangic)} - ${formatDate(data.bitis)} / ${data.yas_grubu || '-'}`;
    dialog.querySelector('[data-ai-report-note-count]').textContent = `${data.not_sayisi || 0} not incelendi`;
    dialog.querySelectorAll('[data-report-section]').forEach((field) => {
      field.value = data.bolumler?.[field.dataset.reportSection] || '';
    });
  }

  function formatDate(value) {
    const parts = String(value || '').split('-');
    return parts.length === 3 ? `${parts[2]}.${parts[1]}.${parts[0]}` : value;
  }

  document.querySelector('[data-ai-report-open]')?.addEventListener('click', () => {
    showSetup();
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', 'open');
  });

  dialog.querySelectorAll('[data-ai-report-close]').forEach((button) => {
    button.addEventListener('click', () => dialog.close());
  });

  backButton.addEventListener('click', showSetup);

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (generateButton.disabled) return;
    const studentId = form.elements.ogrenci_id.value;
    const period = form.querySelector('input[name="donem"]:checked')?.value || '1';
    if (!studentId) {
      setMessage('Lutfen bir ogrenci secin.', 'error');
      form.elements.ogrenci_id.focus();
      return;
    }

    setBusy(true);
    setMessage('Gunluk notlar analiz ediliyor. Bu islem biraz surebilir...', 'loading');
    try {
      const result = await talyaAjax('gunluk_not_raporu_olustur', { ogrenci_id: Number(studentId), donem: Number(period) });
      showEditor(result.veri);
      setMessage(result.mesaj || 'Taslak hazirlandi.', 'success');
    } catch (error) {
      setMessage(error.message || 'Rapor olusturulamadi.', 'error');
    } finally {
      setBusy(false);
    }
  });

  pdfButton.addEventListener('click', () => {
    if (!report || !pdfForm) return;
    const sections = {};
    let missing = false;
    dialog.querySelectorAll('[data-report-section]').forEach((field) => {
      sections[field.dataset.reportSection] = field.value.trim();
      if (!sections[field.dataset.reportSection]) missing = true;
    });
    if (missing) {
      setMessage('PDF icin tum rapor bolumlerini doldurun.', 'error');
      return;
    }
    report.bolumler = sections;
    pdfForm.elements.rapor.value = JSON.stringify(report);
    pdfForm.submit();
    setMessage('PDF yeni sekmede hazirlaniyor...', 'success');
  });
})();
