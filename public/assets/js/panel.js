document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-demo-islem]');
  if (!button) {
    return;
  }
  event.preventDefault();
  const sonucAlani = document.querySelector('#ajax-sonuc');
  const islem = button.getAttribute('data-demo-islem');
  if (sonucAlani) {
    sonucAlani.textContent = 'Islem calisiyor...';
  }
  try {
    const sonuc = await talyaAjax(islem);
    if (sonucAlani) {
      sonucAlani.textContent = sonuc.mesaj;
    }
  } catch (error) {
    if (sonucAlani) {
      sonucAlani.textContent = error.message;
    }
  }
});

(() => {
  const toggles = Array.from(document.querySelectorAll('[data-sidebar-toggle]'));
  const closeButtons = Array.from(document.querySelectorAll('[data-sidebar-close]'));
  if (!toggles.length) {
    return;
  }

  const root = document.documentElement;
  const mobileQuery = window.matchMedia('(max-width: 900px)');

  function setToggleState(expanded) {
    document.querySelectorAll('[data-sidebar-toggle]').forEach((toggle) => {
      toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    });
  }

  function applySidebarState() {
    if (mobileQuery.matches) {
      root.classList.add('sidebar-collapsed');
      root.classList.remove('sidebar-expanded');
      setToggleState(root.classList.contains('sidebar-mobile-open'));
      return;
    }

    const stored = localStorage.getItem('talyaSidebarCollapsed');
    const collapsed = stored === '1';

    root.classList.remove('sidebar-mobile-open');
    root.classList.toggle('sidebar-collapsed', collapsed);
    root.classList.toggle('sidebar-expanded', !collapsed);
    setToggleState(!collapsed);
  }

  function closeMobileSidebar() {
    root.classList.remove('sidebar-mobile-open');
    document.body.classList.remove('has-sidebar-open');
    applySidebarState();
  }

  closeButtons.forEach((button) => {
    button.addEventListener('click', closeMobileSidebar);
  });

  mobileQuery.addEventListener?.('change', () => {
    closeMobileSidebar();
    applySidebarState();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && root.classList.contains('sidebar-mobile-open')) {
      closeMobileSidebar();
    }
  });

  document.addEventListener('click', (event) => {
    const sidebarToggle = event.target.closest('[data-sidebar-toggle]');
    if (sidebarToggle) {
      if (mobileQuery.matches) {
        const willOpen = !root.classList.contains('sidebar-mobile-open');
        root.classList.toggle('sidebar-mobile-open', willOpen);
        document.body.classList.toggle('has-sidebar-open', willOpen);
        setToggleState(willOpen);
        return;
      }

      const nextCollapsed = !root.classList.contains('sidebar-collapsed');
      localStorage.setItem('talyaSidebarCollapsed', nextCollapsed ? '1' : '0');
      applySidebarState();
      return;
    }

    if (!mobileQuery.matches || !root.classList.contains('sidebar-mobile-open')) {
      return;
    }
    if (event.target.closest('.sidebar') || event.target.closest('[data-sidebar-toggle]')) {
      return;
    }
    closeMobileSidebar();
  });

  applySidebarState();
})();

document.addEventListener('click', (event) => {
  const toggle = event.target.closest('[data-menu-group-toggle]');
  if (!toggle) {
    return;
  }

  const group = toggle.closest('.menu-group');
  if (!group) {
    return;
  }
  const isOpen = group.classList.toggle('is-open');
  toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
});

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function formValues(form) {
  preparePackageAssignmentForm(form);

  const values = {};
  const data = new FormData(form);
  data.forEach((value, key) => {
    if (key.endsWith('[]')) {
      const cleanKey = key.slice(0, -2);
      values[cleanKey] = values[cleanKey] || [];
      values[cleanKey].push(value);
      return;
    }
    if (Object.prototype.hasOwnProperty.call(values, key)) {
      values[key] = Array.isArray(values[key]) ? values[key] : [values[key]];
      values[key].push(value);
      return;
    }
    values[key] = value;
  });
  return values;
}

function normalRightCount(form) {
  return Number(form.querySelector('[name="toplam_normal_hak"]')?.value || 0);
}

function isoWeekday(dateValue) {
  const date = new Date(`${dateValue}T00:00:00`);
  if (Number.isNaN(date.getTime())) {
    return '';
  }
  const day = date.getDay();
  return String(day === 0 ? 7 : day);
}

function preparePackageAssignmentForm(form) {
  if (!form.matches('[data-package-assignment-form]')) {
    return;
  }

  const singleFields = form.querySelector('[data-single-schedule-fields]');
  if (!singleFields) {
    return;
  }

  singleFields.innerHTML = '';
  if (normalRightCount(form) !== 1) {
    return;
  }

  const startDate = form.querySelector('[name="baslangic_tarihi"]')?.value || '';
  const weekday = isoWeekday(startDate);
  const time = form.querySelector('[name="tek_randevu_saati"]')?.value || '15:00';
  if (!weekday) {
    return;
  }

  singleFields.innerHTML = `
    <input type="hidden" name="program_gunleri[]" value="${escapeHtml(weekday)}">
    <input type="hidden" name="program_saat_${escapeHtml(weekday)}" value="${escapeHtml(time)}">
  `;
}

function updatePackageAssignmentSchedule(form) {
  if (!form?.matches('[data-package-assignment-form]')) {
    return;
  }

  const isSingleAppointment = normalRightCount(form) === 1;
  const weeklyCard = form.querySelector('[data-weekly-schedule-card]');
  const singleTime = form.querySelector('[data-single-appointment-time]');

  if (weeklyCard) {
    weeklyCard.hidden = isSingleAppointment;
  }
  if (singleTime) {
    singleTime.hidden = !isSingleAppointment;
  }

  form.querySelectorAll('[name="program_gunleri[]"]').forEach((field) => {
    field.disabled = isSingleAppointment;
  });
  form.querySelectorAll('[name^="program_saat_"]').forEach((field) => {
    field.disabled = isSingleAppointment;
  });

  preparePackageAssignmentForm(form);
}

function formatMoney(value) {
  return new Intl.NumberFormat('tr-TR', {
    style: 'currency',
    currency: 'TRY'
  }).format(Number(value || 0));
}

(() => {
  const report = document.querySelector('[data-capacity-report]');
  if (!report) {
    return;
  }

  const input = report.querySelector('[data-capacity-student-input]');
  const result = report.querySelector('[data-capacity-income-result]');
  const average = Number(report.getAttribute('data-average-income') || 0);
  if (!input || !result) {
    return;
  }

  function render() {
    const count = Math.max(0, Number(input.value || 0));
    result.textContent = formatMoney(count * average);
  }

  input.addEventListener('input', render);
  render();
})();

function renderHizmetTable(target, rows) {
  if (!rows || rows.length === 0) {
    target.innerHTML = '<div class="empty-table">Kayit bulunamadi.</div>';
    return;
  }

  const tbody = rows.map((row, index) => `
    <tr>
      <td>${index + 1}</td>
      <td>${escapeHtml(row.hizmet_adi)}</td>
      <td>${escapeHtml(formatMoney(row.ucret))}</td>
      <td>${escapeHtml(row.haftalik_katilim_sayisi)}</td>
      <td>${escapeHtml(row.toplam_normal_hak)}</td>
      <td>${escapeHtml(row.toplam_telafi_hak)}</td>
      <td><span class="status-pill">${String(row.aktif) === '1' ? 'Aktif' : 'Pasif'}</span></td>
      <td>
        <button
          class="btn btn-ghost"
          type="button"
          data-edit-service
          data-id="${escapeHtml(row.id)}"
          data-name="${escapeHtml(row.hizmet_adi)}"
          data-price="${escapeHtml(row.ucret)}"
          data-weekly="${escapeHtml(row.haftalik_katilim_sayisi)}"
          data-normal="${escapeHtml(row.toplam_normal_hak)}"
          data-makeup="${escapeHtml(row.toplam_telafi_hak)}"
          data-active="${escapeHtml(row.aktif)}"
        >Duzenle</button>
        <button
          class="btn btn-danger"
          type="button"
          data-delete-service="${escapeHtml(row.id)}"
        >Sil</button>
      </td>
    </tr>
  `).join('');

  target.innerHTML = `
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Paket Adi</th>
          <th>Ucret</th>
          <th>Haftalik Katilim</th>
          <th>Normal Hak</th>
          <th>Telafi Hakki</th>
          <th>Durum</th>
          <th>Islem</th>
        </tr>
      </thead>
      <tbody>${tbody}</tbody>
    </table>
  `;
}

function normalizeSearch(value) {
  return String(value || '')
    .toLocaleLowerCase('tr-TR')
    .replace(/\D/g, (match) => match);
}

function searchablePhone(value) {
  return String(value || '').replace(/\D/g, '');
}

function renderMiniPagination(paging, attributeName, limitAttribute) {
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
        <button class="mini-btn" type="button" ${attributeName}="1" ${pageNumber <= 1 ? 'disabled' : ''}>&laquo;</button>
        <button class="mini-btn" type="button" ${attributeName}="${Math.max(1, pageNumber - 1)}" ${pageNumber <= 1 ? 'disabled' : ''}>&lsaquo;</button>
        ${totalPage <= 1 ? `<button class="mini-btn is-active" type="button" ${attributeName}="1">1</button>` : ''}
        ${start > 1 ? '<span class="table-pagination-gap">...</span>' : ''}
        ${pages.map((page) => `<button class="mini-btn ${page === pageNumber ? 'is-active' : ''}" type="button" ${attributeName}="${page}">${page}</button>`).join('')}
        ${end < totalPage ? '<span class="table-pagination-gap">...</span>' : ''}
        <button class="mini-btn" type="button" ${attributeName}="${Math.min(totalPage, pageNumber + 1)}" ${pageNumber >= totalPage ? 'disabled' : ''}>&rsaquo;</button>
        <button class="mini-btn" type="button" ${attributeName}="${Math.max(1, totalPage)}" ${pageNumber >= totalPage ? 'disabled' : ''}>&raquo;</button>
      </div>
      <label class="table-pagination-size">
        <span>Sayfa başına satır</span>
        <select ${limitAttribute}>
          <option value="20" ${currentLimit === 20 ? 'selected' : ''}>20</option>
          <option value="50" ${currentLimit === 50 ? 'selected' : ''}>50</option>
          <option value="100" ${currentLimit === 100 ? 'selected' : ''}>100</option>
        </select>
      </label>
    </div>
  `;
}

function renderOgrenciTable(target, rows, paging = {}) {
  target._talyaRows = rows || [];
  const filtered = target._talyaRows;

  if (!filtered.length) {
    target.innerHTML = '<div class="empty-table">Eslesen ogrenci bulunamadi.</div>';
    return;
  }

  const body = filtered.map((row, index) => {
    const whatsappUrl = talyaWhatsappUrl(row.telefon, row.ad_soyad || `${row.ad || ''} ${row.soyad || ''}`.trim());
    return `
    <tr>
      <td>${index + 1}</td>
      <td><a class="table-link" href="/panel/ogrenciler/profil?id=${escapeHtml(row.id)}">${escapeHtml(row.ad_soyad || `${row.ad || ''} ${row.soyad || ''}`.trim())}</a></td>
      <td>${escapeHtml(row.telefon || '-')}</td>
      <td>${escapeHtml(row.veliler || '-')}</td>
      <td>${escapeHtml(row.dogum_tarihi || '-')}</td>
      <td>${escapeHtml(row.kayit_tarihi || '-')}</td>
      <td>
        <span class="status-pill">${escapeHtml(row.durum || '-')}</span>
        ${String(row.kara_liste_aktif || '0') === '1' ? '<span class="status-pill is-danger">Kara Liste</span>' : ''}
      </td>
      <td>
        ${whatsappUrl ? `<a class="btn btn-primary" href="${escapeHtml(whatsappUrl)}" target="_blank" rel="noopener noreferrer">WhatsApp</a>` : ''}
        <button class="btn btn-danger" type="button" data-delete-student="${escapeHtml(row.id)}">Sil</button>
      </td>
    </tr>
  `;
  }).join('');

  target.innerHTML = `
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Ogrenci</th>
          <th>Telefon</th>
          <th>Veli</th>
          <th>Dogum Tarihi</th>
          <th>Kayit Tarihi</th>
          <th>Durum</th>
          <th>Islem</th>
        </tr>
      </thead>
      <tbody>${body}</tbody>
    </table>
    ${renderMiniPagination(paging, 'data-student-page', 'data-student-limit')}
  `;
}

function talyaWhatsappUrl(phone, studentName) {
  let digits = String(phone || '').replace(/\D/g, '');
  if (digits.startsWith('00')) {
    digits = digits.slice(2);
  }
  if (digits.startsWith('90') && digits.length === 12) {
    digits = digits.slice(2);
  }
  if (digits.startsWith('0')) {
    digits = digits.slice(1);
  }
  if (digits.length !== 10) {
    return '';
  }

  const message = `Merhaba, ${studentName || 'öğrencimiz'} hakkında sizinle iletişime geçiyoruz.`;
  return `https://wa.me/90${digits}?text=${encodeURIComponent(message)}`;
}

function waitingParentStatusLabel(value) {
  return {
    bekliyor: 'Bekliyor',
    iletisime_gecildi: 'Iletisime Gecildi',
    kayda_donustu: 'Kayda Donustu',
    iptal: 'Iptal',
  }[value] || value || '-';
}

function waitingParentPreferenceLabel(value) {
  return {
    hafta_ici: 'Hafta ici',
    hafta_sonu: 'Hafta sonu',
    farketmez: 'Fark etmez',
  }[value] || '-';
}

function monthAgeFromBirthDate(value) {
  if (!value) {
    return null;
  }

  const birthDate = new Date(`${value}T00:00:00`);
  if (Number.isNaN(birthDate.getTime())) {
    return null;
  }

  const today = new Date();
  let months = ((today.getFullYear() - birthDate.getFullYear()) * 12)
    + (today.getMonth() - birthDate.getMonth());

  if (today.getDate() < birthDate.getDate()) {
    months -= 1;
  }

  return months >= 0 ? months : null;
}

function waitingParentAgeLabel(row) {
  const backendAge = row.ogrenci_ay_yasi;
  const months = backendAge === null || backendAge === undefined || backendAge === ''
    ? monthAgeFromBirthDate(row.ogrenci_dogum_tarihi)
    : Number(backendAge);

  return Number.isFinite(months) && months >= 0 ? `${months} aylik` : '';
}

function renderBekleyenVeliTable(target, rows) {
  target._talyaRows = rows || [];
  const search = document.querySelector('[data-waiting-parent-search]');
  const query = String(search?.value || '').trim().toLocaleLowerCase('tr-TR');
  const queryDigits = searchablePhone(query);
  const filtered = query
    ? target._talyaRows.filter((row) => {
        const text = [
          row.ogrenci_ad_soyad,
          row.veli_ad_soyad,
          row.beklenen_gun,
          row.ay_grubu,
          row.iletisim_referansi,
          waitingParentAgeLabel(row),
          waitingParentPreferenceLabel(row.zaman_tercihi),
          waitingParentStatusLabel(row.durum),
          row.notlar,
        ].join(' ').toLocaleLowerCase('tr-TR');
        const phone = searchablePhone(row.veli_telefon || '');
        return text.includes(query) || (queryDigits !== '' && phone.includes(queryDigits));
      })
    : target._talyaRows;

  if (!filtered.length) {
    target.innerHTML = '<div class="empty-table">Bekleyen veli kaydi bulunamadi.</div>';
    return;
  }

  const body = filtered.map((row, index) => {
    const ogrenciId = Number(row.ogrenci_id || 0);
    const appointmentUrl = ogrenciId > 0 ? `/panel/paketler/tanimla?ogrenci_id=${encodeURIComponent(String(ogrenciId))}` : '';
    const conversionButton = ogrenciId > 0
      ? `<a class="btn btn-primary" href="${appointmentUrl}">Randevu Olustur</a>`
      : `<button class="btn btn-primary" type="button" data-convert-waiting-parent="${escapeHtml(row.id)}">Aktif Ogrenci Yap</button>`;
    const birthDate = row.ogrenci_dogum_tarihi || '-';
    const ageLabel = waitingParentAgeLabel(row);
    const birthAndAge = ageLabel ? `${birthDate} / ${ageLabel}` : birthDate;

    return `
    <tr>
      <td>${index + 1}</td>
      <td>
        <strong>${escapeHtml(row.ogrenci_ad_soyad || '-')}</strong>
        <small>${escapeHtml(birthAndAge)}</small>
      </td>
      <td>
        <strong>${escapeHtml(row.veli_ad_soyad || '-')}</strong>
        <small>${escapeHtml(row.veli_telefon || '-')}</small>
      </td>
      <td>${escapeHtml(row.beklenen_gun || '-')}</td>
      <td>${escapeHtml(row.ay_grubu || '-')}</td>
      <td>${escapeHtml(row.iletisim_referansi || '-')}</td>
      <td>${escapeHtml(waitingParentPreferenceLabel(row.zaman_tercihi))}</td>
      <td>
        <select data-waiting-parent-status="${escapeHtml(row.id)}">
          <option value="bekliyor" ${row.durum === 'bekliyor' ? 'selected' : ''}>Bekliyor</option>
          <option value="iletisime_gecildi" ${row.durum === 'iletisime_gecildi' ? 'selected' : ''}>Iletisime Gecildi</option>
          <option value="kayda_donustu" ${row.durum === 'kayda_donustu' ? 'selected' : ''}>Kayda Donustu</option>
          <option value="iptal" ${row.durum === 'iptal' ? 'selected' : ''}>Iptal</option>
        </select>
      </td>
      <td>${escapeHtml(row.notlar || '-')}</td>
      <td>
        ${conversionButton}
        <button class="btn btn-ghost" type="button" data-save-waiting-parent-status="${escapeHtml(row.id)}">Kaydet</button>
        <button class="btn btn-danger" type="button" data-delete-waiting-parent="${escapeHtml(row.id)}">Sil</button>
      </td>
    </tr>
  `;
  }).join('');

  target.innerHTML = `
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Ogrenci</th>
          <th>Veli</th>
          <th>Bekledigi Gun</th>
          <th>Ay Grubu</th>
          <th>İletişim Kaynağı</th>
          <th>Tercih</th>
          <th>Durum</th>
          <th>Not</th>
          <th>Islem</th>
        </tr>
      </thead>
      <tbody>${body}</tbody>
    </table>
  `;
}

function renderTable(target, rows, islem = '') {
  if (islem === 'hizmet_listele') {
    renderHizmetTable(target, rows);
    return;
  }
  if (islem === 'ogrenci_listele') {
    renderOgrenciTable(target, rows?.kayitlar || [], rows?.sayfalama || {});
    return;
  }
  if (islem === 'bekleyen_veli_listele') {
    renderBekleyenVeliTable(target, rows);
    return;
  }

  if (!rows || rows.length === 0) {
    target.innerHTML = '<div class="empty-table">Kayit bulunamadi.</div>';
    return;
  }

  const columns = Object.keys(rows[0]);
  const thead = columns.map((column) => `<th>${escapeHtml(column.replaceAll('_', ' '))}</th>`).join('');
  const tbody = rows.map((row) => {
    const cells = columns.map((column) => {
      const value = row[column];
      if (column === 'durum' || column === 'aktif') {
        const label = column === 'aktif' ? (String(value) === '1' ? 'Aktif' : 'Pasif') : value;
        return `<td><span class="status-pill">${escapeHtml(label)}</span></td>`;
      }
      return `<td>${escapeHtml(value)}</td>`;
    }).join('');
    return `<tr>${cells}</tr>`;
  }).join('');

  target.innerHTML = `<table><thead><tr>${thead}</tr></thead><tbody>${tbody}</tbody></table>`;
}

function openDialogElement(target) {
  if (!target) {
    return;
  }
  if (typeof target.showModal === 'function') {
    target.showModal();
    return;
  }
  target.setAttribute('open', 'open');
}

function closeDialogElement(target) {
  if (!target) {
    return;
  }
  if (typeof target.close === 'function') {
    target.close();
    return;
  }
  target.removeAttribute('open');
}

async function loadAjaxTable(element) {
  const islem = element.getAttribute('data-table');
  if (!islem) {
    return;
  }
  const currentPage = Math.max(1, Number(element.dataset.page || '1'));
  element.innerHTML = '<div class="empty-table">Yukleniyor...</div>';
  try {
    const payload = {};
    if (islem === 'ogrenci_listele') {
      payload.arama = document.querySelector('[data-student-search]')?.value || '';
      payload.sayfa = currentPage;
      payload.limit = Number(element.dataset.limit || '20');
    }
    const sonuc = await talyaAjax(islem, payload);
    renderTable(element, sonuc.veri || [], islem);
  } catch (error) {
    element.innerHTML = `<div class="empty-table">${escapeHtml(error.message)}</div>`;
  }
}

document.querySelectorAll('[data-table]').forEach(loadAjaxTable);

let studentSearchTimer = 0;
document.addEventListener('input', (event) => {
  if (!event.target.closest('[data-student-search]')) {
    if (!event.target.closest('[data-waiting-parent-search]')) {
      return;
    }

    const target = document.querySelector('[data-table="bekleyen_veli_listele"]');
    if (target?._talyaRows) {
      renderBekleyenVeliTable(target, target._talyaRows);
    }
    return;
  }
  const target = document.querySelector('[data-table="ogrenci_listele"]');
  if (target) {
    window.clearTimeout(studentSearchTimer);
    studentSearchTimer = window.setTimeout(() => {
      target.dataset.page = '1';
      loadAjaxTable(target);
    }, 250);
  }
});

document.addEventListener('click', (event) => {
  const button = event.target.closest('[data-student-page]');
  if (!button || button.disabled) {
    return;
  }

  const target = button.closest('[data-table]') || document.querySelector('[data-table="ogrenci_listele"]');
  if (!target) {
    return;
  }

  target.dataset.page = String(button.getAttribute('data-student-page') || '1');
  loadAjaxTable(target);
});

document.addEventListener('change', (event) => {
  const select = event.target.closest('[data-student-limit]');
  if (!select) {
    return;
  }

  const target = select.closest('[data-table]') || document.querySelector('[data-table="ogrenci_listele"]');
  if (!target) {
    return;
  }

  target.dataset.limit = String(select.value || '20');
  target.dataset.page = '1';
  loadAjaxTable(target);
});

document.addEventListener('submit', async (event) => {
  const form = event.target.closest('[data-student-create-form]');
  if (!form || form.dataset.phoneChecked === '1') {
    return;
  }

  const phones = Array.from(form.querySelectorAll('[data-phone-mask]'))
    .map((field) => field.value || '')
    .filter((phone) => searchablePhone(phone).length >= 10);
  if (!phones.length) {
    return;
  }

  event.preventDefault();
  event.stopImmediatePropagation();

  const message = form.querySelector('[data-form-message]');
  if (message) {
    message.textContent = 'Telefon kontrol ediliyor...';
  }

  function showDuplicateStudents(matches, phone) {
    const dialog = document.querySelector('[data-duplicate-student-dialog]');
    const list = dialog?.querySelector('[data-duplicate-student-list]');
    if (list) {
      list.innerHTML = matches.map((item) => `
        <a class="duplicate-student-item" href="/panel/ogrenciler/profil?id=${encodeURIComponent(String(item.id))}">
          <strong>${escapeHtml(item.ad_soyad || '-')}</strong>
          <span>${escapeHtml(item.telefon || phone)} · ${escapeHtml(item.veliler || 'Veli bilgisi yok')} · ${escapeHtml(item.durum || '-')}</span>
          <em>Profile Git</em>
        </a>
      `).join('');
    }
    if (message) {
      message.textContent = 'Bu telefon numarasina ait ogrenci kaydi zaten var.';
    }
    openDialogElement(dialog);
  }

  try {
    let matches = [];
    let matchedPhone = phones[0] || '';
    for (const phone of phones) {
      const result = await talyaAjax('ogrenci_telefon_kontrol', { telefon: phone });
      matches = result.veri || [];
      if (matches.length) {
        matchedPhone = phone;
        break;
      }
    }
    if (!matches.length) {
      if (message) {
        message.textContent = '';
      }
      form.dataset.phoneChecked = '1';
      form.requestSubmit();
      return;
    }

    showDuplicateStudents(matches, matchedPhone);
  } catch (error) {
    const matches = error?.veri?.eslesmeler || [];
    if (matches.length) {
      showDuplicateStudents(matches, phones[0] || '');
      return;
    }
    if (message) {
      message.textContent = error.message;
    }
  }
}, true);

document.addEventListener('submit', async (event) => {
  const blacklistForm = event.target.closest('[data-blacklist-form]');
  if (blacklistForm) {
    event.preventDefault();
    const message = blacklistForm.querySelector('[data-blacklist-message]');
    if (message) {
      message.textContent = 'Kaydediliyor...';
    }
    try {
      const result = await talyaAjax('ogrenci_kara_liste_ekle', formValues(blacklistForm));
      if (message) {
        message.textContent = result.mesaj;
      }
      window.location.reload();
    } catch (error) {
      if (message) {
        message.textContent = error.message;
      }
    }
    return;
  }

  const form = event.target.closest('[data-ajax-form]');
  if (!form) {
    return;
  }

  event.preventDefault();
  if (form.dataset.submitting === '1') {
    return;
  }

  const message = form.querySelector('[data-form-message]');
  const islem = form.getAttribute('data-ajax-form');
  const refresh = form.getAttribute('data-refresh');
  const targetSelector = form.getAttribute('data-target');
  const submitButtons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
  let redirecting = false;

  form.dataset.submitting = '1';
  submitButtons.forEach((button) => {
    button.disabled = true;
  });

  if (message) {
    message.textContent = 'Kaydediliyor...';
  }

  try {
    const sonuc = await talyaAjax(islem, formValues(form));
    form.reset();
    if (message) {
      message.textContent = sonuc.mesaj;
    }
    if (refresh && targetSelector) {
      const target = document.querySelector(targetSelector);
      if (target) {
        target.setAttribute('data-table', refresh);
        await loadAjaxTable(target);
      }
    }
    const dialog = form.closest('dialog');
    if (dialog) {
      closeDialogElement(dialog);
    }
    const redirect = form.getAttribute('data-success-redirect');
    if (redirect) {
      redirecting = true;
      if (message) {
        message.textContent = `${sonuc.mesaj} Yonlendiriliyor...`;
      }
      window.location.assign(redirect);
    }
  } catch (error) {
    if (message) {
      message.textContent = error.message;
    }
  } finally {
    if (!redirecting || document.visibilityState === 'visible') {
      form.dataset.submitting = '0';
      submitButtons.forEach((button) => {
        button.disabled = false;
      });
    }
  }
});

document.addEventListener('click', async (event) => {
  const opener = event.target.closest('[data-open-dialog]');
  if (opener) {
    openDialogElement(document.querySelector(opener.getAttribute('data-open-dialog')));
    return;
  }

  const debtPayment = event.target.closest('[data-payment-from-debt]');
  if (debtPayment) {
    const dialog = document.querySelector('#tahsilat-dialog');
    const form = dialog?.querySelector('form');
    if (form) {
      const packageField = form.querySelector('[name="paket_id"]');
      const amountField = form.querySelector('[name="tutar"]');
      if (packageField) {
        packageField.value = debtPayment.getAttribute('data-paket-id') || '';
      }
      if (amountField) {
        amountField.value = debtPayment.getAttribute('data-tutar') || '';
      }
      const packagePriceToggle = form.querySelector('[data-package-price-toggle]');
      if (packagePriceToggle) {
        packagePriceToggle.checked = false;
        packagePriceToggle.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
    openDialogElement(dialog);
    return;
  }

  const debtUnpaidClose = event.target.closest('[data-debt-unpaid-close]');
  if (debtUnpaidClose) {
    const id = Number(debtUnpaidClose.getAttribute('data-paket-id'));
    if (!id) {
      return;
    }
    if (!window.confirm('Bu paketin kalan borcu odeme yapilmadi olarak kapatilacak. Tahsilat veya kasa kaydi olusmayacak. Devam edilsin mi?')) {
      return;
    }
    debtUnpaidClose.disabled = true;
    try {
      await talyaAjax('paket_odeme_yapilmadi_kapat', {
        paket_id: id,
        neden: 'Gelmedigi icin odeme alinmadi.'
      });
      window.location.reload();
    } catch (error) {
      window.alert(error.message);
      debtUnpaidClose.disabled = false;
    }
    return;
  }

  const waitingParentStatus = event.target.closest('[data-save-waiting-parent-status]');
  if (waitingParentStatus) {
    const id = Number(waitingParentStatus.getAttribute('data-save-waiting-parent-status'));
    const statusField = document.querySelector(`[data-waiting-parent-status="${id}"]`);
    const durum = statusField?.value || '';
    waitingParentStatus.disabled = true;
    try {
      await talyaAjax('bekleyen_veli_durum_guncelle', { id, durum });
      const table = waitingParentStatus.closest('[data-table]') || document.querySelector('[data-table="bekleyen_veli_listele"]');
      if (table) {
        await loadAjaxTable(table);
      }
    } catch (error) {
      window.alert(error.message);
      waitingParentStatus.disabled = false;
    }
    return;
  }

  const waitingParentConvert = event.target.closest('[data-convert-waiting-parent]');
  if (waitingParentConvert) {
    const id = Number(waitingParentConvert.getAttribute('data-convert-waiting-parent'));
    if (!window.confirm('Bu bekleyen veli kaydi aktif ogrenciye aktarilsin mi?')) {
      return;
    }

    waitingParentConvert.disabled = true;
    try {
      const response = await talyaAjax('bekleyen_veli_ogrenciye_donustur', { id });
      const ogrenciId = Number(response?.veri?.ogrenci_id || 0);
      const table = waitingParentConvert.closest('[data-table]') || document.querySelector('[data-table="bekleyen_veli_listele"]');
      if (table) {
        await loadAjaxTable(table);
      }
      if (ogrenciId > 0 && window.confirm('Ogrenci aktif kayda alindi. Randevu olusturma ekranina gidilsin mi?')) {
        window.location.href = `/panel/paketler/tanimla?ogrenci_id=${encodeURIComponent(String(ogrenciId))}`;
      }
    } catch (error) {
      window.alert(error.message);
      waitingParentConvert.disabled = false;
    }
    return;
  }

  const waitingParentDelete = event.target.closest('[data-delete-waiting-parent]');
  if (waitingParentDelete) {
    const id = Number(waitingParentDelete.getAttribute('data-delete-waiting-parent'));
    if (!window.confirm('Bekleyen veli kaydi silinsin mi?')) {
      return;
    }
    waitingParentDelete.disabled = true;
    try {
      await talyaAjax('bekleyen_veli_sil', { id });
      const table = waitingParentDelete.closest('[data-table]') || document.querySelector('[data-table="bekleyen_veli_listele"]');
      if (table) {
        await loadAjaxTable(table);
      }
    } catch (error) {
      window.alert(error.message);
      waitingParentDelete.disabled = false;
    }
    return;
  }

  const serviceEdit = event.target.closest('[data-edit-service]');
  if (serviceEdit) {
    const dialog = document.querySelector('#hizmet-duzenle-dialog');
    const form = dialog?.querySelector('form');
    if (form) {
      const set = (name, value) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (field) {
          field.value = value ?? '';
        }
      };
      set('id', serviceEdit.dataset.id);
      set('hizmet_adi', serviceEdit.dataset.name);
      set('ucret', serviceEdit.dataset.price);
      set('haftalik_katilim_sayisi', serviceEdit.dataset.weekly);
      set('toplam_normal_hak', serviceEdit.dataset.normal);
      set('toplam_telafi_hak', serviceEdit.dataset.makeup);
      set('aktif', serviceEdit.dataset.active);
    }
    openDialogElement(dialog);
    return;
  }

  const serviceDelete = event.target.closest('[data-delete-service]');
  if (serviceDelete) {
    const id = Number(serviceDelete.getAttribute('data-delete-service'));
    if (!window.confirm('Bu paket tanimi silinsin mi? Daha once ogrenciye atanmis paketler etkilenmez.')) {
      return;
    }
    try {
      await talyaAjax('hizmet_sil', { id });
      const table = serviceDelete.closest('[data-table]') || document.querySelector('[data-table="hizmet_listele"]');
      if (table) {
        await loadAjaxTable(table);
      }
    } catch (error) {
      window.alert(error.message);
    }
    return;
  }

  const studentDelete = event.target.closest('[data-delete-student]');
  if (studentDelete) {
    const id = Number(studentDelete.getAttribute('data-delete-student'));
    if (!window.confirm('Bu ogrenci ve ogrenciye bagli randevu, paket, odeme ve veli baglantilari silinsin mi?')) {
      return;
    }
    studentDelete.disabled = true;
    try {
      const sonuc = await talyaAjax('ogrenci_sil', { id });
      window.alert(sonuc.mesaj || 'Ogrenci silindi.');
      const table = studentDelete.closest('[data-table]') || document.querySelector('[data-table="ogrenci_listele"]');
      if (table) {
        await loadAjaxTable(table);
      }
    } catch (error) {
      window.alert(error.message);
      studentDelete.disabled = false;
    }
    return;
  }

  const blacklistRemove = event.target.closest('[data-blacklist-remove]');
  if (blacklistRemove) {
    const id = Number(blacklistRemove.getAttribute('data-blacklist-remove'));
    if (!id || !window.confirm('Bu kara liste kaydi kaldirilsin mi?')) {
      return;
    }
    blacklistRemove.disabled = true;
    try {
      await talyaAjax('ogrenci_kara_liste_kaldir', { id });
      window.location.reload();
    } catch (error) {
      window.alert(error.message);
      blacklistRemove.disabled = false;
    }
    return;
  }

  const closer = event.target.closest('[data-close-dialog]');
  if (closer) {
    closeDialogElement(closer.closest('dialog'));
  }
});

document.addEventListener('click', (event) => {
  if (event.target.closest('[data-duplicate-student-close]')) {
    closeDialogElement(event.target.closest('dialog'));
    return;
  }
});

document.addEventListener('click', (event) => {
  const toggle = event.target.closest('[data-toggle-panel]');
  if (!toggle) {
    return;
  }
  const panel = document.querySelector(toggle.getAttribute('data-toggle-panel'));
  if (panel) {
    panel.hidden = !panel.hidden;
  }
});

document.addEventListener('change', (event) => {
  const select = event.target.closest('[data-service-select]');
  if (!select) {
    return;
  }
  const option = select.selectedOptions[0];
  const form = select.closest('form');
  if (!option || !form) {
    return;
  }
  const set = (name, value) => {
    const field = form.querySelector(`[name="${name}"]`);
    if (field && value !== undefined) {
      field.value = value;
    }
  };
  set('liste_fiyati', option.dataset.price);
  set('haftalik_katilim_sayisi', option.dataset.weekly);
  set('haftalik_katilim_sayisi_gosterim', option.dataset.weekly);
  set('toplam_normal_hak', option.dataset.normal);
  set('toplam_telafi_hak', option.dataset.makeup);
  updatePackageAssignmentSchedule(form);
});

document.querySelectorAll('[data-package-assignment-form]').forEach(updatePackageAssignmentSchedule);

function updateQuickAppointmentDay(form) {
  if (!form?.matches('[data-quick-appointment-form]')) {
    return;
  }
  const dateValue = form.querySelector('[data-quick-appointment-date]')?.value || '';
  const dayField = form.querySelector('[data-quick-appointment-day]');
  if (!dayField) {
    return;
  }
  dayField.value = isoWeekday(dateValue) || dayField.value || '1';
}

document.querySelectorAll('[data-quick-appointment-form]').forEach(updateQuickAppointmentDay);

function updateQuickAppointmentDateFromDay(form) {
  if (!form?.matches('[data-quick-appointment-form]')) {
    return;
  }
  const dateField = form.querySelector('[data-quick-appointment-date]');
  const dayField = form.querySelector('[data-quick-appointment-day]');
  if (!dateField || !dayField || !dateField.value || !dayField.value) {
    return;
  }

  const selectedDay = Number(dayField.value);
  const date = new Date(`${dateField.value}T00:00:00`);
  if (Number.isNaN(date.getTime()) || selectedDay < 1 || selectedDay > 7) {
    return;
  }

  const currentDay = Number(isoWeekday(dateField.value));
  const offset = (selectedDay - currentDay + 7) % 7;
  date.setDate(date.getDate() + offset);
  dateField.value = date.toISOString().slice(0, 10);
}

document.addEventListener('change', (event) => {
  const field = event.target.closest('[data-package-assignment-form] [name="baslangic_tarihi"], [data-package-assignment-form] [name="tek_randevu_saati"]');
  if (!field) {
    return;
  }
  updatePackageAssignmentSchedule(field.closest('[data-package-assignment-form]'));
});

document.addEventListener('change', (event) => {
  const field = event.target.closest('[data-quick-appointment-date]');
  if (!field) {
    return;
  }
  updateQuickAppointmentDay(field.closest('[data-quick-appointment-form]'));
});

document.addEventListener('change', (event) => {
  const field = event.target.closest('[data-quick-appointment-day]');
  if (!field) {
    return;
  }
  updateQuickAppointmentDateFromDay(field.closest('[data-quick-appointment-form]'));
});

(function () {
  const panel = document.querySelector('[data-group-fit-panel]');
  if (!panel) {
    return;
  }

  const birthdate = panel.querySelector('[data-group-birthdate]');
  const summary = panel.querySelector('[data-group-age-summary]');
  const results = panel.querySelector('[data-group-fit-results]');
  const dialog = panel.querySelector('[data-group-fit-dialog]');
  const dialogTitle = panel.querySelector('[data-group-fit-dialog-title]');
  const dialogContent = panel.querySelector('[data-group-fit-dialog-content]');
  const dayNames = {
    1: 'Pazartesi',
    2: 'Sali',
    3: 'Carsamba',
    4: 'Persembe',
    5: 'Cuma',
    6: 'Cumartesi',
    7: 'Pazar'
  };

  let groups = [];
  let suitableGroups = [];
  try {
    groups = JSON.parse(panel.getAttribute('data-groups') || '[]');
  } catch (error) {
    groups = [];
  }

  function dateKey(date) {
    return [
      date.getFullYear(),
      String(date.getMonth() + 1).padStart(2, '0'),
      String(date.getDate()).padStart(2, '0')
    ].join('-');
  }

  function monthsOld(value) {
    const date = new Date(`${value}T00:00:00`);
    const today = new Date();
    if (Number.isNaN(date.getTime()) || date > today) {
      return null;
    }

    let months = (today.getFullYear() - date.getFullYear()) * 12 + today.getMonth() - date.getMonth();
    if (today.getDate() < date.getDate()) {
      months -= 1;
    }
    return Math.max(0, months);
  }

  function formatDate(value) {
    if (!value) {
      return '-';
    }
    const [year, month, day] = String(value).split('-');
    return `${day}.${month}.${year}`;
  }

  function formatTime(value) {
    return value ? String(value).slice(0, 5) : '-';
  }

  function capacityLabel(value) {
    if (value === 'dolu') {
      return 'Dolu';
    }
    if (value === 'sinirli') {
      return 'Sinirli';
    }
    return 'Musait';
  }

  function groupStudentsHtml(group) {
    const students = Array.isArray(group.grup_ogrencileri) ? group.grup_ogrencileri : [];
    if (!students.length) {
      return '<div class="group-fit-detail-empty">Bu grupta aktif ogrenci bulunamadi.</div>';
    }

    return `
      <div class="group-fit-detail-list">
        ${students.map((student) => `
          <div class="group-fit-detail-item">
            <strong>${escapeHtml(student.ogrenci || '-')}</strong>
            <span>${escapeHtml(student.paket_adi || 'Aktif paket yok')}</span>
            <b>${escapeHtml(formatDate(student.bitis_tarihi))}</b>
            <small>Ders: ${escapeHtml(student.kalan_ders ?? '-')} / Telafi: ${escapeHtml(student.kalan_telafi ?? '-')}</small>
          </div>
        `).join('')}
      </div>
    `;
  }

  function earliestCellHtml(group, index) {
    const students = Array.isArray(group.grup_ogrencileri) ? group.grup_ogrencileri : [];
    const isFull = group.kontenjan_durumu === 'dolu';
    const label = isFull ? formatDate(group.en_erken_musait_tarih) : 'Bugun';
    const student = isFull && group.en_erken_ogrenci ? `<small>${escapeHtml(group.en_erken_ogrenci)}</small>` : '';
    const button = students.length
      ? `<button class="mini-btn group-fit-more" type="button" data-group-fit-toggle="${escapeHtml(index)}">Digerleri</button>`
      : '';

    return `<div class="group-fit-earliest"><span><strong>${escapeHtml(label)}</strong>${student}</span>${button}</div>`;
  }

  function openGroupFitDialog(group) {
    if (!dialog || !dialogContent) {
      return;
    }
    if (dialogTitle) {
      dialogTitle.textContent = `${group.program_adi || 'Grup'} - ${dayNames[group.gun] || '-'} ${formatTime(group.baslangic_saati)}`;
    }
    dialogContent.innerHTML = groupStudentsHtml(group);
    if (typeof dialog.showModal === 'function') {
      dialog.showModal();
      return;
    }
    dialog.setAttribute('open', 'open');
  }

  function closeGroupFitDialog() {
    if (!dialog) {
      return;
    }
    if (typeof dialog.close === 'function') {
      dialog.close();
      return;
    }
    dialog.removeAttribute('open');
  }

  function render() {
    const age = monthsOld(birthdate.value);
    if (age === null) {
      summary.textContent = 'Dogum tarihi giriniz.';
      results.innerHTML = '<div class="empty-table">Uygun grup aramak icin dogum tarihi girin.</div>';
      return;
    }

    const suitable = groups
      .filter((group) => {
        const min = Number(group.yas_min_ay);
        const max = Number(group.yas_max_ay);
        return Number.isFinite(min) && Number.isFinite(max) && age >= min && age <= max;
      })
      .sort((a, b) => {
        const aFull = a.kontenjan_durumu === 'dolu' ? 1 : 0;
        const bFull = b.kontenjan_durumu === 'dolu' ? 1 : 0;
        if (aFull !== bFull) {
          return aFull - bFull;
        }
        return String(a.en_erken_musait_tarih || dateKey(new Date())).localeCompare(String(b.en_erken_musait_tarih || dateKey(new Date())));
      });
    suitableGroups = suitable;

    summary.textContent = `Ogrenci ${age} aylik. ${suitable.length} uygun grup bulundu.`;
    if (suitable.length === 0) {
      results.innerHTML = '<div class="empty-table">Bu yas araligina uygun grup bulunamadi.</div>';
      return;
    }

    const rows = suitable.map((group, index) => `
      <tr>
        <td>${escapeHtml(dayNames[group.gun] || '-')}</td>
        <td>${escapeHtml(formatTime(group.baslangic_saati))}</td>
        <td>${escapeHtml(group.program_adi || '-')}</td>
        <td>${escapeHtml(group.yas_araligi || '-')}</td>
        <td>${escapeHtml(group.ogrenci_sayisi)} / ${escapeHtml(group.kontenjan)}</td>
        <td><span class="status-pill capacity-${escapeHtml(group.kontenjan_durumu)}">${escapeHtml(capacityLabel(group.kontenjan_durumu))}</span></td>
        <td>${earliestCellHtml(group, index)}</td>
      </tr>
    `).join('');

    results.innerHTML = `
      <table>
        <thead><tr><th>Gun</th><th>Saat</th><th>Grup</th><th>Yas</th><th>Kontenjan</th><th>Durum</th><th>En Erken</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    `;
  }

  results.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-group-fit-toggle]');
    if (!toggle) {
      return;
    }
    const key = toggle.getAttribute('data-group-fit-toggle');
    const group = suitableGroups[Number(key)];
    if (!group) {
      return;
    }
    openGroupFitDialog(group);
  });

  panel.addEventListener('click', (event) => {
    if (event.target.closest('[data-group-fit-dialog-close]')) {
      closeGroupFitDialog();
    }
  });

  birthdate?.addEventListener('change', render);
  render();
})();

(function () {
  const calendar = document.querySelector('[data-renewal-calendar]');
  if (!calendar) {
    return;
  }

  const source = (() => {
    try {
      return JSON.parse(calendar.getAttribute('data-renewals') || '[]');
    } catch (error) {
      return [];
    }
  })();

  function parseDate(value) {
    return new Date(`${value}T00:00:00`);
  }

  function dateKey(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  const today = calendar.getAttribute('data-today') || dateKey(new Date());
  let range = 7;
  let customStart = '';
  let customEnd = '';

  function addDays(value, count) {
    const date = parseDate(value);
    date.setDate(date.getDate() + count);
    return dateKey(date);
  }

  function diffDays(start, end) {
    const startDate = parseDate(start);
    const endDate = parseDate(end);
    if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
      return 0;
    }
    return Math.floor((endDate - startDate) / 86400000);
  }

  function formatDate(value) {
    const date = parseDate(value);
    if (Number.isNaN(date.getTime())) {
      return value;
    }
    return date.toLocaleDateString('tr-TR', { day: '2-digit', month: 'long', weekday: 'short' });
  }

  function money(value) {
    return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(Number(value || 0));
  }

  function renewalStatus(value) {
    const labels = {
      odeme_alindi: 'Odeme alindi',
      kismi_odeme: 'Kismi odeme',
      odeme_bekliyor: 'Odeme alinmadi'
    };
    return labels[value] || value || 'Odeme alinmadi';
  }

  function render() {
    const startDate = customStart || today;
    const dayCount = customStart && customEnd ? Math.max(1, Math.min(365, diffDays(customStart, customEnd) + 1)) : range;
    const grouped = {};
    source.forEach((row) => {
      grouped[row.tarih] = grouped[row.tarih] || [];
      grouped[row.tarih].push(row);
    });

    let total = 0;
    const cells = [];
    for (let i = 0; i < dayCount; i += 1) {
      const date = addDays(startDate, i);
      const rows = grouped[date] || [];
      const dayTotal = rows.reduce((sum, row) => sum + Number(row.yenileme_ucreti || 0), 0);
      total += dayTotal;
      cells.push(`
        <article class="renewal-day ${dayTotal > 0 ? 'has-balance' : ''}">
          <div class="renewal-day-head">
            <strong>${escapeHtml(formatDate(date))}</strong>
            <span>${escapeHtml(money(dayTotal))}</span>
          </div>
          <div class="renewal-day-list">
            ${rows.length ? rows.map((row) => `
              <div>
                <b>${Number(row.ogrenci_id || 0) > 0
                  ? `<a class="renewal-student-link" href="/panel/ogrenciler/profil?id=${encodeURIComponent(row.ogrenci_id)}">${escapeHtml(row.ogrenci)}</a>`
                  : escapeHtml(row.ogrenci)}</b>
                <small>${escapeHtml(row.paket_adi)} - ${escapeHtml(money(row.yenileme_ucreti))}</small>
                <small>Kalan Ders: ${escapeHtml(row.kalan_normal_hak ?? '-')} / Telafi Hakkı: ${escapeHtml(row.kalan_telafi_hak ?? '-')}</small>
                <i>${escapeHtml(renewalStatus(row.odeme_durumu))}</i>
              </div>
            `).join('') : '<em>Beklenen yenileme yok.</em>'}
          </div>
        </article>
      `);
    }

    calendar.innerHTML = `
      <div class="renewal-calendar-summary">
        <span>${customStart && customEnd ? `${escapeHtml(formatDate(customStart))} - ${escapeHtml(formatDate(customEnd))}` : `${range} gunluk`} beklenen yenileme bakiyesi</span>
        <strong>${escapeHtml(money(total))}</strong>
      </div>
      <div class="renewal-calendar-grid">${cells.join('')}</div>
    `;
  }

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-renewal-range]');
    if (!button) {
      return;
    }
    range = Number(button.getAttribute('data-renewal-range') || 7);
    customStart = '';
    customEnd = '';
    document.querySelectorAll('[data-renewal-range]').forEach((item) => item.classList.remove('is-active'));
    button.classList.add('is-active');
    render();
  });

  document.addEventListener('click', (event) => {
    if (!event.target.closest('[data-renewal-custom-apply]')) {
      return;
    }
    const start = document.querySelector('[data-renewal-start]')?.value || '';
    const end = document.querySelector('[data-renewal-end]')?.value || '';
    if (!start || !end) {
      return;
    }
    customStart = start <= end ? start : end;
    customEnd = start <= end ? end : start;
    document.querySelectorAll('[data-renewal-range]').forEach((item) => item.classList.remove('is-active'));
    render();
  });

  render();
})();
