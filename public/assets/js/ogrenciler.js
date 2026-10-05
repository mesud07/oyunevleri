'use strict';

const talyaDistricts = {
  Antalya: [
    'Muratpaşa',
    'Kepez',
    'Konyaaltı',
    'Döşemealtı',
    'Aksu'
  ]
};

function formatTalyaPhone(value) {
  const rawDigits = String(value || '').replace(/\D/g, '');
  if (rawDigits === '') {
    return '';
  }
  if (rawDigits === '0') {
    return '0';
  }

  const digits = rawDigits.startsWith('0') ? rawDigits.slice(1, 11) : rawDigits.slice(0, 10);
  if (digits === '') {
    return '0';
  }

  let formatted = `0(${digits.slice(0, 3)}`;
  if (digits.length >= 3) {
    formatted += ')';
  }
  if (digits.length > 3) {
    formatted += ` ${digits.slice(3, 6)}`;
  }
  if (digits.length > 6) {
    formatted += ` ${digits.slice(6, 8)}`;
  }
  if (digits.length > 8) {
    formatted += ` ${digits.slice(8, 10)}`;
  }

  return formatted;
}

document.addEventListener('input', (event) => {
  const field = event.target.closest('[data-phone-mask]');
  if (!field) {
    return;
  }

  const formatted = formatTalyaPhone(field.value);
  field.value = formatted;
  field.setSelectionRange(formatted.length, formatted.length);
  const studentCreateForm = field.closest('[data-student-create-form]');
  if (studentCreateForm) {
    studentCreateForm.dataset.phoneChecked = '0';
  }
});

document.addEventListener('blur', (event) => {
  const field = event.target.closest('[data-phone-mask]');
  if (!field) {
    return;
  }
  field.value = formatTalyaPhone(field.value);
}, true);

document.addEventListener('change', (event) => {
  const city = event.target.closest('[data-city-select]');
  if (!city) {
    return;
  }

  const district = document.querySelector('[data-district-select]');
  if (!district) {
    return;
  }

  const items = talyaDistricts[city.value] || [];
  district.innerHTML = items.length
    ? '<option value="">Seciniz</option>' + items.map((item) => `<option value="${escapeHtml(item)}">${escapeHtml(item)}</option>`).join('')
    : '<option value="">Once il seciniz.</option>';
  district.disabled = items.length === 0;
});

(() => {
  const dialog = document.querySelector('#ogrenci-toplu-import-dialog');
  const form = dialog?.querySelector('[data-student-import-form]');
  const result = dialog?.querySelector('[data-student-import-result]');
  if (!dialog || !form || !result) {
    return;
  }

  document.querySelector('[data-open-student-import]')?.addEventListener('click', () => {
    result.innerHTML = '';
    openDialogElement(dialog);
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submit = form.querySelector('button[type="submit"]');
    submit.disabled = true;
    result.className = 'import-result is-loading';
    result.textContent = 'Excel dosyası kontrol ediliyor ve öğrenciler aktarılıyor...';

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        body: new FormData(form)
      });
      const payload = await response.json();
      const details = payload.veri?.hatalar || [];
      result.className = `import-result ${payload.basari ? 'is-success' : 'is-error'}`;
      result.innerHTML = `
        <strong>${escapeHtml(payload.mesaj || 'Aktarım tamamlandı.')}</strong>
        ${details.length ? `
          <details>
            <summary>Atlanan ve hatalı satırları göster (${details.length})</summary>
            <ul>${details.map((item) => `<li><b>Satır ${escapeHtml(item.satir || '-')}:</b> ${escapeHtml(item.mesaj || '')}</li>`).join('')}</ul>
          </details>
        ` : ''}
      `;

      if (Number(payload.veri?.eklenen || 0) > 0) {
        form.querySelector('[name="excel_dosyasi"]').value = '';
        const table = document.querySelector('[data-table="ogrenci_listele"]');
        if (table) {
          table.dataset.page = '1';
          await loadAjaxTable(table);
        }
      }
    } catch (error) {
      result.className = 'import-result is-error';
      result.textContent = 'Sunucudan geçerli bir aktarım yanıtı alınamadı. Lütfen tekrar deneyin.';
    } finally {
      submit.disabled = false;
    }
  });
})();
