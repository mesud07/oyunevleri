'use strict';

function developmentTestToday() {
  const today = new Date();
  const offset = today.getTimezoneOffset() * 60000;
  return new Date(today.getTime() - offset).toISOString().slice(0, 10);
}

function updateDevelopmentTestDateLabel(form) {
  const checkbox = form.querySelector('input[name="uygulandi"]');
  const dateLabel = form.querySelector('.development-test-date span');
  if (dateLabel) {
    dateLabel.textContent = checkbox.checked ? 'Uygulama tarihi' : 'Planlanan tarih';
  }
}

async function saveDevelopmentTest(form) {
  if (form.dataset.saving === '1') return;

  const checkbox = form.querySelector('input[name="uygulandi"]');
  const date = form.querySelector('input[name="uygulama_tarihi"]');
  const message = form.querySelector('[data-development-test-message]');
  const controls = [checkbox, date].filter(Boolean);
  const previousApplied = form.dataset.currentApplied === '1';
  const previousDate = form.dataset.currentDate || '';

  form.dataset.saving = '1';
  controls.forEach((control) => { control.disabled = true; });
  message.textContent = 'Kaydediliyor...';
  message.classList.remove('is-error');

  try {
    await talyaAjax('ogrenci_gelisim_testi_guncelle', {
      ogrenci_id: form.querySelector('[name="ogrenci_id"]')?.value || '',
      uygulandi: checkbox.checked,
      uygulama_tarihi: date.value
    });
    const total = document.querySelector('[data-development-test-total]');
    if (total && previousApplied !== checkbox.checked) {
      total.textContent = String(Math.max(0, Number(total.textContent) + (checkbox.checked ? 1 : -1)));
    }
    form.dataset.currentApplied = checkbox.checked ? '1' : '0';
    form.dataset.currentDate = date.value;
    message.textContent = checkbox.checked
      ? 'Uygulandı · Otomatik kaydedildi'
      : (date.value ? 'Test tarihi planlandı · Otomatik kaydedildi' : 'Otomatik kaydedildi');
  } catch (error) {
    checkbox.checked = previousApplied;
    date.value = previousDate;
    updateDevelopmentTestDateLabel(form);
    message.textContent = error.message;
    message.classList.add('is-error');
  } finally {
    controls.forEach((control) => { control.disabled = false; });
    form.dataset.saving = '0';
  }
}

document.addEventListener('change', async (event) => {
  const control = event.target.closest('[data-development-test-form] input[name="uygulandi"], [data-development-test-form] input[name="uygulama_tarihi"]');
  if (!control) return;

  const form = control.closest('[data-development-test-form]');
  const checkbox = form.querySelector('input[name="uygulandi"]');
  const date = form.querySelector('input[name="uygulama_tarihi"]');
  if (control.name === 'uygulandi' && checkbox.checked && !date.value) {
    date.value = developmentTestToday();
  }
  updateDevelopmentTestDateLabel(form);
  await saveDevelopmentTest(form);
});

if (document.querySelector('.attendance-print-report')) {
  document.body.classList.add('attendance-report-page');
}

document.addEventListener('click', (event) => {
  if (event.target.closest('[data-attendance-print]')) {
    window.print();
  }
});
