(function () {
  const toggles = document.querySelectorAll('[data-package-price-toggle]');
  if (!toggles.length) {
    return;
  }

  toggles.forEach((toggle) => {
    const form = toggle.closest('form');
    const panel = form?.querySelector('[data-package-price-panel]') || document.querySelector('[data-package-price-panel]');
    if (!panel) {
      return;
    }

    const priceInput = panel.querySelector('[name="yeni_paket_tutari"]');

    function sync() {
      const active = toggle.checked;
      panel.hidden = !active;
      if (priceInput) {
        priceInput.required = active;
      }
      if (!active) {
        if (priceInput) {
          priceInput.value = '';
        }
      }
    }

    toggle.addEventListener('change', sync);
    form?.addEventListener('reset', () => window.setTimeout(sync, 0));
    sync();
  });
})();

(function () {
  const table = document.querySelector('[data-payment-table]');
  if (!table) {
    return;
  }
  const cashboxDialog = document.querySelector('#tahsilat-kasa-dialog');
  const cashboxForm = document.querySelector('[data-payment-cashbox-form]');
  let paymentRows = [];
  let currentPage = 1;

  function money(value) {
    return `${Number(value || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} TL`;
  }

  function statusLabel(row) {
    return String(row.iptal) === '1' ? 'Geri Alindi' : 'Aktif';
  }

  function methodLabel(value) {
    const labels = {
      nakit: 'Nakit',
      kredi_karti: 'Kredi Karti',
      havale_eft: 'Havale / EFT',
      banka_havalesi: 'Banka Havalesi',
      odeme_baglantisi: 'Odeme Baglantisi',
      diger: 'Diger'
    };
    return labels[value] || value || '-';
  }

  function dateLabel(value) {
    if (!value) {
      return '-';
    }
    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
      return value;
    }
    return date.toLocaleDateString('tr-TR');
  }

  function receiptNo(row) {
    return row.makbuz_numarasi || `TK-${String(row.id || '').padStart(5, '0')}`;
  }

  function printReceipt(row) {
    const printWindow = window.open('', '_blank', 'width=900,height=650');
    if (!printWindow) {
      window.alert('Makbuz penceresi acilamadi. Tarayiciniz popup engelliyor olabilir.');
      return;
    }

    const no = escapeHtml(receiptNo(row));
    const receiptHtml = `<!doctype html>
      <html lang="tr">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Tahsilat Makbuzu ${no}</title>
        <style>
          @page { size: A5 landscape; margin: 8mm; }
          * { box-sizing: border-box; }
          body {
            margin: 0;
            background: #f7f8fc;
            color: #263247;
            font-family: Montserrat, Arial, sans-serif;
            font-size: 11px;
          }
          .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px;
          }
          .toolbar button {
            border: 1px solid #d7deef;
            border-radius: 999px;
            background: #ffffff;
            color: #1c2a3f;
            cursor: pointer;
            font: 700 13px Montserrat, Arial, sans-serif;
            padding: 9px 16px;
          }
          .receipt {
            width: 210mm;
            min-height: 148mm;
            margin: 0 auto;
            background: #fff;
            border: 2px solid #5863b4;
            padding: 7mm;
          }
          .header {
            align-items: flex-start;
            border-bottom: 2px solid #5863b4;
            display: grid;
            gap: 8mm;
            grid-template-columns: 1fr 1.2fr 1fr;
            padding-bottom: 5mm;
          }
          .brand {
            color: #283f9f;
            font-size: 26px;
            font-weight: 800;
            line-height: 1;
          }
          .brand small,
          .clinic small {
            color: #586479;
            display: block;
            font-size: 10px;
            font-weight: 600;
            margin-top: 3px;
          }
          .clinic {
            color: #263247;
            font-size: 10px;
            line-height: 1.5;
            text-align: center;
          }
          .title {
            text-align: right;
          }
          .title strong {
            background: #30358e;
            color: #fff;
            display: inline-block;
            font-size: 14px;
            font-weight: 800;
            padding: 5px 14px;
          }
          .title span {
            display: block;
            font-size: 11px;
            font-weight: 700;
            margin-top: 8px;
          }
          .info-grid {
            border: 1px solid #7f86c9;
            display: grid;
            grid-template-columns: 35mm 1fr 30mm 42mm;
            margin-top: 5mm;
          }
          .cell {
            border-bottom: 1px solid #7f86c9;
            border-right: 1px solid #7f86c9;
            min-height: 10mm;
            padding: 3mm;
          }
          .cell:nth-child(4n) { border-right: 0; }
          .cell:nth-last-child(-n+4) { border-bottom: 0; }
          .label {
            background: #f3f5ff;
            color: #30358e;
            font-weight: 800;
          }
          table {
            border-collapse: collapse;
            margin-top: 4mm;
            width: 100%;
          }
          th, td {
            border: 1px solid #7f86c9;
            height: 10mm;
            padding: 2.5mm;
            text-align: left;
            vertical-align: top;
          }
          th {
            background: #f3f5ff;
            color: #30358e;
            font-weight: 800;
          }
          .note {
            border: 1px solid #7f86c9;
            margin-top: 4mm;
            min-height: 16mm;
            padding: 3mm;
          }
          .totals {
            align-items: end;
            display: grid;
            gap: 5mm;
            grid-template-columns: 1fr 44mm;
            margin-top: 4mm;
          }
          .total-box {
            border: 1px solid #7f86c9;
          }
          .total-row {
            display: flex;
            justify-content: space-between;
            padding: 3mm;
          }
          .total-row strong {
            color: #30358e;
            font-size: 15px;
          }
          .signatures {
            display: flex;
            font-weight: 800;
            justify-content: space-between;
            margin-top: 7mm;
          }
          @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .receipt { border-color: #5863b4; margin: 0; width: 100%; }
          }
        </style>
      </head>
      <body>
        <div class="toolbar">
          <button type="button" onclick="window.print()">Yazdir</button>
          <button type="button" onclick="window.close()">Kapat</button>
        </div>
        <main class="receipt">
          <section class="header">
            <div class="brand">Oyun Evleri<small>www.oyunevleri.com</small></div>
            <div class="clinic">
              <strong>Oyun Evleri Yönetim Sistemi</strong>
              <small>Tahsilat kaydi bilgilendirme makbuzudur.</small>
            </div>
            <div class="title">
              <strong>TAHSILAT MAKBUZU</strong>
              <span>No: ${no}</span>
              <span>Tarih: ${escapeHtml(dateLabel(row.tarih))}</span>
            </div>
          </section>

          <section class="info-grid">
            <div class="cell label">Musteri Adi</div>
            <div class="cell">${escapeHtml(row.ogrenci || '-')}</div>
            <div class="cell label">Makbuz No</div>
            <div class="cell">${no}</div>
            <div class="cell label">Paket / Hizmet</div>
            <div class="cell">${escapeHtml(row.paket_adi || '-')}</div>
            <div class="cell label">Odeme Tarihi</div>
            <div class="cell">${escapeHtml(dateLabel(row.tarih))}</div>
            <div class="cell label">Odeme Yontemi</div>
            <div class="cell">${escapeHtml(methodLabel(row.yontem))}</div>
            <div class="cell label">Kasa</div>
            <div class="cell">${escapeHtml(row.kasa || '-')}</div>
          </section>

          <table>
            <thead>
              <tr>
                <th>Aciklama</th>
                <th>Yontem</th>
                <th>Makbuz No</th>
                <th>Vade</th>
                <th>Tutar</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>${escapeHtml(row.paket_adi || '-')}</td>
                <td>${escapeHtml(methodLabel(row.yontem))}</td>
                <td>${no}</td>
                <td>${escapeHtml(dateLabel(row.tarih))}</td>
                <td>${escapeHtml(money(row.tutar))}</td>
              </tr>
              <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
              <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr>
            </tbody>
          </table>

          <div class="totals">
            <div class="note"><strong>Not:</strong> ${escapeHtml(row.aciklama || '-')}</div>
            <div class="total-box">
              <div class="total-row"><span>Toplam</span><strong>${escapeHtml(money(row.tutar))}</strong></div>
            </div>
          </div>

          <div class="signatures">
            <span>Tahsil Eden</span>
            <span>Imza</span>
          </div>
        </main>
        <script>
          window.addEventListener('load', function () {
            window.setTimeout(function () { window.print(); }, 250);
          });
        </script>
      </body>
      </html>`;

    printWindow.document.open();
    printWindow.document.write(receiptHtml);
    printWindow.document.close();
  }

  function renderPagination(paging) {
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
          <button class="mini-btn" type="button" data-payment-page="1" ${pageNumber <= 1 ? 'disabled' : ''}>&laquo;</button>
          <button class="mini-btn" type="button" data-payment-page="${Math.max(1, pageNumber - 1)}" ${pageNumber <= 1 ? 'disabled' : ''}>&lsaquo;</button>
          ${totalPage <= 1 ? '<button class="mini-btn is-active" type="button" data-payment-page="1">1</button>' : ''}
          ${start > 1 ? '<span class="table-pagination-gap">...</span>' : ''}
          ${pages.map((page) => `<button class="mini-btn ${page === pageNumber ? 'is-active' : ''}" type="button" data-payment-page="${page}">${page}</button>`).join('')}
          ${end < totalPage ? '<span class="table-pagination-gap">...</span>' : ''}
          <button class="mini-btn" type="button" data-payment-page="${Math.min(totalPage, pageNumber + 1)}" ${pageNumber >= totalPage ? 'disabled' : ''}>&rsaquo;</button>
          <button class="mini-btn" type="button" data-payment-page="${Math.max(1, totalPage)}" ${pageNumber >= totalPage ? 'disabled' : ''}>&raquo;</button>
        </div>
        <label class="table-pagination-size">
          <span>Sayfa başına satır</span>
          <select data-payment-limit>
            <option value="20" ${currentLimit === 20 ? 'selected' : ''}>20</option>
            <option value="50" ${currentLimit === 50 ? 'selected' : ''}>50</option>
            <option value="100" ${currentLimit === 100 ? 'selected' : ''}>100</option>
          </select>
        </label>
      </div>
    `;
  }

  function render(rows, paging = {}) {
    paymentRows = rows;
    if (!rows.length) {
      table.innerHTML = '<div class="empty-table">Tahsilat kaydi bulunamadi.</div>';
      return;
    }

    table.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Ogrenci</th>
            <th>Paket</th>
            <th>Tarih</th>
            <th>Tutar</th>
            <th>Yontem</th>
            <th>Kasa</th>
            <th>Durum</th>
            <th>Islem</th>
          </tr>
        </thead>
        <tbody>
          ${rows.map((row, index) => `
            <tr>
              <td>${index + 1}</td>
              <td>${escapeHtml(row.ogrenci)}</td>
              <td>${escapeHtml(row.paket_adi)}</td>
              <td>${escapeHtml(row.tarih)}</td>
              <td>${escapeHtml(money(row.tutar))}</td>
              <td>${escapeHtml(row.yontem)}</td>
              <td>${escapeHtml(row.kasa || '-')}</td>
              <td><span class="status-pill ${String(row.iptal) === '1' ? 'is-danger' : 'is-success'}">${escapeHtml(statusLabel(row))}</span></td>
              <td>
                <div class="row-actions">
                  <button type="button" data-payment-receipt="${escapeHtml(row.id)}" ${String(row.iptal) === '1' ? 'disabled' : ''}>Makbuz</button>
                  <button type="button" data-payment-cashbox="${escapeHtml(row.id)}" ${String(row.iptal) === '1' ? 'disabled' : ''}>Kasaya Aktar</button>
                  <button type="button" data-payment-undo="${escapeHtml(row.id)}" ${String(row.iptal) === '1' ? 'disabled' : ''}>Geri Al</button>
                  <button type="button" data-payment-delete="${escapeHtml(row.id)}">Sil</button>
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
      ${renderPagination(paging)}
    `;
  }

  function openDialog(target) {
    if (target && typeof target.showModal === 'function') {
      target.showModal();
    } else if (target) {
      target.setAttribute('open', 'open');
    }
  }

  function closeDialog(target) {
    if (target && typeof target.close === 'function') {
      target.close();
    } else if (target) {
      target.removeAttribute('open');
    }
  }

  function openCashboxTransfer(row) {
    if (!cashboxForm || !cashboxDialog) {
      return;
    }
    cashboxForm.reset();
    cashboxForm.elements.id.value = row.id || '';
    cashboxForm.elements.odeme_bilgi.value = `${row.ogrenci} / ${money(row.tutar)} / ${row.tarih}`;
    cashboxForm.elements.kasa_id.value = row.kasa_id || '';
    const message = cashboxForm.querySelector('[data-form-message]');
    if (message) {
      message.textContent = '';
    }
    openDialog(cashboxDialog);
  }

  async function loadPayments(page = currentPage) {
    currentPage = Math.max(1, Number(page || 1));
    const limit = Math.max(10, Number(table.dataset.limit || '20'));
    table.innerHTML = '<div class="empty-table">Yukleniyor...</div>';
    try {
      const result = await talyaAjax('odeme_listele', { sayfa: currentPage, limit });
      render(result.veri?.kayitlar || [], result.veri?.sayfalama || {});
    } catch (error) {
      table.innerHTML = `<div class="empty-table">${escapeHtml(error.message)}</div>`;
    }
  }

  table.addEventListener('click', async (event) => {
    const receipt = event.target.closest('[data-payment-receipt]');
    if (receipt) {
      const row = paymentRows.find((item) => String(item.id) === String(receipt.dataset.paymentReceipt));
      if (row) {
        printReceipt(row);
      }
      return;
    }

    const cashbox = event.target.closest('[data-payment-cashbox]');
    if (cashbox) {
      const row = paymentRows.find((item) => String(item.id) === String(cashbox.dataset.paymentCashbox));
      if (row) {
        openCashboxTransfer(row);
      }
      return;
    }

    const undo = event.target.closest('[data-payment-undo]');
    if (undo) {
      if (!window.confirm('Bu tahsilat geri alinsin mi?')) {
        return;
      }
      await talyaAjax('odeme_geri_al', {
        id: undo.getAttribute('data-payment-undo'),
        iptal_nedeni: 'Kullanici tarafindan geri alindi.'
      });
      await loadPayments(currentPage);
      return;
    }

    const del = event.target.closest('[data-payment-delete]');
    if (del) {
      if (!window.confirm('Bu tahsilat kalici olarak silinsin mi?')) {
        return;
      }
      await talyaAjax('odeme_sil', { id: del.getAttribute('data-payment-delete') });
      await loadPayments(currentPage);
      return;
    }

    const pageButton = event.target.closest('[data-payment-page]');
    if (pageButton && !pageButton.disabled) {
      await loadPayments(Number(pageButton.dataset.paymentPage || 1));
    }
  });

  table.addEventListener('change', async (event) => {
    const select = event.target.closest('[data-payment-limit]');
    if (!select) {
      return;
    }

    table.dataset.limit = String(select.value || '20');
    await loadPayments(1);
  });

  cashboxForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (cashboxForm.dataset.submitting === '1') {
      return;
    }

    const message = cashboxForm.querySelector('[data-form-message]');
    const submitButtons = Array.from(cashboxForm.querySelectorAll('button[type="submit"], input[type="submit"]'));
    cashboxForm.dataset.submitting = '1';
    submitButtons.forEach((button) => {
      button.disabled = true;
    });
    if (message) {
      message.textContent = 'Aktariliyor...';
    }

    try {
      const result = await talyaAjax('odeme_kasaya_aktar', formValues(cashboxForm));
      if (message) {
        message.textContent = result.mesaj;
      }
      closeDialog(cashboxDialog);
      await loadPayments();
    } catch (error) {
      if (message) {
        message.textContent = error.message;
      }
    } finally {
      cashboxForm.dataset.submitting = '0';
      submitButtons.forEach((button) => {
        button.disabled = false;
      });
    }
  });

  loadPayments();
})();

(function () {
  const selects = document.querySelectorAll('[data-expense-repeat-select]');
  if (!selects.length) {
    return;
  }

  function sync(select) {
    const form = select.closest('form');
    const wrap = form?.querySelector('[data-expense-repeat-count-wrap]');
    const input = wrap?.querySelector('input');
    const isMonthly = select.value === 'aylik';

    if (wrap) {
      wrap.hidden = !isMonthly;
    }
    if (input) {
      input.required = isMonthly;
      if (!isMonthly) {
        input.value = '12';
      }
    }
  }

  selects.forEach((select) => {
    sync(select);
    select.addEventListener('change', () => sync(select));
    select.closest('form')?.addEventListener('reset', () => {
      window.setTimeout(() => sync(select), 0);
    });
  });
})();

(function () {
  const table = document.querySelector('[data-expense-table]');
  if (!table) {
    return;
  }
  const summary = document.querySelector('[data-expense-summary]');
  const insights = document.querySelector('[data-expense-insights]');
  const searchInput = document.querySelector('[data-expense-search]');
  const filterRoot = document.querySelector('[data-expense-date-filter]');
  const filterButtons = filterRoot ? Array.from(filterRoot.querySelectorAll('[data-expense-period]')) : [];
  const filterStart = filterRoot?.querySelector('[data-expense-start]');
  const filterEnd = filterRoot?.querySelector('[data-expense-end]');
  const filterApply = filterRoot?.querySelector('[data-expense-apply]');
  const editDialog = document.querySelector('#gider-duzenle-dialog');
  const editForm = document.querySelector('[data-expense-edit-form]');
  const chartColors = ['#35a4e8', '#11b8a2', '#8b5cf6', '#f59e0b', '#ef5b4f', '#5b7cfa', '#14a46f', '#64748b'];
  let allExpenseRows = [];
  let expenseRows = [];
  let expenseSummary = {};
  let expenseFilter = {
    tarih_filtresi: filterRoot?.dataset.defaultPeriod || 'bu_ay',
    baslangic_tarihi: '',
    bitis_tarihi: ''
  };

  function money(value) {
    return `${Number(value || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} TL`;
  }

  function statusLabel(status) {
    const labels = { planlandi: 'Planlandi', odendi: 'Odendi', iptal: 'Iptal' };
    return labels[status] || status;
  }

  function paymentTypeLabel(value) {
    const labels = {
      nakit: 'Nakit',
      kredi_karti: 'Kredi Karti',
      banka_havalesi: 'Banka Havalesi',
      otomatik_odeme: 'Otomatik Odeme',
      diger: 'Diger'
    };
    return labels[value] || value;
  }

  function renderSummary(ozet) {
    if (!summary) {
      return;
    }
    summary.innerHTML = `
      <article><span>Secili Aralik</span><strong>${escapeHtml(money(ozet.aralik_planli))}</strong></article>
      <article><span>Gecikmis</span><strong>${escapeHtml(money(ozet.gecikmis))}</strong></article>
      <article><span>Bugun</span><strong>${escapeHtml(money(ozet.bugun))}</strong></article>
      <article><span>7 Gun</span><strong>${escapeHtml(money(ozet.yedi_gun))}</strong></article>
    `;
  }

  function normalizeText(value) {
    return String(value || '').toLocaleLowerCase('tr-TR');
  }

  function rowMatchesSearch(row, query) {
    if (!query) {
      return true;
    }

    return [
      row.tarih,
      row.tedarikci,
      row.kategori,
      row.aciklama,
      row.kasa,
      row.tutar,
      statusLabel(row.durum),
      paymentTypeLabel(row.odeme_turu)
    ].some((value) => normalizeText(value).includes(query));
  }

  function filteredRows() {
    const query = normalizeText(searchInput?.value || '').trim();
    return allExpenseRows.filter((row) => rowMatchesSearch(row, query));
  }

  function currentMonthKey() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
  }

  function categoryName(row) {
    return row.kategori || 'Kategorisiz';
  }

  function activeFilterLabel() {
    if (expenseFilter.tarih_filtresi === 'sonraki_ay') {
      return 'sonraki ay';
    }
    if (expenseFilter.tarih_filtresi === 'ozel') {
      return 'secili tarih araligi';
    }
    return 'bu ay';
  }

  function renderInsights(rows) {
    if (!insights) {
      return;
    }

    const totals = new Map();
    let grandTotal = 0;
    let selectedRemaining = 0;

    rows.forEach((row) => {
      const amount = Number(row.tutar || 0);
      if (amount <= 0) {
        return;
      }

      const category = categoryName(row);
      totals.set(category, (totals.get(category) || 0) + amount);
      grandTotal += amount;

      if (row.durum === 'planlandi') {
        selectedRemaining += amount;
      }
    });

    const categories = Array.from(totals, ([name, total]) => ({ name, total }))
      .sort((a, b) => b.total - a.total)
      .map((item, index) => ({
        ...item,
        color: chartColors[index % chartColors.length],
        percent: grandTotal > 0 ? (item.total / grandTotal) * 100 : 0
      }));

    const highest = categories[0] || null;
    let cursor = 0;
    const segments = categories.map((item) => {
      const start = cursor;
      cursor += item.percent;
      return `${item.color} ${start.toFixed(2)}% ${cursor.toFixed(2)}%`;
    });
    const donutBackground = segments.length
      ? `conic-gradient(${segments.join(', ')})`
      : 'conic-gradient(#e8f3fb 0% 100%)';

    insights.innerHTML = `
      <article class="expense-insight-card">
        <div class="expense-insight-head">
          <div>
            <h3>Kategori Gider Dagilimi</h3>
            <p>Giderlerin kategori bazli toplam tutarlari ve ${escapeHtml(activeFilterLabel())} icin kalan planli odeme.</p>
          </div>
        </div>
        <div class="expense-kpi-grid">
          <article>
            <span>Secili Aralik Kalan</span>
            <strong>${escapeHtml(money(selectedRemaining || expenseSummary.aralik_planli))}</strong>
            <small>Filtrelenen aralikta planli ve henuz odenmemis gider.</small>
          </article>
          <article>
            <span>En Cok Giden</span>
            <strong>${escapeHtml(highest ? highest.name : '-')}</strong>
            <small>${escapeHtml(highest ? money(highest.total) : money(0))}</small>
          </article>
          <article>
            <span>Toplam Gider</span>
            <strong>${escapeHtml(money(grandTotal))}</strong>
            <small>Listelenen gider kayitlari uzerinden.</small>
          </article>
        </div>
        <div class="expense-chart-layout">
          <div class="expense-donut" style="background: ${donutBackground};">
            <span>${escapeHtml(String(categories.length))}</span>
            <small>kategori</small>
          </div>
          <div class="expense-chart-list">
            ${categories.length ? categories.map((item) => `
              <div class="expense-chart-row">
                <div class="expense-chart-row-head">
                  <span><i style="background:${item.color};"></i>${escapeHtml(item.name)}</span>
                  <strong>${escapeHtml(money(item.total))}</strong>
                </div>
                <div class="expense-chart-bar"><span style="width:${Math.max(2, item.percent).toFixed(2)}%; background:${item.color};"></span></div>
                <small>${item.percent.toLocaleString('tr-TR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}%</small>
              </div>
            `).join('') : '<div class="empty-table">Grafik icin gider kaydi bulunamadi.</div>'}
          </div>
        </div>
      </article>
    `;
  }

  function render(rows) {
    expenseRows = rows;
    if (!rows.length) {
      const emptyText = searchInput?.value ? 'Aramaniza uygun gider bulunamadi.' : 'Planlanmis gider bulunamadi.';
      table.innerHTML = `<div class="empty-table">${escapeHtml(emptyText)}</div>`;
      return;
    }

    table.innerHTML = `
      <table>
        <thead>
          <tr>
            <th>Tarih</th>
            <th>Tedarikci</th>
            <th>Kategori</th>
            <th>Aciklama</th>
            <th>Tutar</th>
            <th>Odeme Turu</th>
            <th>Kasa</th>
            <th>Durum</th>
            <th>Islem</th>
          </tr>
        </thead>
        <tbody>
          ${rows.map((row) => `
            <tr>
              <td>${escapeHtml(row.tarih)}</td>
              <td>${escapeHtml(row.tedarikci)}</td>
              <td>${escapeHtml(row.kategori || '-')}</td>
              <td>${escapeHtml(row.aciklama || '-')}</td>
              <td><strong>${escapeHtml(money(row.tutar))}</strong></td>
              <td>${escapeHtml(paymentTypeLabel(row.odeme_turu))}</td>
              <td>${escapeHtml(row.kasa || '-')}</td>
              <td><span class="status-pill ${row.durum === 'odendi' ? 'is-success' : row.durum === 'iptal' ? 'is-danger' : ''}">${escapeHtml(statusLabel(row.durum))}</span></td>
              <td>
                <div class="row-actions">
                  <button type="button" data-expense-edit="${escapeHtml(row.id)}">Duzenle</button>
                  <button type="button" data-expense-paid="${escapeHtml(row.id)}" ${row.durum !== 'planlandi' ? 'disabled' : ''}>Odendi</button>
                  <button type="button" data-expense-delete="${escapeHtml(row.id)}">Sil</button>
                </div>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  }

  function dateInput(value) {
    return String(value || '').slice(0, 10);
  }

  function openDialog(target) {
    if (target && typeof target.showModal === 'function') {
      target.showModal();
    } else if (target) {
      target.setAttribute('open', 'open');
    }
  }

  function closeDialog(target) {
    if (target && typeof target.close === 'function') {
      target.close();
    } else if (target) {
      target.removeAttribute('open');
    }
  }

  function syncFilterUi(filter) {
    if (!filterRoot || !filter) {
      return;
    }

    expenseFilter = {
      tarih_filtresi: filter.tarih_filtresi || expenseFilter.tarih_filtresi || 'bu_ay',
      baslangic_tarihi: filter.baslangic_tarihi || '',
      bitis_tarihi: filter.bitis_tarihi || ''
    };

    if (filterStart) {
      filterStart.value = expenseFilter.baslangic_tarihi;
    }
    if (filterEnd) {
      filterEnd.value = expenseFilter.bitis_tarihi;
    }

    filterButtons.forEach((button) => {
      button.classList.toggle('is-active', button.getAttribute('data-expense-period') === expenseFilter.tarih_filtresi);
    });
  }

  function fillSelectOrNew(select, value) {
    if (!select) {
      return;
    }
    const form = select.closest('form');
    const newInput = form?.querySelector('[name="yeni_kategori"]');
    const exists = Array.from(select.options).some((option) => option.value === value);
    if (exists || !value) {
      select.value = value || '';
      if (newInput) {
        newInput.value = '';
      }
    } else {
      select.value = '__new';
      if (newInput) {
        newInput.value = value;
      }
    }
    select.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function openEdit(row) {
    if (!editForm || !editDialog) {
      return;
    }
    editForm.reset();
    editForm.elements.id.value = row.id || '';
    editForm.elements.tarih.value = dateInput(row.tarih);
    editForm.elements.tedarikci.value = row.tedarikci || '';
    editForm.elements.tutar.value = Number(row.tutar || 0);
    editForm.elements.odeme_turu.value = row.odeme_turu || 'nakit';
    if (editForm.elements.kasa_id) {
      editForm.elements.kasa_id.value = row.kasa_id || '';
    }
    editForm.elements.aciklama.value = row.aciklama || '';
    fillSelectOrNew(editForm.elements.kategori, row.kategori || '');
    const message = editForm.querySelector('[data-form-message]');
    if (message) {
      message.textContent = '';
    }
    openDialog(editDialog);
  }

  async function loadExpenses() {
    table.innerHTML = '<div class="empty-table">Yukleniyor...</div>';
    try {
      const result = await talyaAjax('gider_listele', expenseFilter);
      allExpenseRows = result.veri?.kayitlar || [];
      expenseSummary = result.veri?.ozet || {};
      syncFilterUi(result.veri?.filtre || expenseFilter);
      renderSummary(expenseSummary);
      renderInsights(allExpenseRows);
      render(filteredRows());
    } catch (error) {
      table.innerHTML = `<div class="empty-table">${escapeHtml(error.message)}</div>`;
    }
  }

  searchInput?.addEventListener('input', () => {
    render(filteredRows());
  });

  filterButtons.forEach((button) => {
    button.addEventListener('click', async () => {
      expenseFilter = {
        tarih_filtresi: button.getAttribute('data-expense-period') || 'bu_ay',
        baslangic_tarihi: '',
        bitis_tarihi: ''
      };
      await loadExpenses();
    });
  });

  filterApply?.addEventListener('click', async () => {
    expenseFilter = {
      tarih_filtresi: 'ozel',
      baslangic_tarihi: filterStart?.value || '',
      bitis_tarihi: filterEnd?.value || ''
    };
    await loadExpenses();
  });

  table.addEventListener('click', async (event) => {
    const edit = event.target.closest('[data-expense-edit]');
    if (edit) {
      const id = String(edit.getAttribute('data-expense-edit') || '');
      const row = expenseRows.find((item) => String(item.id) === id);
      if (row) {
        openEdit(row);
      }
      return;
    }

    const paid = event.target.closest('[data-expense-paid]');
    if (paid) {
      if (!window.confirm('Bu gider odendi olarak isaretlensin mi?')) {
        return;
      }
      await talyaAjax('gider_odendi', { id: paid.getAttribute('data-expense-paid') });
      await loadExpenses();
      return;
    }

    const del = event.target.closest('[data-expense-delete]');
    if (del) {
      if (!window.confirm('Bu gider silinsin mi?')) {
        return;
      }
      await talyaAjax('gider_sil', { id: del.getAttribute('data-expense-delete') });
      await loadExpenses();
    }
  });

  editForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (editForm.dataset.submitting === '1') {
      return;
    }

    const message = editForm.querySelector('[data-form-message]');
    const submitButtons = Array.from(editForm.querySelectorAll('button[type="submit"], input[type="submit"]'));
    editForm.dataset.submitting = '1';
    submitButtons.forEach((button) => {
      button.disabled = true;
    });
    if (message) {
      message.textContent = 'Kaydediliyor...';
    }

    try {
      const result = await talyaAjax('gider_guncelle', formValues(editForm));
      if (message) {
        message.textContent = result.mesaj;
      }
      closeDialog(editDialog);
      await loadExpenses();
    } catch (error) {
      if (message) {
        message.textContent = error.message;
      }
    } finally {
      editForm.dataset.submitting = '0';
      submitButtons.forEach((button) => {
        button.disabled = false;
      });
    }
  });

  loadExpenses();
})();

(function () {
  const selects = document.querySelectorAll('[data-expense-category-select]');
  if (!selects.length) {
    return;
  }

  function sync(select) {
    const form = select.closest('form');
    const wrap = form?.querySelector('[data-expense-category-new-wrap]');
    const input = wrap?.querySelector('input');
    const isNew = select.value === '__new';

    if (wrap) {
      wrap.hidden = !isNew;
    }
    if (input) {
      input.required = isNew;
      if (!isNew) {
        input.value = '';
      }
    }
  }

  selects.forEach((select) => {
    sync(select);
    select.addEventListener('change', () => sync(select));
    select.closest('form')?.addEventListener('reset', () => {
      window.setTimeout(() => sync(select), 0);
    });
  });
})();
