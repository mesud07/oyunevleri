'use strict';

const talyaDistricts = {
  Antalya: [
    'Akseki',
    'Aksu',
    'Alanya',
    'Demre',
    'Dosemealti',
    'Elmali',
    'Finike',
    'Gazipasa',
    'Gundogmus',
    'Ibradi',
    'Kas',
    'Kemer',
    'Kepez',
    'Konyaalti',
    'Korkuteli',
    'Kumluca',
    'Manavgat',
    'Muratpasa',
    'Serik'
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
