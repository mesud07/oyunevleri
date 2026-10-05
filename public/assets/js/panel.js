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

function updateMonthlyPackageRights(form) {
  if (!form?.matches('[data-package-assignment-form]')) return;
  const monthly = form.querySelector('[name="hak_hesaplama_turu"]')?.value === 'aylik_takvim';
  const note = form.querySelector('[data-monthly-calendar-note]');
  if (note) note.hidden = !monthly;
  if (!monthly) return;
  const dateValue = form.querySelector('[name="baslangic_tarihi"]')?.value || '';
  const date = new Date(`${dateValue}T00:00:00`);
  if (Number.isNaN(date.getTime())) return;
  const selectedDays = new Set(Array.from(form.querySelectorAll('[name="program_gunleri[]"]:checked')).map((field) => Number(field.value)));
  const year = date.getFullYear();
  const month = date.getMonth();
  const lastDay = new Date(year, month + 1, 0).getDate();
  let count = 0;
  for (let day = 1; day <= lastDay; day++) {
    const weekday = new Date(year, month, day).getDay() || 7;
    if (selectedDays.has(weekday)) count++;
  }
  const rights = form.querySelector('[name="toplam_normal_hak"]');
  if (rights) rights.value = String(count);
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

  updateMonthlyPackageRights(form);
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

(() => {
  const calendar = document.querySelector('[data-debt-payment-calendar]');
  if (!calendar) return;

  const sourceElement = calendar.querySelector('[data-debt-calendar-source]');
  const grid = calendar.querySelector('[data-debt-calendar-grid]');
  const monthLabel = calendar.querySelector('[data-debt-calendar-month]');
  const detail = calendar.querySelector('[data-debt-calendar-detail]');
  let source = [];
  try {
    source = JSON.parse(sourceElement?.textContent || '[]');
  } catch (error) {
    source = [];
  }

  const grouped = source.reduce((result, row) => {
    const key = String(row.tarih || '');
    if (!key) return result;
    result[key] = result[key] || [];
    result[key].push(row);
    return result;
  }, {});

  const isoDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };
  const parseDate = (value) => {
    const [year, month, day] = String(value || '').split('-').map(Number);
    return year && month && day ? new Date(year, month - 1, day) : null;
  };
  const formatDate = (value) => {
    const date = parseDate(value);
    return date ? new Intl.DateTimeFormat('tr-TR', { day: '2-digit', month: 'long', year: 'numeric' }).format(date) : value;
  };

  const firstExpectedDate = parseDate(source[0]?.tarih);
  const now = new Date();
  let cursor = new Date(
    firstExpectedDate?.getFullYear() ?? now.getFullYear(),
    firstExpectedDate?.getMonth() ?? now.getMonth(),
    1
  );
  let selectedDate = source[0]?.tarih || '';

  function renderDetail(dateKey) {
    const rows = grouped[dateKey] || [];
    if (!rows.length) {
      detail.innerHTML = '<div class="empty-table">Bu gün için beklenen ödeme bulunmuyor.</div>';
      return;
    }
    const total = rows.reduce((sum, row) => sum + Number(row.kalan_borc || 0), 0);
    detail.innerHTML = `
      <div class="debt-calendar-detail-head">
        <h3>${escapeHtml(formatDate(dateKey))}</h3>
        <strong>${escapeHtml(formatMoney(total))}</strong>
      </div>
      ${rows.map((row) => `
        <article class="debt-calendar-payment">
          <header><strong>${escapeHtml(row.ogrenci || '-')}</strong><span>${escapeHtml(formatMoney(row.kalan_borc))}</span></header>
          <small>${escapeHtml(row.paket_adi || '-')}</small>
          ${row.tahsilat_notu ? `<p>${escapeHtml(row.tahsilat_notu).replaceAll('\n', '<br>')}</p>` : ''}
        </article>
      `).join('')}
    `;
  }

  function renderCalendar() {
    monthLabel.textContent = new Intl.DateTimeFormat('tr-TR', { month: 'long', year: 'numeric' }).format(cursor);
    const monthStart = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
    const offset = (monthStart.getDay() + 6) % 7;
    const gridStart = new Date(cursor.getFullYear(), cursor.getMonth(), 1 - offset);
    const monthPrefix = `${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, '0')}-`;
    if (!selectedDate.startsWith(monthPrefix)) {
      selectedDate = Object.keys(grouped).find((key) => key.startsWith(monthPrefix)) || '';
    }

    const cells = [];
    for (let index = 0; index < 42; index += 1) {
      const date = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + index);
      const key = isoDate(date);
      const rows = grouped[key] || [];
      const total = rows.reduce((sum, row) => sum + Number(row.kalan_borc || 0), 0);
      const classes = [
        'debt-calendar-day',
        date.getMonth() !== cursor.getMonth() ? 'is-outside' : '',
        key === isoDate(now) ? 'is-today' : '',
        rows.length ? 'has-payment' : '',
        key === selectedDate ? 'is-selected' : ''
      ].filter(Boolean).join(' ');
      const content = `<strong>${date.getDate()}</strong>${rows.length ? `<span>${rows.length} ödeme<br>${escapeHtml(formatMoney(total))}</span>` : ''}`;
      cells.push(rows.length
        ? `<button type="button" class="${classes}" data-debt-calendar-date="${key}">${content}</button>`
        : `<div class="${classes}">${content}</div>`);
    }
    grid.innerHTML = cells.join('');
    renderDetail(selectedDate);
  }

  calendar.addEventListener('click', (event) => {
    const day = event.target.closest('[data-debt-calendar-date]');
    if (day) {
      selectedDate = day.getAttribute('data-debt-calendar-date') || '';
      const selected = parseDate(selectedDate);
      if (selected && (selected.getFullYear() !== cursor.getFullYear() || selected.getMonth() !== cursor.getMonth())) {
        cursor = new Date(selected.getFullYear(), selected.getMonth(), 1);
      }
      renderCalendar();
      return;
    }
    if (event.target.closest('[data-debt-calendar-prev]')) {
      cursor = new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1);
      renderCalendar();
      return;
    }
    if (event.target.closest('[data-debt-calendar-next]')) {
      cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
      renderCalendar();
    }
  });

  renderCalendar();
})();

document.querySelector('[data-waiting-parent-history-form]')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const dialog = form.closest('[data-waiting-parent-history-dialog]');
  const message = dialog.querySelector('[data-waiting-parent-history-message]');
  const button = form.querySelector('button[type="submit"]');
  button.disabled = true;
  message.textContent = 'Kaydediliyor...';
  try {
    const payload = formValues(form);
    const response = await talyaAjax('bekleyen_veli_gorusme_ekle', payload);
    message.textContent = response.mesaj;
    const id = Number(payload.bekleyen_veli_id);
    form.elements.ozet.value = '';
    form.elements.sonraki_takip_tarihi.value = '';
    form.elements.gorusme_tarihi.value = localDateTimeInputValue();
    await loadWaitingParentHistory(id, dialog);
    const table = document.querySelector('[data-table="bekleyen_veli_listele"]');
    if (table) await loadAjaxTable(table);
  } catch (error) {
    message.textContent = error.message;
  } finally {
    button.disabled = false;
  }
});

document.querySelector('[data-waiting-parent-groups-form]')?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const form = event.currentTarget;
  const dialog = form.closest('[data-waiting-parent-history-dialog]');
  const message = dialog.querySelector('[data-waiting-parent-groups-message]');
  const button = form.querySelector('button[type="submit"]');
  button.disabled = true;
  try {
    const payload = formValues(form);
    payload.grup_ids = Array.from(form.querySelectorAll('[name="grup_ids[]"]:checked')).map((input) => Number(input.value));
    const response = await talyaAjax('bekleyen_veli_gruplari_guncelle', payload);
    message.textContent = response.mesaj;
    await loadWaitingParentHistory(Number(payload.bekleyen_veli_id), dialog);
    const table = document.querySelector('[data-table="bekleyen_veli_listele"]');
    if (table) await loadAjaxTable(table);
  } catch (error) {
    message.textContent = error.message;
  } finally {
    button.disabled = false;
  }
});

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
      <td>${row.kdv_orani === null || row.kdv_orani === '' ? 'Tanimlanmamis' : `%${escapeHtml(row.kdv_orani)}`}</td>
      <td>${escapeHtml(row.haftalik_katilim_sayisi)}</td>
      <td>${escapeHtml(row.toplam_normal_hak)}</td>
      <td>${escapeHtml(row.toplam_telafi_hak)}</td>
      <td>${row.hak_hesaplama_turu === 'aylik_takvim' ? 'Aylık takvim' : 'Sabit'}</td>
      <td><span class="status-pill">${String(row.aktif) === '1' ? 'Aktif' : 'Pasif'}</span></td>
      <td>
        <button
          class="btn btn-ghost"
          type="button"
          data-edit-service
          data-id="${escapeHtml(row.id)}"
          data-name="${escapeHtml(row.hizmet_adi)}"
          data-price="${escapeHtml(row.ucret)}"
          data-vat="${escapeHtml(row.kdv_orani)}"
          data-weekly="${escapeHtml(row.haftalik_katilim_sayisi)}"
          data-normal="${escapeHtml(row.toplam_normal_hak)}"
          data-makeup="${escapeHtml(row.toplam_telafi_hak)}"
          data-calendar-mode="${escapeHtml(row.hak_hesaplama_turu || 'sabit')}"
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
          <th>KDV</th>
          <th>Haftalik Katilim</th>
          <th>Normal Hak</th>
          <th>Telafi Hakki</th>
          <th>Hak Hesaplama</th>
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
  const canEditStudent = target.dataset.canEditStudent === '1';

  if (!filtered.length) {
    target.innerHTML = '<div class="empty-table">Eslesen ogrenci bulunamadi.</div>';
    return;
  }

  const body = filtered.map((row, index) => `
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
        ${canEditStudent ? `
          <div class="table-row-actions">
            <button class="btn btn-ghost" type="button" data-quick-edit-student="${escapeHtml(row.id)}">Hizli Duzenle</button>
            <button class="btn btn-danger" type="button" data-delete-student="${escapeHtml(row.id)}">Sil</button>
          </div>
        ` : '<span>-</span>'}
      </td>
    </tr>
  `).join('');

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

function waitingParentStatusLabel(value) {
  return {
    bekliyor: 'Bekliyor',
    iletisime_gecildi: 'Iletisime Gecildi',
    bilgi_verildi: 'Bilgi Verildi',
    ulasilamadi: 'Ulaşılamadı',
    katilmadi: 'Katılmadı',
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

function waitingParentDaysLabel(value) {
  const days = Math.max(0, Number(value || 0));
  if (days === 0) {
    return 'Bugün';
  }
  return `${days} gün`;
}

function waitingParentSortValue(row, key) {
  const values = {
    student: row.ogrenci_ad_soyad,
    parent: `${row.veli_ad_soyad || ''} ${row.veli_telefon || ''}`,
    age_group: row.ay_grubu,
    groups: row.grup_adlari,
    preference: waitingParentPreferenceLabel(row.zaman_tercihi),
    days: Number(row.listeye_ekleneli_gun || 0),
    status: waitingParentStatusLabel(row.durum),
    note: row.notlar,
    last_contact: row.son_gorusme_tarihi || '',
  };
  return values[key] ?? '';
}

function waitingParentSortHeader(target, key, label) {
  const sort = target._talyaSort || {};
  const active = sort.key === key;
  const direction = active ? sort.direction : '';
  const icon = direction === 'asc' ? '↑' : (direction === 'desc' ? '↓' : '↕');
  const ariaSort = direction === 'asc' ? 'ascending' : (direction === 'desc' ? 'descending' : 'none');
  return `<th aria-sort="${ariaSort}"><button class="waiting-parent-sort" type="button" data-waiting-parent-sort="${key}" title="Sıralamak için tıklayın">${label}<span aria-hidden="true">${icon}</span></button></th>`;
}

function renderBekleyenVeliTable(target, rows) {
  target._talyaRows = rows || [];
  const waitingRows = target._talyaRows.filter((row) => !['kayda_donustu', 'iptal'].includes(row.durum));
  const convertedRows = target._talyaRows.filter((row) => row.durum === 'kayda_donustu');
  document.querySelectorAll('[data-waiting-parent-count]').forEach((element) => {
    const type = element.dataset.waitingParentCount;
    element.textContent = String(type === 'waiting' ? waitingRows.length : (type === 'converted' ? convertedRows.length : target._talyaRows.length));
  });

  const activeView = document.querySelector('[data-waiting-parent-view].is-active')?.dataset.waitingParentView || 'waiting';
  const visibleRows = activeView === 'converted'
    ? convertedRows
    : (activeView === 'all' ? target._talyaRows : waitingRows);
  const search = document.querySelector('[data-waiting-parent-search]');
  const query = String(search?.value || '').trim().toLocaleLowerCase('tr-TR');
  const queryDigits = searchablePhone(query);
  let filtered = query
    ? visibleRows.filter((row) => {
        const text = [
          row.ogrenci_ad_soyad,
          row.veli_ad_soyad,
          row.ay_grubu,
          waitingParentAgeLabel(row),
          waitingParentPreferenceLabel(row.zaman_tercihi),
          waitingParentStatusLabel(row.durum),
          row.notlar,
          row.son_gorusme_ozeti,
          row.grup_adlari,
        ].join(' ').toLocaleLowerCase('tr-TR');
        const phone = searchablePhone(row.veli_telefon || '');
        return text.includes(query) || (queryDigits !== '' && phone.includes(queryDigits));
      })
    : visibleRows;

  const sort = target._talyaSort || {};
  if (sort.key && ['asc', 'desc'].includes(sort.direction)) {
    filtered = [...filtered].sort((left, right) => {
      const leftValue = waitingParentSortValue(left, sort.key);
      const rightValue = waitingParentSortValue(right, sort.key);
      const comparison = typeof leftValue === 'number' && typeof rightValue === 'number'
        ? leftValue - rightValue
        : String(leftValue).localeCompare(String(rightValue), 'tr-TR', { numeric: true, sensitivity: 'base' });
      return sort.direction === 'desc' ? -comparison : comparison;
    });
  }

  if (!filtered.length) {
    const emptyLabel = activeView === 'converted' ? 'Aktif kayda dönüşen veli bulunamadı.' : 'Bekleyen veli kaydı bulunamadı.';
    target.innerHTML = `<div class="empty-table">${emptyLabel}</div>`;
    return;
  }

  const body = filtered.map((row, index) => {
    const ogrenciId = Number(row.ogrenci_id || 0);
    const appointmentUrl = ogrenciId > 0 ? `/panel/paketler/tanimla?ogrenci_id=${encodeURIComponent(String(ogrenciId))}` : '';
    const conversionButton = ogrenciId > 0
      ? `<a class="waiting-parent-action-icon is-primary" href="${appointmentUrl}" title="Randevu oluştur" aria-label="Randevu oluştur">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm7 7v5m-2.5-2.5h5"/></svg>
        </a>`
      : `<button class="waiting-parent-action-icon is-primary" type="button" data-convert-waiting-parent="${escapeHtml(row.id)}" title="Aktif öğrenci yap" aria-label="Aktif öğrenci yap">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 19a6 6 0 0 0-12 0m6-8a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm9-4v6m-3-3h6"/></svg>
        </button>`;
    const editButton = !['kayda_donustu', 'iptal'].includes(row.durum)
      ? `<button class="waiting-parent-action-icon" type="button" data-edit-waiting-parent="${escapeHtml(row.id)}" title="Bilgileri düzenle" aria-label="Bilgileri düzenle">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4l11-11a2.8 2.8 0 0 0-4-4L4 16v4Zm9.5-13.5 4 4"/></svg>
        </button>`
      : (ogrenciId > 0
        ? `<a class="waiting-parent-action-icon" href="/panel/ogrenciler/profil?id=${encodeURIComponent(String(ogrenciId))}" title="Aktif öğrenci profilini düzenle" aria-label="Aktif öğrenci profilini düzenle">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4l11-11a2.8 2.8 0 0 0-4-4L4 16v4Zm9.5-13.5 4 4"/></svg>
          </a>`
        : '');
    const birthDate = row.ogrenci_dogum_tarihi || '-';
    const ageLabel = waitingParentAgeLabel(row);
    const birthAndAge = ageLabel ? `${birthDate} / ${ageLabel}` : birthDate;
    const lastContact = row.son_gorusme_tarihi
      ? `${escapeHtml(String(row.son_gorusme_tarihi).slice(0, 16))}<small>${escapeHtml(row.son_gorusme_ozeti || '-')}</small>`
      : '<span class="muted">Henüz görüşme yok</span>';
    const followUp = row.sonraki_takip_tarihi ? `<small class="waiting-follow-up">Takip: ${escapeHtml(row.sonraki_takip_tarihi)}</small>` : '';

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
      <td>${escapeHtml(row.ay_grubu || '-')}</td>
      <td>${escapeHtml(row.grup_adlari || 'Grup seçilmedi')}</td>
      <td>${escapeHtml(waitingParentPreferenceLabel(row.zaman_tercihi))}</td>
      <td>
        <strong class="waiting-parent-days">${escapeHtml(waitingParentDaysLabel(row.listeye_ekleneli_gun))}</strong>
        <small>${escapeHtml(String(row.olusturulma_tarihi || '-').slice(0, 10))}</small>
      </td>
      <td>
        <select data-waiting-parent-status="${escapeHtml(row.id)}" data-saved-status="${escapeHtml(row.durum || 'bekliyor')}" aria-label="${escapeHtml(row.ogrenci_ad_soyad || 'Öğrenci')} durumunu değiştir">
          <option value="bekliyor" ${row.durum === 'bekliyor' ? 'selected' : ''}>Bekliyor</option>
          <option value="iletisime_gecildi" ${row.durum === 'iletisime_gecildi' ? 'selected' : ''}>İletişime Geçildi</option>
          <option value="bilgi_verildi" ${row.durum === 'bilgi_verildi' ? 'selected' : ''}>Bilgi Verildi</option>
          <option value="ulasilamadi" ${row.durum === 'ulasilamadi' ? 'selected' : ''}>Ulaşılamadı</option>
          <option value="katilmadi" ${row.durum === 'katilmadi' ? 'selected' : ''}>Katılmadı</option>
          <option value="kayda_donustu" ${row.durum === 'kayda_donustu' ? 'selected' : ''}>Kayda Dönüştü</option>
          <option value="iptal" ${row.durum === 'iptal' ? 'selected' : ''}>İptal</option>
        </select>
      </td>
      <td>${escapeHtml(row.notlar || '-')}</td>
      <td>${lastContact}${followUp}</td>
      <td class="waiting-parent-actions-cell">
        <div class="waiting-parent-row-actions">
          ${editButton}
          <button class="waiting-parent-action-icon" type="button" data-waiting-parent-history="${escapeHtml(row.id)}" title="Detay ve görüşmeler (${escapeHtml(row.gorusme_sayisi || 0)})" aria-label="Detay ve görüşmeler">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v4l3 2m6-2a9 9 0 1 1-3-6.7M18 2v4h4"/></svg>
          </button>
          ${conversionButton}
          <button class="waiting-parent-action-icon is-danger" type="button" data-delete-waiting-parent="${escapeHtml(row.id)}" title="Kaydı sil" aria-label="Kaydı sil">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m3 0-1 13H7L6 7"/></svg>
          </button>
        </div>
      </td>
    </tr>
  `;
  }).join('');

  target.innerHTML = `
    <table class="waiting-parent-table">
      <thead>
        <tr>
          <th>#</th>
          ${waitingParentSortHeader(target, 'student', 'Öğrenci')}
          ${waitingParentSortHeader(target, 'parent', 'Veli')}
          ${waitingParentSortHeader(target, 'age_group', 'Ay Grubu')}
          ${waitingParentSortHeader(target, 'groups', 'Beklediği Gruplar')}
          ${waitingParentSortHeader(target, 'preference', 'Tercih')}
          ${waitingParentSortHeader(target, 'days', 'Listeye Ekleneli')}
          ${waitingParentSortHeader(target, 'status', 'Durum')}
          ${waitingParentSortHeader(target, 'note', 'Not')}
          ${waitingParentSortHeader(target, 'last_contact', 'Son Görüşme')}
          <th>İşlem</th>
        </tr>
      </thead>
      <tbody>${body}</tbody>
    </table>
  `;
}

const waitingParentChannelLabels = {
  telefon: 'Telefon', whatsapp: 'WhatsApp', yuz_yuze: 'Yüz yüze', sms: 'SMS', diger: 'Diğer'
};
const waitingParentResultLabels = {
  bilgi_verildi: 'Bilgi verildi', tekrar_aranacak: 'Tekrar aranacak', randevu_planlandi: 'Randevu planlandı',
  kararsiz: 'Kararsız', ulasilamadi: 'Ulaşılamadı', katilmadi: 'Katılmadı', olumsuz: 'Olumsuz', diger: 'Diğer'
};

function localDateTimeInputValue(date = new Date()) {
  const offset = date.getTimezoneOffset();
  return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 16);
}

function renderWaitingParentHistory(dialog, rows) {
  const target = dialog.querySelector('[data-waiting-parent-history-list]');
  if (!rows?.length) {
    target.innerHTML = '<div class="empty-table">Henüz görüşme kaydı bulunmuyor.</div>';
    return;
  }
  target.innerHTML = rows.map((row) => `
    <article class="waiting-parent-history-item">
      <div class="waiting-parent-history-meta">
        <strong>${escapeHtml(String(row.gorusme_tarihi || '').slice(0, 16))}</strong>
        <span>${escapeHtml(waitingParentChannelLabels[row.kanal] || row.kanal)}</span>
        <span>${escapeHtml(waitingParentResultLabels[row.sonuc] || row.sonuc)}</span>
      </div>
      <p>${escapeHtml(row.ozet || '').replaceAll('\n', '<br>')}</p>
      <footer><span>Kaydeden: ${escapeHtml(row.kaydeden || '-')}</span>${row.sonraki_takip_tarihi ? `<strong>Takip: ${escapeHtml(row.sonraki_takip_tarihi)}</strong>` : ''}</footer>
    </article>
  `).join('');
}

async function loadWaitingParentHistory(id, dialog) {
  const list = dialog.querySelector('[data-waiting-parent-history-list]');
  list.innerHTML = '<div class="empty-table">Görüşmeler yükleniyor...</div>';
  const response = await talyaAjax('bekleyen_veli_gorusmeleri', {id});
  const data = response.veri || {};
  dialog.querySelector('[data-waiting-parent-history-title]').textContent = `${data.veli?.veli_ad_soyad || '-'} · ${data.veli?.ogrenci_ad_soyad || '-'}`;
  renderWaitingParentHistory(dialog, data.gorusmeler || []);
  const groupForm = dialog.querySelector('[data-waiting-parent-groups-form]');
  if (groupForm) {
    groupForm.elements.bekleyen_veli_id.value = String(id);
    dialog.querySelector('[data-waiting-parent-group-options]').innerHTML = (data.gruplar || []).length
      ? data.gruplar.map((grup) => {
        const programlar = Array.isArray(grup.programlar) ? grup.programlar : [];
        const programMetni = programlar.length
          ? programlar.map((program) => `${program.gun_adi || '-'} ${program.baslangic_saati || '-'}–${program.bitis_saati || '-'}`).join(' · ')
          : 'Program saati tanımlı değil';
        return `<label><input type="checkbox" name="grup_ids[]" value="${escapeHtml(grup.id)}" ${Number(grup.secili) === 1 ? 'checked' : ''}><span><strong>${escapeHtml(grup.ad)}</strong><small>${escapeHtml(grup.yas_araligi || '-')}</small><small class="waiting-parent-group-schedule">${escapeHtml(programMetni)}</small></span></label>`;
      }).join('')
      : '<div class="empty-table">Aktif grup bulunamadı.</div>';
  }
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
  const viewButton = event.target.closest('[data-waiting-parent-view]');
  if (!viewButton) {
    return;
  }
  document.querySelectorAll('[data-waiting-parent-view]').forEach((button) => {
    button.classList.toggle('is-active', button === viewButton);
  });
  const target = document.querySelector('[data-table="bekleyen_veli_listele"]');
  if (target?._talyaRows) {
    renderBekleyenVeliTable(target, target._talyaRows);
  }
});

document.addEventListener('click', (event) => {
  const sortButton = event.target.closest('[data-waiting-parent-sort]');
  if (!sortButton) {
    return;
  }
  const target = sortButton.closest('[data-table="bekleyen_veli_listele"]');
  if (!target?._talyaRows) {
    return;
  }
  const key = sortButton.dataset.waitingParentSort || '';
  const current = target._talyaSort || {};
  target._talyaSort = current.key !== key
    ? { key, direction: 'asc' }
    : (current.direction === 'asc' ? { key, direction: 'desc' } : {});
  renderBekleyenVeliTable(target, target._talyaRows);
});

document.addEventListener('change', async (event) => {
  const statusField = event.target.closest('[data-waiting-parent-status]');
  if (!statusField || statusField.disabled) {
    return;
  }

  const id = Number(statusField.getAttribute('data-waiting-parent-status'));
  const durum = statusField.value || '';
  const eskiDurum = statusField.dataset.savedStatus || 'bekliyor';
  if (!id || !durum || durum === eskiDurum) {
    return;
  }

  statusField.disabled = true;
  statusField.classList.add('is-saving');
  try {
    await talyaAjax('bekleyen_veli_durum_guncelle', { id, durum });
    statusField.dataset.savedStatus = durum;
    const table = statusField.closest('[data-table]') || document.querySelector('[data-table="bekleyen_veli_listele"]');
    if (table) {
      await loadAjaxTable(table);
    }
  } catch (error) {
    statusField.value = eskiDurum;
    statusField.disabled = false;
    statusField.classList.remove('is-saving');
    window.alert(error.message);
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
    const dialog = document.querySelector(opener.getAttribute('data-open-dialog'));
    if (opener.matches('[data-quick-appointment-prefill]')) {
      const form = dialog?.querySelector('[data-quick-appointment-form]');
      if (form) {
        const values = {
          ogrenci_id: opener.dataset.ogrenciId || '',
          veli_onam_id: opener.dataset.onamId || '',
          ogrenci_ad_soyad: opener.dataset.ogrenciAdSoyad || '',
          dogum_tarihi: opener.dataset.dogumTarihi || '',
          veli_ad_soyad: opener.dataset.veliAdSoyad || '',
          veli_telefon: opener.dataset.veliTelefon || '',
        };
        Object.entries(values).forEach(([name, value]) => {
          const field = form.elements.namedItem(name);
          if (field) {
            field.value = value;
            field.dispatchEvent(new Event('input', { bubbles: true }));
          }
        });
      }
    }
    openDialogElement(dialog);
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

  const debtPaymentPlan = event.target.closest('[data-debt-payment-plan]');
  if (debtPaymentPlan) {
    const dialog = document.querySelector('[data-debt-payment-plan-dialog]');
    const form = dialog?.querySelector('[data-debt-payment-plan-form]');
    if (!dialog || !form) return;
    form.reset();
    form.elements.paket_id.value = debtPaymentPlan.getAttribute('data-paket-id') || '';
    form.elements.beklenen_odeme_tarihi.value = debtPaymentPlan.getAttribute('data-expected-payment-date') || '';
    try {
      form.elements.tahsilat_notu.value = decodeURIComponent(debtPaymentPlan.getAttribute('data-payment-note') || '');
    } catch (error) {
      form.elements.tahsilat_notu.value = '';
    }
    const title = dialog.querySelector('[data-debt-payment-plan-title]');
    if (title) title.textContent = debtPaymentPlan.getAttribute('data-package-label') || '';
    const message = form.querySelector('[data-form-message]');
    if (message) message.textContent = '';
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

  const waitingParentHistory = event.target.closest('[data-waiting-parent-history]');
  if (waitingParentHistory) {
    const id = Number(waitingParentHistory.getAttribute('data-waiting-parent-history'));
    const dialog = document.querySelector('[data-waiting-parent-history-dialog]');
    const form = dialog?.querySelector('[data-waiting-parent-history-form]');
    if (!id || !dialog || !form) return;
    form.reset();
    form.elements.bekleyen_veli_id.value = String(id);
    form.elements.gorusme_tarihi.value = localDateTimeInputValue();
    dialog.querySelector('[data-waiting-parent-history-message]').textContent = '';
    dialog.querySelector('[data-waiting-parent-groups-message]').textContent = '';
    openDialogElement(dialog);
    try {
      await loadWaitingParentHistory(id, dialog);
    } catch (error) {
      dialog.querySelector('[data-waiting-parent-history-list]').innerHTML = `<div class="empty-table">${escapeHtml(error.message)}</div>`;
    }
    return;
  }

  const waitingParentEdit = event.target.closest('[data-edit-waiting-parent]');
  if (waitingParentEdit) {
    const id = Number(waitingParentEdit.getAttribute('data-edit-waiting-parent'));
    const table = waitingParentEdit.closest('[data-table="bekleyen_veli_listele"]');
    const row = table?._talyaRows?.find((item) => Number(item.id) === id);
    const dialog = document.querySelector('[data-waiting-parent-edit-dialog]');
    const form = dialog?.querySelector('form');
    if (!row || !dialog || !form) return;

    form.reset();
    const values = {
      id: row.id,
      ogrenci_ad_soyad: row.ogrenci_ad_soyad || '',
      ogrenci_dogum_tarihi: row.ogrenci_dogum_tarihi || '',
      veli_ad_soyad: row.veli_ad_soyad || '',
      veli_telefon: row.veli_telefon || '',
      veli_eposta: row.veli_eposta || '',
      ay_grubu: row.ay_grubu || '',
      zaman_tercihi: row.zaman_tercihi || 'farketmez',
      notlar: row.notlar || '',
    };
    Object.entries(values).forEach(([name, value]) => {
      const field = form.elements.namedItem(name);
      if (field) field.value = value;
    });
    const selectedGroups = new Set((row.grup_idleri || []).map(Number));
    form.querySelectorAll('[name="grup_ids[]"]').forEach((field) => {
      field.checked = selectedGroups.has(Number(field.value));
    });
    const message = form.querySelector('[data-form-message]');
    if (message) message.textContent = '';
    form.elements.veli_telefon?.dispatchEvent(new Event('input', { bubbles: true }));
    openDialogElement(dialog);
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
      set('kdv_orani', serviceEdit.dataset.vat);
      set('haftalik_katilim_sayisi', serviceEdit.dataset.weekly);
      set('toplam_normal_hak', serviceEdit.dataset.normal);
      set('toplam_telafi_hak', serviceEdit.dataset.makeup);
      set('hak_hesaplama_turu', serviceEdit.dataset.calendarMode);
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

  const studentQuickEdit = event.target.closest('[data-quick-edit-student]');
  if (studentQuickEdit) {
    const table = studentQuickEdit.closest('[data-table="ogrenci_listele"]');
    const id = Number(studentQuickEdit.getAttribute('data-quick-edit-student'));
    const row = (table?._talyaRows || []).find((item) => Number(item.id) === id);
    const dialog = document.querySelector('#ogrenci-hizli-duzenle-dialog');
    const form = dialog?.querySelector('[data-student-quick-edit-form]');
    if (!row || !form) {
      return;
    }

    form.reset();
    const values = {
      id: row.id,
      veli_id: row.birincil_veli_id || '',
      ogrenci_ad: row.ad || '',
      ogrenci_soyad: row.soyad || '',
      ogrenci_dogum_tarihi: row.dogum_tarihi || '',
      ogrenci_kayit_tarihi: row.kayit_tarihi || '',
      ogrenci_cinsiyet: row.cinsiyet || 'belirtilmedi',
      ogrenci_durum: row.durum || 'aktif',
      ogrenci_il: row.il || 'Antalya',
      ogrenci_ilce: row.ilce || '',
      ogrenci_adres: row.adres || '',
      veli_ad: row.birincil_veli_ad || '',
      veli_soyad: row.birincil_veli_soyad || '',
      veli_telefon: row.birincil_veli_telefon || row.telefon || '',
    };
    Object.entries(values).forEach(([name, value]) => {
      const field = form.elements.namedItem(name);
      if (field) field.value = value;
    });
    const message = form.querySelector('[data-form-message]');
    if (message) message.textContent = '';
    form.elements.veli_telefon?.dispatchEvent(new Event('input', { bubbles: true }));
    openDialogElement(dialog);
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
  set('kdv_orani', option.dataset.vat);
  set('haftalik_katilim_sayisi', option.dataset.weekly);
  set('haftalik_katilim_sayisi_gosterim', option.dataset.weekly);
  set('toplam_normal_hak', option.dataset.normal);
  set('toplam_telafi_hak', option.dataset.makeup);
  set('hak_hesaplama_turu', option.dataset.calendarMode || 'sabit');
  updatePackageAssignmentSchedule(form);
});

document.addEventListener('change', (event) => {
  const form = event.target.closest('[data-package-assignment-form]');
  if (!form || (!event.target.matches('[name="baslangic_tarihi"]') && !event.target.matches('[name="program_gunleri[]"]'))) return;
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
      kismi_odeme: 'Kismi odeme'
    };
    return labels[value] || '';
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
                ${row.paket_bitis_tarihi && row.paket_bitis_tarihi !== row.tarih ? '<small class="waiting-follow-up">Telafi dahil son ders tarihi</small>' : ''}
                ${renewalStatus(row.odeme_durumu) ? `<i>${escapeHtml(renewalStatus(row.odeme_durumu))}</i>` : ''}
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
