(function () {
  const page = document.querySelector('[data-group-program-page]');
  if (!page) {
    return;
  }

  const body = page.querySelector('[data-group-program-body]');
  const message = page.querySelector('[data-group-program-message]');
  const addButton = document.querySelector('[data-group-add-row]');
  const vacancyOpenButton = document.querySelector('[data-open-vacancy-calendar]');
  const syncButton = document.querySelector('[data-sync-group-appointments]');
  const studentDialog = page.querySelector('[data-group-student-dialog]');
  const studentSelect = page.querySelector('[data-group-student-select]');
  const groupTargetList = page.querySelector('[data-group-target-list]');
  const studentList = page.querySelector('[data-group-student-list]');
  const groupMonth = page.querySelector('[data-group-month]');
  const groupMonthlyList = page.querySelector('[data-group-monthly-list]');
  const studentMessage = page.querySelector('[data-group-student-message]');
  const vacancyDialog = page.querySelector('[data-group-vacancy-dialog]');
  const vacancyCalendar = page.querySelector('[data-group-vacancy-calendar]');
  const vacancySummary = page.querySelector('[data-group-vacancy-summary]');
  const vacancyMessage = page.querySelector('[data-group-vacancy-message]');
  const vacancyPrintArea = page.querySelector('[data-group-vacancy-print]');
  const vacancyShowStudents = page.querySelector('[data-group-vacancy-show-students]');
  const vacancyShowLastWeekEnded = page.querySelector('[data-group-vacancy-show-last-week-ended]');
  const vacancyStart = page.querySelector('[data-group-vacancy-start]');
  const vacancyEnd = page.querySelector('[data-group-vacancy-end]');
  const vacancyRangeLabel = page.querySelector('[data-group-vacancy-range-label]');
  const ageSortButton = page.querySelector('[data-sort-age-group]');
  let currentGroupId = 0;
  let lastVacancyRows = [];
  let programRows = [];
  let programSort = {
    field: 'default',
    direction: 'asc'
  };

  const days = [
    { value: 1, label: 'Pazartesi' },
    { value: 2, label: 'Sali' },
    { value: 3, label: 'Carsamba' },
    { value: 4, label: 'Persembe' },
    { value: 5, label: 'Cuma' },
    { value: 6, label: 'Cumartesi' },
    { value: 7, label: 'Pazar' }
  ];

  const statuses = [
    { value: 'durum_yok', label: 'Durum yok' },
    { value: 'kayit_acik', label: 'Kayit Acik' },
    { value: 'yeni_grup', label: 'Yeni Grup' },
    { value: 'kontenjan_sinirli', label: 'Kontenjan Sinirli' },
    { value: 'doldu', label: 'Doldu' }
  ];

  function setMessage(text) {
    if (message) {
      message.textContent = text || '';
    }
  }

  function setStudentMessage(text) {
    if (studentMessage) {
      studentMessage.textContent = text || '';
    }
  }

  function setVacancyMessage(text) {
    if (vacancyMessage) {
      vacancyMessage.textContent = text || '';
    }
  }

  function timeShort(value, fallback = '10:00') {
    return String(value || fallback).slice(0, 5);
  }

  function timeValue(value, fallback = '10:00') {
    return timeShort(value, fallback);
  }

  function ageSortValue(value) {
    const match = String(value || '').match(/\d+/);
    return match ? Number(match[0]) : Number.MAX_SAFE_INTEGER;
  }

  function compareProgramDefault(first, second) {
    return (Number(first.gun || 0) - Number(second.gun || 0))
      || timeValue(first.baslangic_saati).localeCompare(timeValue(second.baslangic_saati))
      || timeValue(first.bitis_saati, '11:00').localeCompare(timeValue(second.bitis_saati, '11:00'))
      || String(first.program_adi || '').localeCompare(String(second.program_adi || ''), 'tr')
      || String(first.yas_araligi || '').localeCompare(String(second.yas_araligi || ''), 'tr');
  }

  function sortedProgramRows(rows = programRows) {
    const sorted = [...rows];
    if (programSort.field === 'age') {
      sorted.sort((first, second) => (
        (ageSortValue(first.yas_araligi) - ageSortValue(second.yas_araligi))
        || String(first.yas_araligi || '').localeCompare(String(second.yas_araligi || ''), 'tr')
        || compareProgramDefault(first, second)
      ));
      if (programSort.direction === 'desc') {
        sorted.reverse();
      }
      return sorted;
    }
    sorted.sort(compareProgramDefault);
    return sorted;
  }

  function updateSortUi() {
    if (!ageSortButton) {
      return;
    }
    const isAgeSort = programSort.field === 'age';
    ageSortButton.classList.toggle('is-active', isAgeSort);
    ageSortButton.classList.toggle('is-desc', isAgeSort && programSort.direction === 'desc');
    ageSortButton.setAttribute('aria-sort', isAgeSort ? (programSort.direction === 'desc' ? 'descending' : 'ascending') : 'none');
  }

  function formatDate(value) {
    if (!value) {
      return '-';
    }
    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
      return value;
    }
    return new Intl.DateTimeFormat('tr-TR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    }).format(date);
  }

  function dateFromIso(value) {
    const parts = String(value || '').split('-').map(Number);
    if (parts.length !== 3 || parts.some((part) => Number.isNaN(part))) {
      return null;
    }
    return new Date(parts[0], parts[1] - 1, parts[2]);
  }

  function isoDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  function addDays(date, count) {
    const copy = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    copy.setDate(copy.getDate() + count);
    return copy;
  }

  function mondayOf(date) {
    const copy = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const day = copy.getDay() || 7;
    copy.setDate(copy.getDate() - day + 1);
    return copy;
  }

  function dateRange(startIso, endIso) {
    const start = dateFromIso(startIso);
    const end = dateFromIso(endIso);
    if (!start || !end) {
      return [];
    }
    const first = start <= end ? start : end;
    const last = start <= end ? end : start;
    const dates = [];
    let cursor = first;
    while (cursor <= last && dates.length < 61) {
      dates.push(isoDate(cursor));
      cursor = addDays(cursor, 1);
    }
    return dates;
  }

  function formatDateHeading(value) {
    const date = dateFromIso(value);
    if (!date) {
      return value || '-';
    }
    const dayLabel = days.find((day) => String(day.value) === String(date.getDay() || 7))?.label || '';
    return `${dayLabel} ${new Intl.DateTimeFormat('tr-TR', { day: '2-digit', month: '2-digit' }).format(date)}`;
  }

  function setVacancyRange(offsetWeeks = 0) {
    const start = addDays(mondayOf(new Date()), offsetWeeks * 7);
    const end = addDays(start, 6);
    if (vacancyStart) {
      vacancyStart.value = isoDate(start);
    }
    if (vacancyEnd) {
      vacancyEnd.value = isoDate(end);
    }
  }

  function selectedVacancyRange() {
    if (!vacancyStart?.value || !vacancyEnd?.value) {
      setVacancyRange(0);
    }
    const dates = dateRange(vacancyStart?.value, vacancyEnd?.value);
    return {
      baslangic_tarihi: dates[0] || vacancyStart?.value || '',
      bitis_tarihi: dates[dates.length - 1] || vacancyEnd?.value || '',
      dates
    };
  }

  function updateVacancyRangeLabel(meta = {}) {
    if (!vacancyRangeLabel) {
      return;
    }
    const range = selectedVacancyRange();
    const start = meta.baslangic_tarihi || range.baslangic_tarihi;
    const end = meta.bitis_tarihi || range.bitis_tarihi;
    vacancyRangeLabel.textContent = `${formatDate(start)} - ${formatDate(end)} / ${new Intl.DateTimeFormat('tr-TR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(new Date())}`;
  }

  function options(list, selected) {
    return list.map((item) => `<option value="${escapeHtml(item.value)}" ${String(item.value) === String(selected) ? 'selected' : ''}>${escapeHtml(item.label)}</option>`).join('');
  }

  function rowTemplate(row = {}) {
    const id = row.id || '';
    const groupId = row.grup_id || '';
    return `
      <tr data-program-row data-id="${escapeHtml(id)}" data-group-id="${escapeHtml(groupId)}">
        <td>
          <select name="gun">
            ${options(days, row.gun || 1)}
          </select>
        </td>
        <td><input type="time" name="baslangic_saati" value="${escapeHtml(timeShort(row.baslangic_saati))}"></td>
        <td><input type="time" name="bitis_saati" value="${escapeHtml(timeShort(row.bitis_saati, '11:00'))}"></td>
        <td><input name="yas_araligi" value="${escapeHtml(row.yas_araligi || '')}" placeholder="30-50 Ay"></td>
        <td><input name="program_adi" value="${escapeHtml(row.program_adi || '')}" placeholder="Yarim Gun"></td>
        <td><input type="number" name="kontenjan" min="1" max="99" value="${escapeHtml(row.kontenjan || 8)}"></td>
        <td>
          <select name="durum">
            ${options(statuses, row.durum || 'durum_yok')}
          </select>
        </td>
        <td>
          <div class="weekly-actions">
            <button class="is-assign" type="button" data-assign-student ${groupId ? '' : 'disabled'}>Ogrenci Ata${row.ogrenci_sayisi ? ` (${escapeHtml(row.ogrenci_sayisi)})` : ''}</button>
            <button class="is-copy" type="button" data-copy-program>Kopyala</button>
            <button class="is-delete" type="button" data-delete-program>Sil</button>
          </div>
        </td>
      </tr>
    `;
  }

  function renderRows() {
    const rows = sortedProgramRows();
    body.innerHTML = rows.length
      ? rows.map(rowTemplate).join('')
      : rowTemplate({ gun: 1, baslangic_saati: '10:00', bitis_saati: '11:00', yas_araligi: '30-50 Ay', program_adi: 'Yarim Gun', durum: 'durum_yok' });
    updateSortUi();
  }

  function groupOptionLabel(row = {}) {
    const dayLabel = days.find((day) => String(day.value) === String(row.gun))?.label || '-';
    return `${dayLabel} ${timeShort(row.baslangic_saati, '-')} - ${timeShort(row.bitis_saati, '-')} / ${row.program_adi || '-'} / ${row.yas_araligi || '-'}`;
  }

  function renderGroupTargets() {
    if (!groupTargetList) {
      return;
    }
    const rows = sortedProgramRows(programRows || []).filter((row) => Number(row.grup_id || 0) > 0);
    if (!rows.length) {
      groupTargetList.innerHTML = '<div class="empty-table">Atanabilecek grup bulunamadi.</div>';
      return;
    }
    groupTargetList.innerHTML = rows.map((row) => {
      const groupId = Number(row.grup_id || 0);
      return `
        <label class="group-target-option">
          <input type="checkbox" data-group-target-check value="${escapeHtml(groupId)}" ${groupId === currentGroupId ? 'checked' : ''}>
          <span>
            <strong>${escapeHtml(row.program_adi || '-')}</strong>
            <em>${escapeHtml(groupOptionLabel(row))}</em>
          </span>
        </label>
      `;
    }).join('');
  }

  function selectedGroupIds() {
    return Array.from(groupTargetList?.querySelectorAll('[data-group-target-check]:checked') || [])
      .map((box) => Number(box.value || 0))
      .filter((id) => id > 0);
  }

  function rowValues(row) {
    return {
      id: row.dataset.id || '',
      gun: row.querySelector('[name="gun"]').value,
      baslangic_saati: row.querySelector('[name="baslangic_saati"]').value,
      bitis_saati: row.querySelector('[name="bitis_saati"]').value,
      yas_araligi: row.querySelector('[name="yas_araligi"]').value.trim(),
      program_adi: row.querySelector('[name="program_adi"]').value.trim(),
      durum: row.querySelector('[name="durum"]').value,
      kontenjan: Math.max(1, Number(row.querySelector('[name="kontenjan"]').value || 8))
    };
  }

  async function loadRows() {
    body.innerHTML = '<tr><td colspan="8">Yukleniyor...</td></tr>';
    try {
      const result = await talyaAjax('grup_program_listele');
      const rows = result.veri || [];
      programRows = rows;
      renderRows();
      setMessage('');
    } catch (error) {
      body.innerHTML = `<tr><td colspan="8">${escapeHtml(error.message)}</td></tr>`;
    }
  }

  async function saveRow(row) {
    const values = rowValues(row);
    if (!values.yas_araligi || !values.program_adi || !values.baslangic_saati || !values.bitis_saati) {
      setMessage('Gun, saat, yas/grup ve program adi alanlari doldurulmalidir.');
      return;
    }

    const isNew = !values.id;
    const islem = isNew ? 'grup_program_ekle' : 'grup_program_guncelle';
    const result = await talyaAjax(islem, values);
    setMessage(result.mesaj);
    if (isNew) {
      await loadRows();
    }
  }

  function debouncedSave(row) {
    clearTimeout(row._saveTimer);
    row._saveTimer = setTimeout(() => {
      saveRow(row).catch((error) => setMessage(error.message));
    }, 500);
  }

  async function deleteRow(row) {
    const id = row.dataset.id || '';
    if (!id) {
      row.remove();
      return;
    }
    if (!window.confirm('Bu ders programi satiri silinsin mi?')) {
      return;
    }
    const result = await talyaAjax('grup_program_sil', { id });
    setMessage(result.mesaj);
    await loadRows();
  }

  async function copyRow(row) {
    const values = rowValues(row);
    values.id = '';
    if (!values.yas_araligi || !values.program_adi || !values.baslangic_saati || !values.bitis_saati) {
      setMessage('Kopyalama icin gun, saat, yas/grup ve program adi alanlari dolu olmalidir.');
      return;
    }

    const result = await talyaAjax('grup_program_ekle', values);
    setMessage(`Satir kopyalandi. ${result.mesaj}`);
    await loadRows();
  }

  addButton?.addEventListener('click', () => {
    body.insertAdjacentHTML('beforeend', rowTemplate({
      gun: 1,
      baslangic_saati: '10:00',
      bitis_saati: '11:00',
      yas_araligi: '',
      program_adi: '',
      kontenjan: 8,
      durum: 'durum_yok'
    }));
  });

  ageSortButton?.addEventListener('click', () => {
    if (programSort.field === 'age') {
      programSort.direction = programSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      programSort = {
        field: 'age',
        direction: 'asc'
      };
    }
    renderRows();
  });

  function openDialog(target) {
    if (target && typeof target.showModal === 'function') {
      target.showModal();
    } else if (target) {
      target.setAttribute('open', 'open');
    }
  }

  function closeStudentDialog() {
    if (studentDialog && typeof studentDialog.close === 'function') {
      studentDialog.close();
    } else if (studentDialog) {
      studentDialog.removeAttribute('open');
    }
  }

  function closeDialog(target) {
    if (target && typeof target.close === 'function') {
      target.close();
    } else if (target) {
      target.removeAttribute('open');
    }
  }

  function statusLabel(value) {
    return (statuses.find((item) => item.value === value)?.label) || value || '-';
  }

  async function loadStudentOptions() {
    const result = await talyaAjax('grup_ogrenci_secenekleri');
    const options = (result.veri || []).map((student) => `<option value="${escapeHtml(student.id)}">${escapeHtml(student.ad_soyad)}</option>`).join('');
    studentSelect.innerHTML = `<option value="">Ogrenci seciniz</option>${options}`;
  }

  async function loadAssignedStudents() {
    const result = await talyaAjax('grup_ogrenci_listele', { grup_id: currentGroupId });
    const rows = result.veri || [];
    studentList.innerHTML = rows.length
      ? rows.map((student) => `
        <div class="group-student-item">
          <span>${escapeHtml(student.ad_soyad)}</span>
          <button type="button" data-remove-student="${escapeHtml(student.ogrenci_id)}">Cikar</button>
        </div>
      `).join('')
      : '<div class="empty-table">Bu gruba atanmis ogrenci yok.</div>';
  }

  async function loadMonthlyTracking() {
    if (!groupMonthlyList || !currentGroupId) {
      return;
    }
    groupMonthlyList.innerHTML = '<div class="empty-table">Yukleniyor...</div>';
    const result = await talyaAjax('grup_aylik_takip', {
      grup_id: currentGroupId,
      ay: groupMonth?.value || new Date().toISOString().slice(0, 7)
    });
    const rows = result.veri || [];
    if (!rows.length) {
      groupMonthlyList.innerHTML = '<div class="empty-table">Bu ay icin randevu bulunamadi.</div>';
      return;
    }

    groupMonthlyList.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>Tarih</th>
            <th>Saat</th>
            <th>Ogrenci</th>
            <th>Hizmet</th>
            <th>Ders</th>
            <th>Bitis</th>
            <th>Durum</th>
          </tr>
        </thead>
        <tbody>
          ${rows.map((row) => `
            <tr>
              <td>${escapeHtml(formatDate(row.tarih))}</td>
              <td>${escapeHtml(timeShort(row.baslangic_saati, '-'))}</td>
              <td>${escapeHtml(row.ogrenci || '-')}</td>
              <td>${escapeHtml(row.paket_adi || '-')}</td>
              <td>${row.telafi_hakki_id ? 'Telafi' : `${escapeHtml(row.ders_sirasi || '-')}/${escapeHtml(row.toplam_normal_hak || '-')}`}</td>
              <td>${escapeHtml(formatDate(row.tahmini_son_ders_tarihi))}</td>
              <td><span class="status-pill">${escapeHtml(row.durum || '-')}</span></td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  function studentNames(value) {
    return String(value || '')
      .split('||')
      .map((name) => name.trim())
      .filter(Boolean);
  }

  function vacancyState(item, includeLastWeekEnded = false) {
    const capacity = Number(item.kontenjan || 0);
    const lastWeekEnded = includeLastWeekEnded ? Number(item.gecen_hafta_dersi_biten_sayisi || 0) : 0;
    const count = Number(item.ogrenci_sayisi || 0) + lastWeekEnded;
    const empty = Math.max(capacity - count, 0);
    const extra = Math.max(Number(item.fazla_kontenjan || 0), count - capacity, 0);
    return { capacity, count, empty, extra, lastWeekEnded, full: empty < 1 };
  }

  function vacancyLabel(item, includeLastWeekEnded = false) {
    const state = vacancyState(item, includeLastWeekEnded);
    const suffix = state.lastWeekEnded > 0 ? ` (${state.lastWeekEnded} gecen hafta biten dahil)` : '';
    if (state.extra > 0) {
      return `+${state.extra} fazla / ${state.capacity} kontenjan${suffix}`;
    }
    if (state.empty < 1) {
      return `Dolu / ${state.capacity} kontenjan${suffix}`;
    }
    return `${state.empty} bos / ${state.capacity} kontenjan${suffix}`;
  }

  function formatMonthAverage(value) {
    const number = Number(value);
    if (!Number.isFinite(number)) {
      return '-';
    }
    return new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 1 }).format(number);
  }

  function monthAverageLabel(value) {
    const formatted = formatMonthAverage(value);
    return formatted === '-' ? '-' : `${formatted} ay`;
  }

  function vacancyTotals(rows, includeLastWeekEnded = false) {
    return (rows || []).reduce((totals, item) => {
      const state = vacancyState(item, includeLastWeekEnded);
      totals.empty += state.empty;
      totals.extra += state.extra;
      totals.capacity += state.capacity;
      totals.count += state.count;
      totals.lastWeekEnded += state.lastWeekEnded;
      const averageMonth = Number(item.ortalama_ay);
      if (Number.isFinite(averageMonth)) {
        totals.averageSum += averageMonth;
        totals.averageCount += 1;
      }
      return totals;
    }, {
      empty: 0,
      extra: 0,
      capacity: 0,
      count: 0,
      lastWeekEnded: 0,
      averageSum: 0,
      averageCount: 0
    });
  }

  function renderVacancySummary(rows, includeLastWeekEnded = false) {
    if (!vacancySummary) {
      return;
    }
    const totals = vacancyTotals(rows, includeLastWeekEnded);
    const rangeAverage = totals.averageCount ? totals.averageSum / totals.averageCount : null;
    vacancySummary.innerHTML = `
      <article>
        <span>Toplam Bos Kontenjan</span>
        <strong>${escapeHtml(totals.empty)}</strong>
      </article>
      <article>
        <span>${includeLastWeekEnded ? 'Toplam Kayit + Gecen Hafta' : 'Toplam Kayit'}</span>
        <strong>${escapeHtml(totals.count)} / ${escapeHtml(totals.capacity)}</strong>
      </article>
      <article class="${totals.extra > 0 ? 'is-overfull' : ''}">
        <span>Fazla Kontenjan</span>
        <strong>${escapeHtml(totals.extra)}</strong>
      </article>
      <article>
        <span>${includeLastWeekEnded ? 'Gecen Hafta Biten' : 'Ortalama Ay'}</span>
        <strong>${escapeHtml(includeLastWeekEnded ? totals.lastWeekEnded : monthAverageLabel(rangeAverage))}</strong>
      </article>
    `;
  }

  function renderVacancyCalendar(rows, showStudents = false, showLastWeekEnded = false) {
    if (!vacancyCalendar) {
      return;
    }
    renderVacancySummary(rows, showLastWeekEnded);
    const range = selectedVacancyRange();
    const dateKeys = range.dates.length ? range.dates : Array.from(new Set((rows || []).map((row) => row.tarih).filter(Boolean)));
    const grouped = new Map(dateKeys.map((date) => [String(date), []]));
    (rows || []).forEach((row) => {
      const key = String(row.tarih || '');
      if (grouped.has(key)) {
        grouped.get(key).push(row);
      }
    });

    vacancyCalendar.innerHTML = dateKeys.map((date) => {
      const items = grouped.get(String(date)) || [];
      return `
        <section class="group-vacancy-day">
          <h4>${escapeHtml(formatDateHeading(date))}</h4>
          ${items.length ? items.map((item) => {
            const lastWeekEndedNames = studentNames(item.gecen_hafta_dersi_bitenler);
            return `
            <article class="group-vacancy-slot ${vacancyState(item, showLastWeekEnded).full ? 'is-full' : ''} ${vacancyState(item, showLastWeekEnded).extra > 0 ? 'is-overfull' : ''} ${showLastWeekEnded && lastWeekEndedNames.length ? 'has-last-week-ended' : ''}">
              <strong>${escapeHtml(timeShort(item.baslangic_saati, '-'))} - ${escapeHtml(timeShort(item.bitis_saati, '-'))}</strong>
              <span>${escapeHtml(item.program_adi || '-')}</span>
              <small>${escapeHtml(item.yas_araligi || '-')} / ${escapeHtml(statusLabel(item.durum))}</small>
              <small>Ortalama: ${escapeHtml(monthAverageLabel(item.ortalama_ay))}</small>
              <b>${escapeHtml(vacancyLabel(item, showLastWeekEnded))}</b>
              ${showStudents ? `
                <ul class="group-vacancy-students">
                  ${studentNames(item.ogrenciler).length
                    ? studentNames(item.ogrenciler).map((name) => `<li>${escapeHtml(name)}</li>`).join('')
                    : '<li>Ogrenci yok</li>'}
                </ul>
              ` : ''}
              ${showLastWeekEnded && lastWeekEndedNames.length ? `
                <div class="group-vacancy-last-week-ended">
                  <em>Gecen hafta son dersi bitenler</em>
                  <ul>
                    ${lastWeekEndedNames.map((name) => `<li>${escapeHtml(name)}</li>`).join('')}
                  </ul>
                </div>
              ` : ''}
            </article>
          `;
          }).join('') : '<div class="group-vacancy-empty">Program yok.</div>'}
        </section>
      `;
    }).join('');
  }

  async function loadVacancyCalendar() {
    setVacancyMessage('');
    renderVacancySummary([]);
    if (vacancyCalendar) {
      vacancyCalendar.innerHTML = '<div class="empty-table">Yukleniyor...</div>';
    }
    try {
      const range = selectedVacancyRange();
      updateVacancyRangeLabel();
      const result = await talyaAjax('grup_bos_kontenjan_takvimi', {
        baslangic_tarihi: range.baslangic_tarihi,
        bitis_tarihi: range.bitis_tarihi
      });
      lastVacancyRows = result.veri || [];
      if (result.meta?.baslangic_tarihi && vacancyStart) {
        vacancyStart.value = result.meta.baslangic_tarihi;
      }
      if (result.meta?.bitis_tarihi && vacancyEnd) {
        vacancyEnd.value = result.meta.bitis_tarihi;
      }
      updateVacancyRangeLabel(result.meta || {});
      renderVacancyCalendar(lastVacancyRows, Boolean(vacancyShowStudents?.checked), Boolean(vacancyShowLastWeekEnded?.checked));
    } catch (error) {
      setVacancyMessage(error.message);
      renderVacancySummary([]);
      if (vacancyCalendar) {
        vacancyCalendar.innerHTML = `<div class="empty-table">${escapeHtml(error.message)}</div>`;
      }
    }
  }

  async function openVacancyCalendar() {
    if (!vacancyStart?.value || !vacancyEnd?.value) {
      setVacancyRange(0);
    }
    openDialog(vacancyDialog);
    await loadVacancyCalendar();
  }

  async function syncGroupAppointments() {
    if (!window.confirm('Mevcut randevular grup gun ve saatlerine gore yeniden eslestirilsin mi?')) {
      return;
    }
    const previousText = syncButton?.textContent || '';
    if (syncButton) {
      syncButton.disabled = true;
      syncButton.textContent = 'Senkronize ediliyor...';
    }
    setMessage('Randevular senkronize ediliyor...');
    try {
      const result = await talyaAjax('grup_randevu_senkronize_et');
      const data = result.veri || {};
      setMessage(`${result.mesaj} Eslesen randevu: ${data.son_eslesen || 0}.`);
      await loadRows();
      if (currentGroupId) {
        await Promise.all([loadAssignedStudents(), loadMonthlyTracking()]);
      }
    } catch (error) {
      setMessage(error.message);
    } finally {
      if (syncButton) {
        syncButton.disabled = false;
        syncButton.textContent = previousText;
      }
    }
  }

  function printVacancyCalendar() {
    if (!vacancyPrintArea) {
      return;
    }
    const printWindow = window.open('', '_blank', 'width=1100,height=800');
    if (!printWindow) {
      window.print();
      return;
    }
    printWindow.document.write(`
      <!doctype html>
      <html lang="tr">
      <head>
        <meta charset="utf-8">
        <title>Haftalik Grup Kontenjan Takvimi</title>
        <style>
          * { box-sizing: border-box; }
          body { margin: 24px; font-family: Montserrat, Arial, sans-serif; color: #1f2f46; }
          h3 { margin: 0 0 4px; font-size: 24px; }
          .group-vacancy-print-head { display: flex; justify-content: space-between; gap: 16px; margin-bottom: 16px; border-bottom: 1px solid #dbe5f1; padding-bottom: 12px; }
          .group-vacancy-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin: 10px 0 14px; }
          .group-vacancy-summary article { border: 1px solid #dbe5f1; border-radius: 8px; padding: 8px; }
          .group-vacancy-summary span { display: block; color: #5f6f85; font-size: 11px; font-weight: 700; }
          .group-vacancy-summary strong { display: block; margin-top: 3px; font-size: 18px; }
          .group-vacancy-summary article.is-overfull strong { color: #d34b3f; }
          .group-vacancy-calendar { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 8px; }
          .group-vacancy-day { min-height: 240px; border: 1px solid #dbe5f1; border-radius: 6px; padding: 8px; }
          .group-vacancy-day h4 { margin: 0 0 8px; text-align: center; font-size: 15px; }
          .group-vacancy-slot { display: grid; gap: 3px; margin-bottom: 7px; padding: 7px; border: 1px solid #dbe5f1; border-radius: 5px; }
          .group-vacancy-slot.is-full { border-color: #f4c7c3; background: #fff6f5; }
          .group-vacancy-slot.is-overfull { border-color: #ef9f96; background: #fff1ef; }
          .group-vacancy-slot.has-last-week-ended { border-color: #f3c66b; background: #fffaf0; }
          .group-vacancy-slot strong { font-size: 13px; }
          .group-vacancy-slot span { font-weight: 700; font-size: 12px; }
          .group-vacancy-slot small { color: #5f6f85; font-size: 11px; }
          .group-vacancy-slot b { color: #0d8f79; font-size: 12px; }
          .group-vacancy-slot.is-full b,
          .group-vacancy-slot.is-overfull b { color: #d34b3f; }
          .group-vacancy-option { display: none; }
          .group-vacancy-students { margin: 5px 0 0; padding-left: 16px; color: #334155; font-size: 10px; line-height: 1.25; }
          .group-vacancy-last-week-ended { margin-top: 6px; padding: 6px; border: 1px solid #f3c66b; border-radius: 4px; background: #fff3d4; color: #7a4d00; font-size: 10px; }
          .group-vacancy-last-week-ended em { display: block; margin-bottom: 3px; font-style: normal; font-weight: 700; }
          .group-vacancy-last-week-ended ul { margin: 0; padding-left: 14px; }
          .group-vacancy-empty { color: #8793a6; font-size: 12px; text-align: center; padding-top: 20px; }
          @media print { body { margin: 12mm; } }
        </style>
      </head>
      <body>${vacancyPrintArea.innerHTML}</body>
      </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
  }

  async function openStudentDialog(row) {
    currentGroupId = Number(row.dataset.groupId || 0);
    if (!currentGroupId) {
      setMessage('Once satiri kaydedin, sonra ogrenci atayin.');
      return;
    }
    setStudentMessage('');
    renderGroupTargets();
    await Promise.all([loadStudentOptions(), loadAssignedStudents(), loadMonthlyTracking()]);
    openDialog(studentDialog);
  }

  vacancyOpenButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    openVacancyCalendar().catch((error) => setMessage(error.message));
  });

  syncButton?.addEventListener('click', () => {
    syncGroupAppointments().catch((error) => setMessage(error.message));
  });

  vacancyShowStudents?.addEventListener('change', () => {
    renderVacancyCalendar(lastVacancyRows, Boolean(vacancyShowStudents.checked), Boolean(vacancyShowLastWeekEnded?.checked));
  });

  vacancyShowLastWeekEnded?.addEventListener('change', () => {
    renderVacancyCalendar(lastVacancyRows, Boolean(vacancyShowStudents?.checked), Boolean(vacancyShowLastWeekEnded.checked));
  });

  page.querySelector('[data-group-vacancy-this-week]')?.addEventListener('click', () => {
    setVacancyRange(0);
    loadVacancyCalendar().catch((error) => setVacancyMessage(error.message));
  });

  page.querySelector('[data-group-vacancy-next-week]')?.addEventListener('click', () => {
    setVacancyRange(1);
    loadVacancyCalendar().catch((error) => setVacancyMessage(error.message));
  });

  page.querySelector('[data-group-vacancy-apply]')?.addEventListener('click', () => {
    loadVacancyCalendar().catch((error) => setVacancyMessage(error.message));
  });

  page.addEventListener('click', async (event) => {
    if (event.target.closest('[data-open-vacancy-calendar]')) {
      await openVacancyCalendar();
      return;
    }

    if (event.target.closest('[data-group-vacancy-close]')) {
      closeDialog(vacancyDialog);
      return;
    }

    if (event.target.closest('[data-group-vacancy-print-button]')) {
      printVacancyCalendar();
      return;
    }

    if (event.target.closest('[data-weekly-info-close]')) {
      const info = page.querySelector('.weekly-info');
      if (info) {
        info.hidden = true;
      }
      return;
    }

    const row = event.target.closest('[data-program-row]');
    if (!row) {
      return;
    }

    try {
      if (event.target.closest('[data-assign-student]')) {
        await openStudentDialog(row);
      } else if (event.target.closest('[data-copy-program]')) {
        await copyRow(row);
      } else if (event.target.closest('[data-delete-program]')) {
        await deleteRow(row);
      }
    } catch (error) {
      setMessage(error.message);
    }
  });

  page.addEventListener('change', (event) => {
    const row = event.target.closest('[data-program-row]');
    if (!row || event.target.closest('[data-group-student-select]')) {
      return;
    }
    debouncedSave(row);
  });

  page.addEventListener('blur', (event) => {
    const row = event.target.closest('[data-program-row]');
    if (!row) {
      return;
    }
    debouncedSave(row);
  }, true);

  page.addEventListener('click', async (event) => {
    if (event.target.closest('[data-group-student-close]')) {
      closeStudentDialog();
      return;
    }

    if (event.target.closest('[data-group-student-add]')) {
      const ogrenciId = Number(studentSelect.value || 0);
      const grupIds = selectedGroupIds();
      if (!ogrenciId) {
        setStudentMessage('Ogrenci secin.');
        return;
      }
      if (!grupIds.length) {
        setStudentMessage('En az bir grup secin.');
        return;
      }
      try {
        const result = await talyaAjax('grup_ogrenci_ata', { grup_ids: grupIds, ogrenci_id: ogrenciId });
        setStudentMessage(result.mesaj);
        await Promise.all([loadAssignedStudents(), loadMonthlyTracking()]);
        await loadRows();
      } catch (error) {
        setStudentMessage(error.message);
      }
      return;
    }

    const remove = event.target.closest('[data-remove-student]');
    if (remove) {
      try {
        const result = await talyaAjax('grup_ogrenci_cikar', {
          grup_id: currentGroupId,
          ogrenci_id: Number(remove.getAttribute('data-remove-student'))
        });
        setStudentMessage(result.mesaj);
        await Promise.all([loadAssignedStudents(), loadMonthlyTracking()]);
        await loadRows();
      } catch (error) {
        setStudentMessage(error.message);
      }
    }
  });

  groupMonth?.addEventListener('change', () => {
    loadMonthlyTracking().catch((error) => setStudentMessage(error.message));
  });

  loadRows();
})();
