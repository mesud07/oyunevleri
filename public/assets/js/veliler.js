'use strict';

(() => {
  const page = document.querySelector('[data-address-book-page]');
  if (!page || typeof talyaAjax !== 'function') return;

  const table = page.querySelector('[data-address-book-table]');
  const search = page.querySelector('[data-address-search]');
  const relation = page.querySelector('[data-address-relation]');
  const completeness = page.querySelector('[data-address-completeness]');
  const resultNote = page.querySelector('[data-address-result-note]');
  const dialog = document.querySelector('[data-address-book-dialog]');
  const form = dialog?.querySelector('[data-address-book-form]');
  const canManage = page.dataset.canManage === '1';
  const canSms = page.dataset.canSms === '1' && Boolean(document.querySelector('[data-sms-compose-dialog]'));
  let rows = [];

  const text = (value) => String(value ?? '').trim();
  const searchable = (value) => text(value).toLocaleLowerCase('tr-TR');
  const phoneDigits = (value) => text(value).replace(/\D/g, '');
  const whatsappPhone = (value) => {
    let digits = phoneDigits(value);
    if (digits.startsWith('0')) digits = `90${digits.slice(1)}`;
    if (digits.length === 10) digits = `90${digits}`;
    return digits;
  };
  const addressText = (row) => [row.adres, row.ilce, row.il].map(text).filter(Boolean).join(', ');

  function filteredRows() {
    const query = searchable(search?.value);
    const selectedRelation = searchable(relation?.value);
    return rows.filter((row) => {
      const haystack = searchable([row.ad, row.soyad, row.telefon, row.yedek_telefon, row.eposta, row.yakinlik, row.ogrenciler, addressText(row), row.iletisim_referansi].join(' '));
      if (query && !haystack.includes(query)) return false;
      if (selectedRelation && searchable(row.yakinlik) !== selectedRelation) return false;
      const complete = Boolean(text(row.telefon) && text(row.eposta) && addressText(row));
      if (completeness?.value === 'complete' && !complete) return false;
      if (completeness?.value === 'missing' && complete) return false;
      return true;
    });
  }

  function actionButtons(row) {
    const phone = phoneDigits(row.telefon);
    const whatsapp = whatsappPhone(row.telefon);
    const email = text(row.eposta);
    const address = addressText(row);
    return `<div class="address-book-actions">
      ${phone ? `<a class="mini-btn" href="tel:${escapeHtml(phone)}" title="Telefonla ara">Ara</a>` : ''}
      ${whatsapp ? `<a class="mini-btn" href="https://wa.me/${escapeHtml(whatsapp)}" target="_blank" rel="noopener" title="WhatsApp konuşması aç">WhatsApp</a>` : ''}
      ${canSms && phone ? `<button class="mini-btn" type="button" data-open-sms-compose data-parent-id="${escapeHtml(row.id)}" data-parent-name="${escapeHtml(`${row.ad} ${row.soyad}`)}" data-phone="${escapeHtml(row.telefon)}">SMS</button>` : ''}
      ${email ? `<a class="mini-btn" href="mailto:${escapeHtml(email)}" title="E-posta gönder">E-posta</a>` : ''}
      ${address ? `<a class="mini-btn" href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}" target="_blank" rel="noopener" title="Haritada aç">Harita</a>` : ''}
      ${canManage ? `<button class="mini-btn" type="button" data-address-edit="${escapeHtml(row.id)}">Düzenle</button>` : ''}
    </div>`;
  }

  function render() {
    const list = filteredRows();
    resultNote.textContent = `${list.length} / ${rows.length} kişi gösteriliyor`;
    if (!list.length) {
      table.innerHTML = '<div class="empty-table">Filtreye uygun kişi bulunamadı.</div>';
      return;
    }
    table.innerHTML = `<table><thead><tr><th>Kişi</th><th>Bağlı Öğrenci</th><th>İletişim</th><th>Adres</th><th>Kaynak / Not</th><th>İşlemler</th></tr></thead><tbody>${list.map((row) => {
      const fullName = `${text(row.ad)} ${text(row.soyad)}`.trim();
      const address = addressText(row);
      return `<tr>
        <td><div class="address-book-person"><strong>${escapeHtml(fullName || '-')}</strong><span>${escapeHtml(row.yakinlik || 'Yakınlık belirtilmedi')}</span></div></td>
        <td><strong>${escapeHtml(row.ogrenciler || '-')}</strong><small>${Number(row.ogrenci_sayisi || 0)} öğrenci</small></td>
        <td><div class="address-book-contact">${row.telefon ? `<a href="tel:${escapeHtml(phoneDigits(row.telefon))}">${escapeHtml(row.telefon)}</a>` : '<span>Telefon yok</span>'}${row.yedek_telefon ? `<small>${escapeHtml(row.yedek_telefon)}</small>` : ''}${row.eposta ? `<a href="mailto:${escapeHtml(row.eposta)}">${escapeHtml(row.eposta)}</a>` : '<small>E-posta yok</small>'}</div></td>
        <td><span class="address-book-address">${escapeHtml(address || 'Adres yok')}</span></td>
        <td><div class="address-book-note"><strong>${escapeHtml(row.iletisim_referansi || '-')}</strong><span>${escapeHtml(row.notlar || 'Not yok')}</span></div></td>
        <td>${actionButtons(row)}</td>
      </tr>`;
    }).join('')}</tbody></table>`;
  }

  function refreshSummary() {
    page.querySelector('[data-address-count]').textContent = String(rows.length);
    page.querySelector('[data-address-phone-count]').textContent = String(rows.filter((row) => text(row.telefon)).length);
    page.querySelector('[data-address-email-count]').textContent = String(rows.filter((row) => text(row.eposta)).length);
    page.querySelector('[data-address-location-count]').textContent = String(rows.filter((row) => addressText(row)).length);
    const relations = [...new Set(rows.map((row) => text(row.yakinlik)).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'tr'));
    relation.innerHTML = '<option value="">Tüm yakınlıklar</option>' + relations.map((item) => `<option value="${escapeHtml(item)}">${escapeHtml(item)}</option>`).join('');
  }

  async function load() {
    table.innerHTML = '<div class="empty-table">Adres defteri yükleniyor...</div>';
    try {
      const result = await talyaAjax('veli_listele');
      rows = Array.isArray(result.veri) ? result.veri : [];
      refreshSummary();
      render();
    } catch (error) {
      table.innerHTML = `<div class="empty-table">${escapeHtml(error.message)}</div>`;
      resultNote.textContent = 'Adres defteri yüklenemedi.';
    }
  }

  function openForm(row = null) {
    if (!dialog || !form) return;
    form.reset();
    form.elements.id.value = row?.id || 0;
    ['ad', 'soyad', 'yakinlik', 'telefon', 'yedek_telefon', 'eposta', 'telefon_ulke', 'il', 'ilce', 'adres', 'iletisim_referansi', 'notlar'].forEach((field) => {
      if (form.elements[field]) form.elements[field].value = row?.[field] || (field === 'il' ? 'Antalya' : (field === 'telefon_ulke' ? 'Türkiye (+90)' : ''));
    });
    dialog.querySelector('[data-address-dialog-title]').textContent = row ? 'Kişiyi Düzenle' : 'Yeni Kişi';
    dialog.querySelector('[data-address-form-message]').textContent = '';
    dialog.showModal();
  }

  document.querySelector('[data-address-book-new]')?.addEventListener('click', () => openForm());
  document.querySelectorAll('[data-address-dialog-close]').forEach((button) => button.addEventListener('click', () => dialog?.close()));
  [search, relation, completeness].forEach((field) => field?.addEventListener(field === search ? 'input' : 'change', render));
  page.querySelector('[data-address-clear]')?.addEventListener('click', () => {
    search.value = '';
    relation.value = '';
    completeness.value = '';
    render();
  });
  table.addEventListener('click', (event) => {
    const edit = event.target.closest('[data-address-edit]');
    if (!edit) return;
    openForm(rows.find((row) => Number(row.id) === Number(edit.dataset.addressEdit)) || null);
  });
  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!form.reportValidity() || form.dataset.submitting === '1') return;
    const message = form.querySelector('[data-address-form-message]');
    form.dataset.submitting = '1';
    message.textContent = 'Kaydediliyor...';
    try {
      const values = Object.fromEntries(new FormData(form).entries());
      const action = Number(values.id || 0) > 0 ? 'veli_guncelle' : 'veli_ekle';
      const result = await talyaAjax(action, values);
      message.textContent = result.mesaj;
      await load();
      dialog.close();
    } catch (error) {
      message.textContent = error.message;
    } finally {
      form.dataset.submitting = '0';
    }
  });

  load();
})();
