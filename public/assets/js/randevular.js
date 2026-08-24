(function () {
  const profileAppointments = document.querySelector('[data-profile-appointments]');
  const profileMakeup = document.querySelector('[data-profile-makeup]');
  const profileEditDialog = document.querySelector('[data-profile-edit-dialog]');
  const profileEditForm = document.querySelector('[data-profile-edit-form]');
  const profileAppointmentEditDialog = document.querySelector('[data-profile-appointment-edit-dialog]');
  const profileAppointmentEditForm = document.querySelector('[data-profile-appointment-edit-form]');
  const packageMessage = document.querySelector('[data-profile-package-message]');
  if (!profileAppointments && !profileMakeup && !profileEditForm && !profileAppointmentEditForm && !packageMessage) {
    return;
  }

  const message = profileAppointments?.querySelector('[data-profile-appointment-message]');
  const makeupMessage = profileMakeup?.querySelector('[data-profile-makeup-message]');

  function setProfileMessage(text) {
    if (message) {
      message.textContent = text || '';
    }
  }

  function setPackageMessage(text) {
    if (packageMessage) {
      packageMessage.textContent = text || '';
    }
  }

  function setMakeupMessage(text) {
    if (makeupMessage) {
      makeupMessage.textContent = text || '';
    }
  }

  function closeProfileEditDialog() {
    if (!profileEditDialog) {
      return;
    }
    if (typeof profileEditDialog.close === 'function') {
      profileEditDialog.close();
      return;
    }
    profileEditDialog.removeAttribute('open');
  }

  function openProfileEditDialog() {
    if (!profileEditDialog) {
      return;
    }
    if (typeof profileEditDialog.showModal === 'function') {
      profileEditDialog.showModal();
      return;
    }
    profileEditDialog.setAttribute('open', 'open');
  }

  function closeProfileAppointmentEditDialog() {
    if (!profileAppointmentEditDialog) {
      return;
    }
    if (typeof profileAppointmentEditDialog.close === 'function') {
      profileAppointmentEditDialog.close();
      return;
    }
    profileAppointmentEditDialog.removeAttribute('open');
  }

  function openProfileAppointmentEditDialog() {
    if (!profileAppointmentEditDialog) {
      return;
    }
    if (typeof profileAppointmentEditDialog.showModal === 'function') {
      profileAppointmentEditDialog.showModal();
      return;
    }
    profileAppointmentEditDialog.setAttribute('open', 'open');
  }

  function timeShort(value) {
    return String(value || '').slice(0, 5);
  }

  function dateInput(value) {
    return String(value || '').slice(0, 10);
  }

  function durationMinutes(row) {
    const start = Date.parse(`2026-01-01T${row.baslangic_saati || '09:00:00'}`);
    const end = Date.parse(`2026-01-01T${row.bitis_saati || '09:45:00'}`);
    if (Number.isNaN(start) || Number.isNaN(end)) {
      return 45;
    }
    return Math.max(15, Math.round((end - start) / 60000));
  }

  function checkedProfileIds() {
    return Array.from(profileAppointments?.querySelectorAll('[data-profile-appointment-check]:checked') || [])
      .map((box) => Number(box.value))
      .filter(Boolean);
  }

  async function deleteProfileAppointments(ids) {
    if (!ids.length) {
      setProfileMessage('Once randevu secin.');
      return;
    }
    if (!window.confirm(`${ids.length} randevu silinsin mi?`)) {
      return;
    }

    setProfileMessage('Siliniyor...');
    try {
      const result = await talyaAjax('randevu_sil', { ids });
      setProfileMessage(result.mesaj);
      window.location.reload();
    } catch (error) {
      setProfileMessage(error.message);
    }
  }

  async function saveProfileAppointmentStatus(id) {
    const statusField = profileAppointments?.querySelector(`[data-profile-appointment-status="${id}"]`);
    const durum = statusField?.value || '';
    if (!id || !durum) {
      setProfileMessage('Randevu ve durum secilmelidir.');
      return;
    }

    setProfileMessage('Durum guncelleniyor...');
    try {
      const result = await talyaAjax('randevu_durum_degistir', { id, durum });
      setProfileMessage(result.mesaj);
      window.location.reload();
    } catch (error) {
      setProfileMessage(error.message);
    }
  }

  async function openProfileAppointmentEdit(id) {
    if (!profileAppointmentEditForm) {
      return;
    }

    setProfileMessage('Randevu bilgisi getiriliyor...');
    try {
      const result = await talyaAjax('randevu_detay', { id });
      const row = result.veri || {};
      profileAppointmentEditForm.elements.id.value = row.id || id;
      profileAppointmentEditForm.elements.tarih.value = dateInput(row.tarih);
      profileAppointmentEditForm.elements.baslangic_saati.value = timeShort(row.baslangic_saati);
      profileAppointmentEditForm.elements.sure_dakika.value = durationMinutes(row);
      profileAppointmentEditForm.elements.durum.value = row.durum || 'planlandi';
      profileAppointmentEditForm.elements.tur.value = row.tur || row.paket_adi || '';
      profileAppointmentEditForm.elements.hak_kaynagi.value = row.hak_kaynagi || 'Aktif paket';
      profileAppointmentEditForm.elements.aciklama.value = row.aciklama || '';
      if (profileAppointmentEditForm.elements.randevu_sms_gonder) {
        profileAppointmentEditForm.elements.randevu_sms_gonder.checked = false;
      }
      const editMessage = profileAppointmentEditForm.querySelector('[data-profile-appointment-edit-message]');
      if (editMessage) {
        editMessage.textContent = '';
      }
      setProfileMessage('');
      openProfileAppointmentEditDialog();
    } catch (error) {
      setProfileMessage(error.message);
    }
  }

  profileAppointments?.addEventListener('click', async (event) => {
    const edit = event.target.closest('[data-profile-appointment-edit]');
    if (edit) {
      await openProfileAppointmentEdit(Number(edit.getAttribute('data-profile-appointment-edit')));
      return;
    }

    const singleDelete = event.target.closest('[data-profile-appointment-delete]');
    if (singleDelete) {
      await deleteProfileAppointments([Number(singleDelete.getAttribute('data-profile-appointment-delete'))]);
      return;
    }

    const statusSave = event.target.closest('[data-profile-appointment-status-save]');
    if (statusSave) {
      await saveProfileAppointmentStatus(Number(statusSave.getAttribute('data-profile-appointment-status-save')));
      return;
    }

    if (event.target.closest('[data-profile-appointment-delete-selected]')) {
      await deleteProfileAppointments(checkedProfileIds());
    }
  });

  profileAppointments?.addEventListener('change', (event) => {
    const statusField = event.target.closest('[data-profile-appointment-status]');
    if (statusField) {
      saveProfileAppointmentStatus(Number(statusField.getAttribute('data-profile-appointment-status')));
      return;
    }

    if (!event.target.closest('[data-profile-appointment-check-all]')) {
      return;
    }
    const checked = event.target.checked;
    profileAppointments.querySelectorAll('[data-profile-appointment-check]').forEach((box) => {
      box.checked = checked;
    });
  });

  document.addEventListener('click', async (event) => {
    if (event.target.closest('[data-open-profile-edit]')) {
      openProfileEditDialog();
      return;
    }

    if (event.target.closest('[data-profile-edit-close]')) {
      closeProfileEditDialog();
      return;
    }

    if (event.target.closest('[data-profile-appointment-edit-close]')) {
      closeProfileAppointmentEditDialog();
      return;
    }

    const unpaidClose = event.target.closest('[data-profile-package-unpaid-close]');
    if (unpaidClose) {
      const id = Number(unpaidClose.getAttribute('data-profile-package-unpaid-close'));
      if (!id) {
        return;
      }
      if (!window.confirm('Bu paketin kalan borcu odeme yapilmadi olarak kapatilacak. Tahsilat veya kasa kaydi olusmayacak. Devam edilsin mi?')) {
        return;
      }
      setPackageMessage('Borc kapatiliyor...');
      unpaidClose.disabled = true;
      try {
        const result = await talyaAjax('paket_odeme_yapilmadi_kapat', {
          paket_id: id,
          neden: 'Gelmedigi icin odeme alinmadi.'
        });
        setPackageMessage(result.mesaj);
        window.location.reload();
      } catch (error) {
        setPackageMessage(error.message);
        unpaidClose.disabled = false;
      }
      return;
    }

    const packageDelete = event.target.closest('[data-profile-package-delete]');
    if (packageDelete) {
      const id = Number(packageDelete.getAttribute('data-profile-package-delete'));
      if (!window.confirm('Bu paket silinsin mi? Bagli randevular, telafi kayitlari ve varsa tahsilat baglantilari da silinir.')) {
        return;
      }
      setPackageMessage('Siliniyor...');
      try {
        const result = await talyaAjax('paket_sil', { id });
        setPackageMessage(result.mesaj);
        window.location.reload();
      } catch (error) {
        setPackageMessage(error.message);
      }
      return;
    }

    const makeupPlan = event.target.closest('[data-makeup-plan]');
    if (makeupPlan) {
      const row = makeupPlan.closest('[data-makeup-row]');
      const id = Number(makeupPlan.getAttribute('data-makeup-plan'));
      const tarih = row?.querySelector('[data-makeup-date]')?.value || '';
      const baslangicSaati = row?.querySelector('[data-makeup-time]')?.value || '';
      const sureDakika = row?.querySelector('[data-makeup-duration]')?.value || '45';
      if (!tarih || !baslangicSaati) {
        setMakeupMessage('Telafi tarihi ve saati secilmelidir.');
        return;
      }
      setMakeupMessage('Telafi randevusu olusturuluyor...');
      try {
        const result = await talyaAjax('telafi_planla', {
          id,
          tarih,
          baslangic_saati: baslangicSaati,
          sure_dakika: sureDakika
        });
        setMakeupMessage(result.mesaj);
        window.location.reload();
      } catch (error) {
        setMakeupMessage(error.message);
      }
    }
  });

  profileEditForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const editMessage = profileEditForm.querySelector('[data-profile-edit-message]');
    if (editMessage) {
      editMessage.textContent = 'Kaydediliyor...';
    }

    try {
      const result = await talyaAjax('ogrenci_profil_guncelle', formValues(profileEditForm));
      if (editMessage) {
        editMessage.textContent = result.mesaj;
      }
      window.location.reload();
    } catch (error) {
      if (editMessage) {
        editMessage.textContent = error.message;
      }
    }
  });

  profileAppointmentEditForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const editMessage = profileAppointmentEditForm.querySelector('[data-profile-appointment-edit-message]');
    if (editMessage) {
      editMessage.textContent = 'Guncelleniyor...';
    }

    try {
      const result = await talyaAjax('randevu_guncelle', formValues(profileAppointmentEditForm));
      if (editMessage) {
        editMessage.textContent = result.mesaj;
      }
      window.location.reload();
    } catch (error) {
      if (editMessage) {
        editMessage.textContent = error.message;
      }
    }
  });
})();

(function () {
  const page = document.querySelector('[data-randevu-page]');
  if (!page) {
    return;
  }

  const canEditAppointments = page.getAttribute('data-can-edit-appointments') === '1';
  const canChangeAppointmentStatus = page.getAttribute('data-can-change-appointment-status') === '1';

  function pad(value) {
    return String(value).padStart(2, '0');
  }

  function dateKey(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
  }

  function monthKey(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}`;
  }

  const state = {
    rows: [],
    calendarRows: [],
    stats: { planlandi: 0, geldi: 0, gelmedi: 0 },
    currentDate: dateKey(new Date()),
    calendarView: 'month',
    month: monthKey(new Date()),
    filter: '',
    selected: new Set()
  };

  const mobileQuery = window.matchMedia ? window.matchMedia('(max-width: 760px)') : { matches: false };
  const table = page.querySelector('[data-randevu-table]');
  const calendar = page.querySelector('[data-randevu-calendar]');
  const message = page.querySelector('[data-randevu-message]');
  const detailDialog = page.querySelector('[data-randevu-detail-dialog]');
  const detailContent = page.querySelector('[data-randevu-detail-content]');
  const dialog = page.querySelector('[data-randevu-dialog]');
  const editForm = page.querySelector('[data-randevu-edit-form]');
  const noteDialog = page.querySelector('[data-randevu-note-dialog]');
  const noteForm = page.querySelector('[data-randevu-note-form]');
  const bulkDialog = page.querySelector('[data-randevu-bulk-dialog]');
  const bulkForm = page.querySelector('[data-randevu-bulk-form]');
  const title = page.querySelector('[data-calendar-title]');
  const contextMenu = document.createElement('div');
  const mobileActionSheet = document.createElement('div');
  const dayPopover = document.createElement('div');
  let currentDetail = null;
  let contextAppointmentId = null;
  let mobileActionAppointmentId = null;
  let mobileCreateMenuOpen = false;

  contextMenu.className = 'appointment-context-menu';
  contextMenu.hidden = true;
  contextMenu.innerHTML = `
    ${canChangeAppointmentStatus ? `
      <button type="button" data-context-action="geldi"><span>✓</span> Geldi Olarak Isaretle</button>
      <button type="button" data-context-action="planlandi"><span>◉</span> Bekleniyor Olarak Isaretle</button>
      <button type="button" data-context-action="gelmedi"><span>■</span> Gelmedi Olarak Isaretle</button>
      <button type="button" data-context-action="kurum_iptali"><span>×</span> Randevuyu Iptal Et</button>
    ` : ''}
    ${canEditAppointments ? `
      <button type="button" data-context-action="edit"><span>✎</span> Randevuyu Duzenle</button>
      <button type="button" data-context-action="note"><span>＋</span> Not Ekle</button>
      <button type="button" data-context-action="copy"><span>▣</span> Randevuyu Kopyala</button>
      <button type="button" data-context-action="delete"><span>▥</span> Randevuyu Sil</button>
    ` : ''}
    <hr>
    <button type="button" data-context-action="profile"><span>●</span> Profile Git</button>
  `;
  document.body.appendChild(contextMenu);

  mobileActionSheet.className = 'mobile-appointment-sheet';
  mobileActionSheet.hidden = true;
  mobileActionSheet.innerHTML = `
    <div class="mobile-appointment-sheet-backdrop" data-mobile-action-close></div>
    <div class="mobile-appointment-sheet-panel" role="dialog" aria-modal="true" aria-label="Randevu islemleri">
      <span class="mobile-sheet-handle" aria-hidden="true"></span>
      <h3>Islem Seciniz</h3>
      <div class="mobile-action-list">
        ${canChangeAppointmentStatus ? `
          <button type="button" data-mobile-action="geldi"><span>✓</span><strong>Geldi Olarak Isaretle</strong><em>›</em></button>
          <button type="button" data-mobile-action="planlandi"><span>◉</span><strong>Bekleniyor Olarak Isaretle</strong><em>›</em></button>
          <button type="button" data-mobile-action="gelmedi"><span>▣</span><strong>Gelmedi Olarak Isaretle</strong><em>›</em></button>
          <button type="button" data-mobile-action="kurum_iptali"><span>□</span><strong>Randevuyu Iptal Et</strong><em>›</em></button>
        ` : ''}
        ${canEditAppointments ? `
          <button type="button" data-mobile-action="edit"><span>✎</span><strong>Randevuyu Duzenle</strong><em>›</em></button>
          <button type="button" data-mobile-action="note"><span>＋</span><strong>Not Ekle</strong><em>›</em></button>
          <button type="button" data-mobile-action="copy"><span>⧉</span><strong>Randevuyu Kopyala</strong><em>›</em></button>
          <button type="button" data-mobile-action="delete"><span>−</span><strong>Randevuyu Sil</strong><em>›</em></button>
        ` : ''}
        <button type="button" data-mobile-action="profile"><span>☻</span><strong>Profile Git</strong><em>›</em></button>
      </div>
    </div>
  `;
  document.body.appendChild(mobileActionSheet);

  dayPopover.className = 'calendar-day-popover';
  dayPopover.hidden = true;
  page.appendChild(dayPopover);

  const statusLabels = {
    planlandi: 'Planlandi',
    geldi: 'Geldi',
    gelmedi: 'Gelmedi',
    mazeretli_gelmedi: 'Mazeretli Gelmedi',
    gec_iptal: 'Gec Iptal',
    kurum_iptali: 'Kurum Iptali',
    ertelendi: 'Ertelendi',
    tamamlandi: 'Tamamlandi'
  };
  const attendanceLabels = {
    katilacagim: 'Katilacagim',
    katilamayacagim: 'Katilamayacagim'
  };
  const monthNames = ['Ocak', 'Subat', 'Mart', 'Nisan', 'Mayis', 'Haziran', 'Temmuz', 'Agustos', 'Eylul', 'Ekim', 'Kasim', 'Aralik'];
  const dayNames = ['PTS', 'SAL', 'CAR', 'PER', 'CUM', 'CTS', 'PAZ'];

  function setMessage(text) {
    if (message) {
      message.textContent = text || '';
    }
  }

  function isMobileCalendar() {
    return Boolean(mobileQuery.matches);
  }

  function selectedIds() {
    return Array.from(state.selected).map((id) => Number(id));
  }

  function timeShort(value) {
    return String(value || '').slice(0, 5);
  }

  function dateInput(value) {
    return String(value || '').slice(0, 10);
  }

  function formatShortDate(value) {
    const date = new Date(`${dateInput(value)}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
      return String(value || '');
    }
    return date.toLocaleDateString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }

  function durationMinutes(row) {
    const start = Date.parse(`2026-01-01T${row.baslangic_saati || '09:00:00'}`);
    const end = Date.parse(`2026-01-01T${row.bitis_saati || '09:45:00'}`);
    if (Number.isNaN(start) || Number.isNaN(end)) {
      return 45;
    }
    return Math.max(15, Math.round((end - start) / 60000));
  }

  function formatDateTime(row) {
    const date = new Date(`${dateInput(row.tarih)}T00:00:00`);
    const formatted = Number.isNaN(date.getTime())
      ? row.tarih
      : date.toLocaleDateString('tr-TR', { day: '2-digit', month: 'long', year: 'numeric', weekday: 'long' });
    return `${formatted} - ${timeShort(row.baslangic_saati)}`;
  }

  function formatDayTitle(value) {
    const date = new Date(`${dateInput(value)}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
      return String(value || '');
    }
    return date.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric' });
  }

  function formatMobileDayTitle(value) {
    const date = new Date(`${dateInput(value)}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
      return String(value || '');
    }
    return date.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric', weekday: 'long' });
  }

  function isMakeup(row) {
    return Number(row?.telafi_hakki_id || 0) > 0 || String(row?.hak_kaynagi || '').toLowerCase().includes('telafi');
  }

  function isRenewalReminder(row) {
    return row?.takvim_turu === 'yenileme_hatirlatma' || row?.durum === 'yenileme_hatirlatma';
  }

  function reminderText(row) {
    return row?.hatirlatma_turu === 'son_ders_yenileme'
      ? 'Son ders bitmis olabilir'
      : 'Bu hafta randevusu yok';
  }

  function previousLessonText(row) {
    return row?.onceki_randevu_tarihi ? `Onceki ders: ${formatShortDate(row.onceki_randevu_tarihi)}` : 'Onceki hafta derse geldi';
  }

  function sourceMakeupText(row) {
    if (!isMakeup(row)) {
      return '';
    }
    const sourceDate = row.telafi_kaynak_tarih ? formatShortDate(row.telafi_kaynak_tarih) : '';
    const sourceTime = timeShort(row.telafi_kaynak_saat || '');
    return [sourceDate, sourceTime].filter(Boolean).join(' ');
  }

  function attendanceText(row) {
    return attendanceLabels[row?.katilim_yaniti] || '';
  }

  function attendanceHtml(row) {
    const text = attendanceText(row);
    if (!text) {
      return '';
    }
    return `<small class="appointment-source attendance-source ${row.katilim_yaniti === 'katilacagim' ? 'is-join' : 'is-decline'}">Veli: ${escapeHtml(text)}</small>`;
  }

  function appointmentClass(row) {
    if (isRenewalReminder(row)) {
      return 'is-renewal-reminder';
    }
    if (isMakeup(row)) {
      return 'is-makeup';
    }
    return statusClass(row.durum);
  }

  function countRows(rows) {
    const stats = { planlandi: 0, geldi: 0, gelmedi: 0 };
    rows.forEach((row) => {
      if (Object.prototype.hasOwnProperty.call(stats, row.durum)) {
        stats[row.durum] += 1;
      }
    });
    return stats;
  }

  function statusClass(status) {
    if (status === 'geldi' || status === 'tamamlandi') {
      return 'is-success';
    }
    if (status === 'gelmedi' || status === 'gec_iptal') {
      return 'is-danger';
    }
    if (status === 'ertelendi' || status === 'mazeretli_gelmedi' || status === 'kurum_iptali') {
      return 'is-dark';
    }
    return 'is-planned';
  }

  function statusText(row) {
    if (isRenewalReminder(row)) {
      return 'Takip';
    }
    if (isMakeup(row)) {
      return 'Telafi';
    }
    return statusLabels[row?.durum] || row?.durum || '-';
  }

  function applyStatusToRows(ids, durum) {
    const idSet = new Set(ids.map((id) => String(id)));
    [state.rows, state.calendarRows].forEach((collection) => {
      collection.forEach((row) => {
        if (idSet.has(String(row.id))) {
          row.durum = durum;
        }
      });
    });
    state.stats = countRows(state.rows);
    renderStats();
    renderTable();
    renderCalendar(state.calendarRows);
  }

  function renderStats() {
    Object.entries(state.stats).forEach(([key, value]) => {
      const target = page.querySelector(`[data-randevu-stat="${key}"]`);
      if (target) {
        target.textContent = value;
      }
    });
  }

  function renderTable() {
    if (!table) {
      return;
    }
    const rows = state.filter ? state.rows.filter((row) => row.durum === state.filter) : state.rows;
    if (!rows.length) {
      table.innerHTML = '<div class="empty-table">Kayit bulunamadi.</div>';
      return;
    }

    const body = rows.map((row, index) => `
      <tr>
        <td><input type="checkbox" data-row-check value="${escapeHtml(row.id)}" ${state.selected.has(String(row.id)) ? 'checked' : ''}></td>
        <td>${index + 1}</td>
        <td>${escapeHtml(row.tarih)}</td>
        <td>${escapeHtml(timeShort(row.baslangic_saati))}</td>
        <td>${escapeHtml(row.ogrenci)}</td>
        <td>${escapeHtml(row.grup)}</td>
        <td>
          ${escapeHtml(row.tur)}
          ${isMakeup(row) ? `<small class="appointment-source">Telafi: ${escapeHtml(sourceMakeupText(row) || 'kaynak ders')}</small>` : ''}
          ${attendanceHtml(row)}
        </td>
        <td><span class="status-pill ${appointmentClass(row)}">${escapeHtml(isMakeup(row) ? `Telafi - ${statusLabels[row.durum] || row.durum}` : (statusLabels[row.durum] || row.durum))}</span></td>
        <td>
          <div class="row-actions">
            <button type="button" data-detail-randevu="${escapeHtml(row.id)}">Detay</button>
            ${canEditAppointments ? `<button type="button" data-edit-randevu="${escapeHtml(row.id)}">Duzenle</button>` : ''}
            ${canEditAppointments ? `<button type="button" data-note-randevu="${escapeHtml(row.id)}">Not Ekle</button>` : ''}
            ${canChangeAppointmentStatus ? `
              <button type="button" data-status-randevu="${escapeHtml(row.id)}" data-status="geldi">Geldi</button>
              <button type="button" data-status-randevu="${escapeHtml(row.id)}" data-status="gelmedi">Gelmedi</button>
            ` : ''}
            ${canEditAppointments ? `<button type="button" data-delete-randevu="${escapeHtml(row.id)}">Sil</button>` : ''}
          </div>
        </td>
      </tr>
    `).join('');

    table.innerHTML = `
      <table class="appointment-table">
        <thead>
          <tr>
            <th><input type="checkbox" data-check-all></th>
            <th>#</th>
            <th>Tarih</th>
            <th>Saat</th>
            <th>Ogrenci</th>
            <th>Poliklinik / Grup</th>
            <th>Randevu Tanimi</th>
            <th>Durum</th>
            <th>Islem</th>
          </tr>
        </thead>
        <tbody>${body}</tbody>
      </table>
    `;
  }

  function calendarRange() {
    const first = new Date(`${state.currentDate.slice(0, 7)}-01T00:00:00`);
    const start = new Date(first);
    const isoDay = first.getDay() === 0 ? 7 : first.getDay();
    start.setDate(first.getDate() - (isoDay - 1));
    const end = new Date(start);
    end.setDate(start.getDate() + 41);
    return { first, start, end };
  }

  function dateFromKey(value) {
    const date = new Date(`${dateInput(value)}T00:00:00`);
    return Number.isNaN(date.getTime()) ? new Date() : date;
  }

  function addDays(date, count) {
    const next = new Date(date);
    next.setDate(next.getDate() + count);
    return next;
  }

  function weekStart(date) {
    const start = new Date(date);
    const isoDay = start.getDay() === 0 ? 7 : start.getDay();
    start.setDate(start.getDate() - (isoDay - 1));
    return start;
  }

  function calendarFetchRange() {
    if (isMobileCalendar()) {
      const start = weekStart(dateFromKey(state.currentDate));
      return { start, end: addDays(start, 6) };
    }
    if (state.calendarView === 'day') {
      const day = dateFromKey(state.currentDate);
      return { start: day, end: day };
    }
    if (state.calendarView === 'week') {
      const start = weekStart(dateFromKey(state.currentDate));
      return { start, end: addDays(start, 6) };
    }
    const { start, end } = calendarRange();
    return { start, end };
  }

  function renderCalendarTitle(firstDate, lastDate) {
    if (!title) {
      return;
    }
    if (state.calendarView === 'day') {
      title.textContent = firstDate.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric', weekday: 'long' });
      return;
    }
    if (state.calendarView === 'week') {
      const sameMonth = firstDate.getMonth() === lastDate.getMonth() && firstDate.getFullYear() === lastDate.getFullYear();
      const startLabel = firstDate.toLocaleDateString('tr-TR', { day: 'numeric', month: sameMonth ? undefined : 'long' });
      const endLabel = lastDate.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric' });
      title.textContent = `${startLabel} - ${endLabel}`;
      return;
    }
    title.textContent = `${monthNames[firstDate.getMonth()]} ${firstDate.getFullYear()}`;
  }

  function groupEventsByDate(events) {
    const byDate = {};
    events.forEach((event) => {
      byDate[event.tarih] = byDate[event.tarih] || [];
      byDate[event.tarih].push(event);
    });
    Object.values(byDate).forEach((items) => {
      items.sort((a, b) => String(a.baslangic_saati || '').localeCompare(String(b.baslangic_saati || '')));
    });
    return byDate;
  }

  function eventButtonHtml(item, compact = false) {
    if (isRenewalReminder(item)) {
      return `
        <button type="button" class="calendar-event ${appointmentClass(item)} ${compact ? 'is-compact' : ''}" data-profile-ogrenci="${escapeHtml(item.ogrenci_id)}" title="${escapeHtml(reminderText(item))}">
          <strong>${escapeHtml(timeShort(item.baslangic_saati))}</strong>
          <span>${escapeHtml(item.ogrenci)}</span>
          <em>${escapeHtml(reminderText(item))} - Profile git</em>
        </button>
      `;
    }

    return `
      <button type="button" class="calendar-event ${appointmentClass(item)} ${compact ? 'is-compact' : ''}" data-detail-randevu="${escapeHtml(item.id)}" data-context-randevu="${escapeHtml(item.id)}" title="${escapeHtml(isMakeup(item) ? `Telafi dersi - Kaynak: ${sourceMakeupText(item) || 'kaynak ders'}` : '')}">
        <strong>${escapeHtml(timeShort(item.baslangic_saati))}</strong>
        <span>${escapeHtml(isMakeup(item) ? `Telafi - ${item.ogrenci}` : item.ogrenci)}</span>
        ${isMakeup(item) ? `<em>Kaynak: ${escapeHtml(sourceMakeupText(item) || 'kaynak ders')}</em>` : ''}
        ${attendanceText(item) ? `<em>Veli: ${escapeHtml(attendanceText(item))}</em>` : ''}
      </button>
    `;
  }

  function renderMobileAppointments(events) {
    const selectedDate = dateFromKey(state.currentDate);
    const weekFirst = weekStart(selectedDate);
    const weekLast = addDays(weekFirst, 6);
    renderCalendarTitle(weekFirst, weekLast);

    const byDate = groupEventsByDate(events);
    const currentKey = dateKey(selectedDate);
    const rows = (byDate[currentKey] || []).slice().sort((a, b) => String(a.baslangic_saati || '').localeCompare(String(b.baslangic_saati || '')));
    const monthLabel = selectedDate.toLocaleDateString('tr-TR', { month: 'long' });

    const dayStrip = Array.from({ length: 7 }, (_, index) => {
      const day = addDays(weekFirst, index);
      const key = dateKey(day);
      const count = (byDate[key] || []).length;
      const selected = key === currentKey;
      return `
        <button type="button" class="mobile-day-chip ${selected ? 'is-selected' : ''}" data-mobile-day="${key}">
          <span>${dayNames[index]}</span>
          <strong>${day.getDate()}</strong>
          ${count ? `<em>${count}</em>` : ''}
        </button>
      `;
    }).join('');

    const list = rows.length ? rows.map((row) => `
      <article class="mobile-appointment-card ${appointmentClass(row)}">
        ${isRenewalReminder(row) ? `<button type="button" class="mobile-appointment-main" data-profile-ogrenci="${escapeHtml(row.ogrenci_id)}">` : `<button type="button" class="mobile-appointment-main" data-detail-randevu="${escapeHtml(row.id)}">`}
          <i aria-hidden="true"></i>
          <span class="mobile-appointment-time">
            <strong>${escapeHtml(timeShort(row.baslangic_saati))}</strong>
            <small>${escapeHtml(timeShort(row.bitis_saati))}</small>
          </span>
          <span class="mobile-appointment-info">
            <strong>${escapeHtml(isMakeup(row) ? `Telafi - ${row.ogrenci}` : row.ogrenci)}</strong>
            <small>${escapeHtml(row.tur || row.paket_adi || 'Genel Randevu')}</small>
            <small>${escapeHtml(isRenewalReminder(row) ? (row.grup || '-') : (row.uzman || 'Muhammed Mesud Karakayali'))}</small>
            ${isMakeup(row) ? `<em>Kaynak: ${escapeHtml(sourceMakeupText(row) || 'kaynak ders')}</em>` : ''}
            ${isRenewalReminder(row) ? `<em>${escapeHtml(reminderText(row))}</em>` : ''}
          </span>
        </button>
        <div class="mobile-appointment-side">
          ${attendanceText(row) ? '<span class="mobile-attendance">✓</span>' : ''}
          <span class="mobile-status ${appointmentClass(row)}">${escapeHtml(statusText(row))}</span>
          ${isRenewalReminder(row) ? '' : `<button type="button" class="mobile-more-button" data-mobile-actions="${escapeHtml(row.id)}" aria-label="Randevu islemleri">•••</button>`}
        </div>
      </article>
    `).join('') : '<div class="mobile-empty-day">Bu gun icin randevu bulunamadi.</div>';

    calendar.innerHTML = `
      <div class="mobile-appointment-view">
        <div class="mobile-calendar-appbar">
          <button type="button" data-sidebar-toggle aria-label="Menuyu ac"><span></span><span></span><span></span></button>
          <strong>Randevular</strong>
          <span class="mobile-bell">9+</span>
        </div>
        <div class="mobile-calendar-month">
          <button type="button" data-mobile-prev-week aria-label="Onceki hafta">‹</button>
          <h2>${escapeHtml(monthLabel)}</h2>
          <button type="button" data-mobile-next-week aria-label="Sonraki hafta">›</button>
        </div>
        <div class="mobile-day-strip">${dayStrip}</div>
        <div class="mobile-selected-day">
          <span>${escapeHtml(formatMobileDayTitle(currentKey))}</span>
          <button type="button" data-calendar-view="day">Genislet⌄</button>
        </div>
        <div class="mobile-appointment-list">${list}</div>
        ${canEditAppointments ? `
          <div class="mobile-create-fab">
            <div class="mobile-create-menu" data-mobile-create-menu ${mobileCreateMenuOpen ? '' : 'hidden'}>
              <button type="button" data-open-dialog="#hizli-randevu-dialog">Hizli Randevu Olustur</button>
              <a href="/panel/randevular/yeni">Toplu Randevu</a>
              <a href="/panel/paketler/tanimla">Randevu Olustur</a>
            </div>
            <button class="mobile-floating-add" type="button" data-mobile-create-toggle aria-label="Randevu olusturma secenekleri" aria-expanded="${mobileCreateMenuOpen ? 'true' : 'false'}">+</button>
          </div>
        ` : ''}
      </div>
    `;
  }

  function renderMonthCalendar(events) {
    const { first, start } = calendarRange();
    renderCalendarTitle(first, first);

    const byDate = groupEventsByDate(events);

    const heads = dayNames.map((day) => `<div class="calendar-head">${day}</div>`).join('');
    const cells = [];
    for (let i = 0; i < 42; i += 1) {
      const day = new Date(start);
      day.setDate(start.getDate() + i);
      const key = dateKey(day);
      const items = byDate[key] || [];
      const visible = items.slice(0, 3);
      const hiddenCount = Math.max(0, items.length - visible.length);
      const isOtherMonth = day.getMonth() !== first.getMonth();
      const isToday = key === dateKey(new Date());
      cells.push(`
        <div class="calendar-cell ${isOtherMonth ? 'is-muted' : ''} ${isToday ? 'is-today' : ''}">
          <div class="calendar-date">${day.getDate()}</div>
          ${visible.map((item) => eventButtonHtml(item, true)).join('')}
          ${hiddenCount ? `<button type="button" class="calendar-more" data-day-popover="${key}">+${hiddenCount} daha fazla</button>` : ''}
        </div>
      `);
    }

    calendar.innerHTML = `<div class="calendar-month">${heads}${cells.join('')}</div>`;
  }

  function renderWeekCalendar(events) {
    const start = weekStart(dateFromKey(state.currentDate));
    const end = addDays(start, 6);
    renderCalendarTitle(start, end);
    const byDate = groupEventsByDate(events);

    const heads = [];
    const cells = [];
    for (let i = 0; i < 7; i += 1) {
      const day = addDays(start, i);
      const key = dateKey(day);
      const items = byDate[key] || [];
      const isToday = key === dateKey(new Date());
      heads.push(`<div class="calendar-head"><span>${dayNames[i]}</span><small>${day.toLocaleDateString('tr-TR', { day: '2-digit', month: '2-digit' })}</small></div>`);
      cells.push(`
        <div class="calendar-cell calendar-week-cell ${isToday ? 'is-today' : ''}">
          <div class="calendar-week-count">${items.length ? `${items.length} kayit` : 'Bos'}</div>
          ${items.map((item) => eventButtonHtml(item)).join('')}
        </div>
      `);
    }

    calendar.innerHTML = `<div class="calendar-week">${heads.join('')}${cells.join('')}</div>`;
  }

  function renderDayCalendar(events) {
    const day = dateFromKey(state.currentDate);
    renderCalendarTitle(day, day);
    const rows = events
      .filter((row) => row.tarih === dateKey(day))
      .sort((a, b) => String(a.baslangic_saati || '').localeCompare(String(b.baslangic_saati || '')));

    calendar.innerHTML = `
      <div class="calendar-day-view">
        <div class="calendar-day-view-head">
          <strong>${escapeHtml(day.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', weekday: 'long' }))}</strong>
          <span>${rows.length ? `${rows.length} kayit` : 'Randevu yok'}</span>
        </div>
        <div class="calendar-day-list">
          ${rows.length ? rows.map((item) => `
            <article class="calendar-day-row ${appointmentClass(item)}" ${isRenewalReminder(item) ? '' : `data-context-randevu="${escapeHtml(item.id)}"`}>
              <div>
                <strong>${escapeHtml(timeShort(item.baslangic_saati))}</strong>
                <small>${escapeHtml(timeShort(item.bitis_saati))}</small>
              </div>
              ${isRenewalReminder(item) ? `<button type="button" data-profile-ogrenci="${escapeHtml(item.ogrenci_id)}">` : `<button type="button" data-detail-randevu="${escapeHtml(item.id)}">`}
                <span>${escapeHtml(isMakeup(item) ? `Telafi - ${item.ogrenci}` : item.ogrenci)}</span>
                <small>${escapeHtml(item.grup || '-')} / ${escapeHtml(item.tur || '-')}</small>
                ${isMakeup(item) ? `<em>Kaynak: ${escapeHtml(sourceMakeupText(item) || 'kaynak ders')}</em>` : ''}
                ${attendanceText(item) ? `<em>Veli: ${escapeHtml(attendanceText(item))}</em>` : ''}
                ${isRenewalReminder(item) ? `<em>${escapeHtml(previousLessonText(item))}</em>` : ''}
              </button>
              <b>${escapeHtml(statusText(item) || statusLabels[item.durum] || item.durum || '-')}</b>
            </article>
          `).join('') : '<div class="empty-table">Bu gun icin randevu bulunamadi.</div>'}
        </div>
      </div>
    `;
  }

  function renderCalendar(events) {
    page.querySelectorAll('[data-calendar-view]').forEach((button) => {
      button.classList.toggle('is-active', button.getAttribute('data-calendar-view') === state.calendarView);
    });

    if (isMobileCalendar()) {
      renderMobileAppointments(events);
      return;
    }

    if (state.calendarView === 'day') {
      renderDayCalendar(events);
      return;
    }
    if (state.calendarView === 'week') {
      renderWeekCalendar(events);
      return;
    }
    renderMonthCalendar(events);
  }

  async function loadList() {
    const result = await talyaAjax('randevu_listele');
    const payload = result.veri || {};
    state.rows = Array.isArray(payload) ? payload : (payload.kayitlar || []);
    state.stats = Array.isArray(payload) ? countRows(payload) : (payload.ozet || { planlandi: 0, geldi: 0, gelmedi: 0 });
    renderStats();
    renderTable();
  }

  async function loadCalendar() {
    const range = calendarFetchRange();
    state.month = monthKey(dateFromKey(state.currentDate));
    const result = await talyaAjax('randevu_takvim', {
      ay: state.month,
      baslangic: dateKey(range.start),
      bitis: dateKey(range.end)
    });
    state.calendarRows = result.veri || [];
    renderCalendar(state.calendarRows);
  }

  async function refreshAll() {
    setMessage('Yukleniyor...');
    try {
      await Promise.all([loadList(), loadCalendar()]);
      setMessage('');
    } catch (error) {
      setMessage(error.message);
    }
  }

  function openDialog(target) {
    if (target && typeof target.showModal === 'function') {
      target.showModal();
    } else if (target) {
      target.setAttribute('open', 'open');
    }
  }

  function closeDialogs() {
    [detailDialog, dialog, noteDialog, bulkDialog].forEach((target) => {
      if (!target) {
        return;
      }
      if (typeof target.close === 'function') {
        target.close();
      } else {
        target.removeAttribute('open');
      }
    });
  }

  function hideContextMenu() {
    contextMenu.hidden = true;
    contextAppointmentId = null;
  }

  function hideDayPopover() {
    dayPopover.hidden = true;
    dayPopover.innerHTML = '';
  }

  function hideMobileActionSheet() {
    mobileActionSheet.hidden = true;
    mobileActionAppointmentId = null;
    document.body.classList.remove('has-mobile-sheet-open');
  }

  function showMobileActionSheet(id) {
    mobileActionAppointmentId = id;
    hideContextMenu();
    hideDayPopover();
    closeMobileCreateMenu();
    mobileActionSheet.hidden = false;
    document.body.classList.add('has-mobile-sheet-open');
  }

  function closeMobileCreateMenu() {
    mobileCreateMenuOpen = false;
    const menu = calendar?.querySelector('[data-mobile-create-menu]');
    const button = calendar?.querySelector('[data-mobile-create-toggle]');
    if (menu) {
      menu.hidden = true;
    }
    if (button) {
      button.setAttribute('aria-expanded', 'false');
    }
  }

  function toggleMobileCreateMenu() {
    const menu = calendar?.querySelector('[data-mobile-create-menu]');
    const button = calendar?.querySelector('[data-mobile-create-toggle]');
    if (!menu || !button) {
      return;
    }
    mobileCreateMenuOpen = !mobileCreateMenuOpen;
    menu.hidden = !mobileCreateMenuOpen;
    button.setAttribute('aria-expanded', mobileCreateMenuOpen ? 'true' : 'false');
  }

  function showContextMenu(event, id) {
    event.preventDefault();
    contextAppointmentId = id;
    const width = 310;
    const height = 360;
    const left = Math.min(event.clientX, window.innerWidth - width - 12);
    const top = Math.min(event.clientY, window.innerHeight - height - 12);
    contextMenu.style.left = `${Math.max(12, left)}px`;
    contextMenu.style.top = `${Math.max(12, top)}px`;
    contextMenu.hidden = false;
    contextMenu.style.zIndex = '2200';
  }

  function showDayPopover(date, anchor) {
    const rows = state.calendarRows
      .filter((row) => row.tarih === date)
      .sort((a, b) => String(a.baslangic_saati || '').localeCompare(String(b.baslangic_saati || '')));

    if (!rows.length) {
      return;
    }

    dayPopover.innerHTML = `
      <div class="calendar-day-popover-head">
        <strong>${escapeHtml(formatDayTitle(date))}</strong>
        <button type="button" data-day-popover-close aria-label="Kapat">×</button>
      </div>
      <div class="calendar-day-popover-list">
        ${rows.map((row) => `
          <button type="button"
            class="calendar-day-popover-event ${appointmentClass(row)}"
            ${isRenewalReminder(row) ? `data-profile-ogrenci="${escapeHtml(row.ogrenci_id)}"` : `data-detail-randevu="${escapeHtml(row.id)}" data-context-randevu="${escapeHtml(row.id)}"`}
          >
            <strong>${escapeHtml(timeShort(row.baslangic_saati))}</strong>
            <span>${escapeHtml(isMakeup(row) ? `Telafi - ${row.ogrenci}` : row.ogrenci)}</span>
            ${isMakeup(row) ? `<em>Kaynak: ${escapeHtml(sourceMakeupText(row) || 'kaynak ders')}</em>` : ''}
            ${attendanceText(row) ? `<em>Veli: ${escapeHtml(attendanceText(row))}</em>` : ''}
            ${isRenewalReminder(row) ? `<em>${escapeHtml(reminderText(row))}</em>` : ''}
          </button>
        `).join('')}
      </div>
    `;

    const rect = anchor.getBoundingClientRect();
    const width = Math.min(300, window.innerWidth - 24);
    const maxHeight = Math.min(320, window.innerHeight - 24);
    const estimatedHeight = Math.min(maxHeight, 42 + Math.min(rows.length, 6) * 48 + 16);
    const left = Math.min(Math.max(12, rect.left - 12), window.innerWidth - width - 12);
    const belowTop = rect.bottom + 8;
    const aboveTop = rect.top - estimatedHeight - 8;
    const top = belowTop + estimatedHeight <= window.innerHeight - 12 ? belowTop : aboveTop;
    dayPopover.style.width = `${width}px`;
    dayPopover.style.maxHeight = `${maxHeight}px`;
    dayPopover.style.left = `${left}px`;
    dayPopover.style.top = `${Math.min(Math.max(12, top), window.innerHeight - estimatedHeight - 12)}px`;
    dayPopover.hidden = false;
  }

  async function goToProfile(id) {
    const result = await talyaAjax('randevu_detay', { id });
    const row = result.veri || {};
    window.location.href = row.ogrenci_id ? `/panel/ogrenciler/profil?id=${encodeURIComponent(row.ogrenci_id)}` : '/panel/ogrenciler';
  }

  async function copyAppointment(id) {
    const result = await talyaAjax('randevu_detay', { id });
    const row = result.veri || {};
    const text = [
      row.ogrenci || '',
      formatDateTime(row),
      row.grup || '',
      row.paket_adi || row.tur || '',
      statusLabels[row.durum] || row.durum || ''
    ].filter(Boolean).join(' | ');

    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text);
      setMessage('Randevu bilgisi kopyalandi.');
      return;
    }
    setMessage(text);
  }

  function noteListHtml(notes) {
    if (!Array.isArray(notes) || !notes.length) {
      return '<div class="empty-table">Bu randevu icin henuz gunluk not yok.</div>';
    }

    return `
      <div class="appointment-note-list">
        ${notes.map((note) => `
          <article>
            <div>
              <strong>${escapeHtml(note.kategori || 'Genel')}</strong>
              <span>${escapeHtml(note.kaydeden || '-')} - ${escapeHtml(note.olusturulma_tarihi || '-')}</span>
            </div>
            <p>${escapeHtml(note.not_metni || '').replace(/\n/g, '<br>')}</p>
          </article>
        `).join('')}
      </div>
    `;
  }

  async function openNoteDialog(id) {
    if (!noteDialog || !noteForm) {
      return;
    }

    const result = await talyaAjax('randevu_detay', { id });
    const row = result.veri || {};
    noteForm.reset();
    noteForm.elements.randevu_id.value = row.id || id;
    noteForm.elements.tarih.value = dateInput(row.tarih) || dateKey(new Date());
    if (noteForm.elements.kategori) {
      noteForm.elements.kategori.value = 'Genel';
    }
    const student = noteForm.querySelector('[data-note-student]');
    const appointment = noteForm.querySelector('[data-note-appointment]');
    const noteMessage = noteForm.querySelector('[data-note-message]');
    if (student) {
      student.textContent = row.ogrenci || 'Ogrenci';
    }
    if (appointment) {
      appointment.textContent = `${formatDateTime(row)} / ${row.paket_adi || row.tur || '-'}`;
    }
    if (noteMessage) {
      noteMessage.textContent = '';
    }

    openDialog(noteDialog);
  }

  async function openDetail(id) {
    if (!detailDialog || !detailContent) {
      window.location.href = '/panel/randevular';
      return;
    }
    const result = await talyaAjax('randevu_detay', { id });
    const row = result.veri;
    currentDetail = row;
    detailContent.innerHTML = `
      <div class="appointment-detail-card">
        <h3>Bilgiler</h3>
        <div class="appointment-detail-grid">
          <div class="appointment-detail-item">
            <strong>Ad Soyad</strong>
            <span>${escapeHtml(row.ogrenci || '-')}</span>
          </div>
          <div class="appointment-detail-item">
            <strong>Randevu Tarihi</strong>
            <span>${escapeHtml(formatDateTime(row))}</span>
          </div>
          <div class="appointment-detail-item">
            <strong>Telefon Numarasi</strong>
            <span>${escapeHtml(row.telefon || '-')}</span>
          </div>
          <div class="appointment-detail-item">
            <strong>Randevu Tanimi / Paket Adi</strong>
            <span>${escapeHtml(row.paket_adi || row.tur || '-')}</span>
          </div>
          ${isMakeup(row) ? `
            <div class="appointment-detail-item">
              <strong>Telafi Kaynagi</strong>
              <span>${escapeHtml(sourceMakeupText(row) || row.telafi_kaynak_paket_adi || 'Kaynak randevu')}</span>
            </div>
          ` : ''}
          <div class="appointment-detail-item">
            <strong>Poliklinik</strong>
            <span>${escapeHtml(row.grup || '-')}</span>
          </div>
          <div class="appointment-detail-item">
            <strong>Randevu Notu</strong>
            <span>${escapeHtml(row.aciklama || '-')}</span>
          </div>
          <div class="appointment-detail-item">
            <strong>Uzman</strong>
            <span>${escapeHtml(row.uzman || '-')}</span>
          </div>
          <div class="appointment-detail-item">
            <strong>Randevu Suresi</strong>
            <span>${escapeHtml(durationMinutes(row))} Dakika</span>
          </div>
          <div class="appointment-detail-item">
            <strong>Veli Katilim Yaniti</strong>
            <span>${escapeHtml(attendanceText(row) || 'Henuz yanit yok')}</span>
          </div>
        </div>
        <p class="appointment-detail-note">* Bu randevu ${escapeHtml(row.olusturulma_tarihi || '-')} tarihinde ${escapeHtml(row.olusturan || '-')} tarafindan olusturulmustur.</p>
      </div>
      <div class="appointment-detail-card">
        <h3>Gunluk Notlar</h3>
        ${noteListHtml(row.gunluk_notlar || [])}
      </div>
    `;
    openDialog(detailDialog);
  }

  async function openEdit(id) {
    if (!editForm || !dialog) {
      window.location.href = '/panel/randevular';
      return;
    }
    const result = await talyaAjax('randevu_detay', { id });
    const row = result.veri;
    editForm.elements.id.value = row.id;
    editForm.elements.tarih.value = dateInput(row.tarih);
    editForm.elements.baslangic_saati.value = timeShort(row.baslangic_saati);
    editForm.elements.sure_dakika.value = durationMinutes(row);
    editForm.elements.durum.value = row.durum;
    editForm.elements.tur.value = row.tur || '';
    editForm.elements.hak_kaynagi.value = row.hak_kaynagi || '';
    editForm.elements.aciklama.value = row.aciklama || '';
    if (editForm.elements.randevu_sms_gonder) {
      editForm.elements.randevu_sms_gonder.checked = false;
    }
    const editMessage = editForm.querySelector('[data-edit-message]');
    if (editMessage) {
      editMessage.textContent = '';
    }
    openDialog(dialog);
  }

  async function deleteAppointments(ids) {
    if (!ids.length) {
      setMessage('Once randevu secin.');
      return;
    }
    if (!window.confirm('Secili randevular silinsin mi?')) {
      return;
    }
    const result = await talyaAjax('randevu_sil', { ids });
    state.selected.clear();
    setMessage(result.mesaj);
    await refreshAll();
  }

  async function runAppointmentAction(action, id) {
    if (!action || !id) {
      return;
    }
    if (['geldi', 'planlandi', 'gelmedi', 'kurum_iptali'].includes(action)) {
      if (!canChangeAppointmentStatus) {
        setMessage('Bu islem icin yetkiniz yok.');
        return;
      }
      const result = await talyaAjax('randevu_durum_degistir', { id, durum: action });
      setMessage(result.mesaj);
      applyStatusToRows([id], action);
      await refreshAll();
      return;
    }
    if (action === 'edit') {
      if (!canEditAppointments) {
        setMessage('Bu islem icin yetkiniz yok.');
        return;
      }
      await openEdit(id);
      return;
    }
    if (action === 'note') {
      if (!canEditAppointments) {
        setMessage('Bu islem icin yetkiniz yok.');
        return;
      }
      await openNoteDialog(id);
      return;
    }
    if (action === 'copy') {
      await copyAppointment(id);
      return;
    }
    if (action === 'delete') {
      if (!canEditAppointments) {
        setMessage('Bu islem icin yetkiniz yok.');
        return;
      }
      await deleteAppointments([Number(id)]);
      return;
    }
    if (action === 'profile') {
      await goToProfile(id);
    }
  }

  page.addEventListener('click', async (event) => {
    if (!event.target.closest('.appointment-context-menu')) {
      hideContextMenu();
    }
    if (!event.target.closest('.calendar-day-popover') && !event.target.closest('[data-day-popover]')) {
      hideDayPopover();
    }

    if (event.target.closest('[data-day-popover-close]')) {
      hideDayPopover();
      return;
    }

    if (event.target.closest('[data-note-close]')) {
      closeDialogs();
      return;
    }

    const close = event.target.closest('[data-dialog-close]');
    if (close) {
      closeDialogs();
      return;
    }

    if (event.target.closest('[data-mobile-create-toggle]')) {
      event.preventDefault();
      toggleMobileCreateMenu();
      return;
    }

    if (event.target.closest('.mobile-create-menu [data-open-dialog]')) {
      closeMobileCreateMenu();
      return;
    }

    const mobileDay = event.target.closest('[data-mobile-day]');
    if (mobileDay) {
      closeMobileCreateMenu();
      state.currentDate = mobileDay.getAttribute('data-mobile-day') || state.currentDate;
      await loadCalendar();
      return;
    }

    if (event.target.closest('[data-mobile-prev-week]')) {
      closeMobileCreateMenu();
      const date = dateFromKey(state.currentDate);
      date.setDate(date.getDate() - 7);
      state.currentDate = dateKey(date);
      await loadCalendar();
      return;
    }

    if (event.target.closest('[data-mobile-next-week]')) {
      closeMobileCreateMenu();
      const date = dateFromKey(state.currentDate);
      date.setDate(date.getDate() + 7);
      state.currentDate = dateKey(date);
      await loadCalendar();
      return;
    }

    const mobileActions = event.target.closest('[data-mobile-actions]');
    if (mobileActions) {
      event.preventDefault();
      event.stopPropagation();
      showMobileActionSheet(mobileActions.getAttribute('data-mobile-actions'));
      return;
    }

    const profileStudent = event.target.closest('[data-profile-ogrenci]');
    if (profileStudent) {
      const ogrenciId = profileStudent.getAttribute('data-profile-ogrenci');
      if (ogrenciId) {
        window.location.href = `/panel/ogrenciler/profil?id=${encodeURIComponent(ogrenciId)}`;
      }
      return;
    }

    const detail = event.target.closest('[data-detail-randevu]');
    if (detail) {
      closeMobileCreateMenu();
      hideDayPopover();
      await openDetail(detail.getAttribute('data-detail-randevu'));
      return;
    }

    if (event.target.closest('[data-detail-profile]')) {
      window.location.href = currentDetail ? `/panel/ogrenciler/profil?id=${encodeURIComponent(currentDetail.ogrenci_id)}` : '/panel/ogrenciler';
      return;
    }

    if (event.target.closest('[data-detail-edit]')) {
      if (currentDetail) {
        closeDialogs();
        await openEdit(currentDetail.id);
      }
      return;
    }

    if (event.target.closest('[data-detail-note]')) {
      if (currentDetail) {
        closeDialogs();
        await openNoteDialog(currentDetail.id);
      }
      return;
    }

    const edit = event.target.closest('[data-edit-randevu]');
    if (edit) {
      if (!canEditAppointments) {
        setMessage('Bu islem icin yetkiniz yok.');
        return;
      }
      await openEdit(edit.getAttribute('data-edit-randevu'));
      return;
    }

    const note = event.target.closest('[data-note-randevu]');
    if (note) {
      if (!canEditAppointments) {
        setMessage('Bu islem icin yetkiniz yok.');
        return;
      }
      await openNoteDialog(note.getAttribute('data-note-randevu'));
      return;
    }

    const status = event.target.closest('[data-status-randevu]');
    if (status) {
      if (!canChangeAppointmentStatus) {
        setMessage('Bu islem icin yetkiniz yok.');
        return;
      }
      const id = status.getAttribute('data-status-randevu');
      const durum = status.getAttribute('data-status');
      const result = await talyaAjax('randevu_durum_degistir', {
        id,
        durum
      });
      setMessage(result.mesaj);
      applyStatusToRows([id], durum);
      await refreshAll();
      return;
    }

    const del = event.target.closest('[data-delete-randevu]');
    if (del) {
      await deleteAppointments([Number(del.getAttribute('data-delete-randevu'))]);
      return;
    }

    if (event.target.closest('[data-bulk-delete]')) {
      await deleteAppointments(selectedIds());
      return;
    }

    if (event.target.closest('[data-bulk-status-apply]')) {
      if (!canChangeAppointmentStatus) {
        setMessage('Bu islem icin yetkiniz yok.');
        return;
      }
      const durum = page.querySelector('[data-bulk-status]')?.value || '';
      const ids = selectedIds();
      if (!ids.length || !durum) {
        setMessage('Randevu ve durum secin.');
        return;
      }
      const result = await talyaAjax('randevu_durum_degistir', { ids, durum });
      state.selected.clear();
      setMessage(result.mesaj);
      applyStatusToRows(ids, durum);
      await refreshAll();
      return;
    }

    if (event.target.closest('[data-bulk-edit-open]')) {
      if (!canEditAppointments) {
        setMessage('Bu islem icin yetkiniz yok.');
        return;
      }
      if (!selectedIds().length) {
        setMessage('Toplu duzenleme icin randevu secin.');
        return;
      }
      openDialog(bulkDialog);
      return;
    }

    const filter = event.target.closest('[data-randevu-filter]');
    if (filter) {
      state.filter = state.filter === filter.getAttribute('data-randevu-filter') ? '' : filter.getAttribute('data-randevu-filter');
      renderTable();
      return;
    }

    const dayButton = event.target.closest('[data-day-popover]');
    if (dayButton) {
      showDayPopover(dayButton.getAttribute('data-day-popover'), dayButton);
      return;
    }

    const viewButton = event.target.closest('[data-calendar-view]');
    if (viewButton) {
      hideDayPopover();
      state.calendarView = viewButton.getAttribute('data-calendar-view') || 'month';
      await loadCalendar();
      return;
    }

    if (event.target.closest('[data-calendar-prev]')) {
      hideDayPopover();
      const date = dateFromKey(state.currentDate);
      if (state.calendarView === 'day') {
        date.setDate(date.getDate() - 1);
      } else if (state.calendarView === 'week') {
        date.setDate(date.getDate() - 7);
      } else {
        date.setMonth(date.getMonth() - 1);
      }
      state.currentDate = dateKey(date);
      state.month = monthKey(date);
      await loadCalendar();
      return;
    }

    if (event.target.closest('[data-calendar-next]')) {
      hideDayPopover();
      const date = dateFromKey(state.currentDate);
      if (state.calendarView === 'day') {
        date.setDate(date.getDate() + 1);
      } else if (state.calendarView === 'week') {
        date.setDate(date.getDate() + 7);
      } else {
        date.setMonth(date.getMonth() + 1);
      }
      state.currentDate = dateKey(date);
      state.month = monthKey(date);
      await loadCalendar();
      return;
    }

    if (event.target.closest('[data-calendar-today]')) {
      hideDayPopover();
      state.currentDate = dateKey(new Date());
      state.month = monthKey(new Date());
      await loadCalendar();
      return;
    }

    if (event.target.closest('[data-calendar-print]')) {
      window.print();
    }
  });

  page.addEventListener('contextmenu', (event) => {
    const dayButton = event.target.closest('[data-day-popover]');
    if (dayButton && !event.target.closest('[data-context-randevu]')) {
      event.preventDefault();
      showDayPopover(dayButton.getAttribute('data-day-popover'), dayButton);
      hideContextMenu();
      return;
    }

    const target = event.target.closest('[data-context-randevu]');
    if (!target) {
      return;
    }
    showContextMenu(event, target.getAttribute('data-context-randevu'));
  });

  contextMenu.addEventListener('click', async (event) => {
    const action = event.target.closest('[data-context-action]')?.getAttribute('data-context-action');
    const id = contextAppointmentId;
    if (!action || !id) {
      return;
    }
    hideContextMenu();
    await runAppointmentAction(action, id);
  });

  mobileActionSheet.addEventListener('click', async (event) => {
    if (event.target.closest('[data-mobile-action-close]')) {
      hideMobileActionSheet();
      return;
    }
    const action = event.target.closest('[data-mobile-action]')?.getAttribute('data-mobile-action');
    const id = mobileActionAppointmentId;
    if (!action || !id) {
      return;
    }
    hideMobileActionSheet();
    await runAppointmentAction(action, id);
  });

  document.addEventListener('click', (event) => {
    if (!event.target.closest('.appointment-context-menu')) {
      hideContextMenu();
    }
    if (!event.target.closest('.calendar-day-popover') && !event.target.closest('[data-day-popover]')) {
      hideDayPopover();
    }
    if (!event.target.closest('.mobile-create-fab')) {
      closeMobileCreateMenu();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      hideContextMenu();
      hideDayPopover();
      hideMobileActionSheet();
      closeMobileCreateMenu();
    }
  });

  if (typeof mobileQuery.addEventListener === 'function') {
    mobileQuery.addEventListener('change', () => {
      loadCalendar();
    });
  } else if (typeof mobileQuery.addListener === 'function') {
    mobileQuery.addListener(() => {
      loadCalendar();
    });
  }

  page.addEventListener('change', (event) => {
    if (!table) {
      return;
    }
    const check = event.target.closest('[data-row-check]');
    if (check) {
      if (check.checked) {
        state.selected.add(check.value);
      } else {
        state.selected.delete(check.value);
      }
      return;
    }

    if (event.target.closest('[data-check-all]')) {
      const checked = event.target.checked;
      table.querySelectorAll('[data-row-check]').forEach((box) => {
        box.checked = checked;
        if (checked) {
          state.selected.add(box.value);
        } else {
          state.selected.delete(box.value);
        }
      });
    }
  });

  if (editForm) {
    editForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const editMessage = editForm.querySelector('[data-edit-message]');
      if (editMessage) {
        editMessage.textContent = 'Kaydediliyor...';
      }
      try {
        const result = await talyaAjax('randevu_guncelle', formValues(editForm));
        if (editMessage) {
          editMessage.textContent = result.mesaj;
        }
        closeDialogs();
        await refreshAll();
      } catch (error) {
        if (editMessage) {
          editMessage.textContent = error.message;
        }
      }
    });
  }

  if (noteForm) {
    noteForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const noteMessage = noteForm.querySelector('[data-note-message]');
      if (noteMessage) {
        noteMessage.textContent = 'Kaydediliyor...';
      }
      try {
        const result = await talyaAjax('gunluk_not_ekle', formValues(noteForm));
        if (noteMessage) {
          noteMessage.textContent = result.mesaj;
        }
        closeDialogs();
        setMessage(result.mesaj);
      } catch (error) {
        if (noteMessage) {
          noteMessage.textContent = error.message;
        }
      }
    });
  }

  if (bulkForm) {
    bulkForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const bulkMessage = bulkForm.querySelector('[data-bulk-message]');
      if (bulkMessage) {
        bulkMessage.textContent = 'Kaydediliyor...';
      }
      try {
        const result = await talyaAjax('randevu_toplu_guncelle', { ...formValues(bulkForm), ids: selectedIds() });
        state.selected.clear();
        if (bulkMessage) {
          bulkMessage.textContent = result.mesaj;
        }
        closeDialogs();
        await refreshAll();
      } catch (error) {
        if (bulkMessage) {
          bulkMessage.textContent = error.message;
        }
      }
    });
  }

  refreshAll();
})();
