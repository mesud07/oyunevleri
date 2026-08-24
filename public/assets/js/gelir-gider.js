(function () {
  const filter = document.querySelector('[data-finance-analysis-filter]');
  const form = document.querySelector('[data-finance-filter-form]');
  const results = document.querySelector('[data-finance-analysis-results]');
  const message = document.querySelector('[data-finance-filter-message]');
  const viewInput = document.querySelector('[data-finance-view-input]');

  if (!filter || !form || !results || !viewInput || typeof talyaAjax !== 'function') {
    return;
  }

  let timer = null;
  let requestId = 0;

  function formData() {
    const data = new FormData(form);
    return {
      gorunum: data.get('gorunum') || 'aylik',
      baslangic: data.get('baslangic') || '',
      bitis: data.get('bitis') || ''
    };
  }

  function formatDate(value) {
    if (!value) {
      return '';
    }
    const parts = String(value).split('-');
    if (parts.length !== 3) {
      return value;
    }
    return `${parts[2]}.${parts[1]}.${parts[0]}`;
  }

  function updateButtons(view) {
    filter.querySelectorAll('[data-finance-view]').forEach((button) => {
      const active = button.getAttribute('data-finance-view') === view;
      button.classList.toggle('btn-primary', active);
      button.classList.toggle('btn-ghost', !active);
    });
  }

  function updateUrl(data) {
    const params = new URLSearchParams();
    params.set('gorunum', data.gorunum);
    if (data.baslangic) {
      params.set('baslangic', data.baslangic);
    }
    if (data.bitis) {
      params.set('bitis', data.bitis);
    }
    window.history.replaceState({}, '', `${window.location.pathname}?${params.toString()}`);
  }

  async function load() {
    const currentRequest = ++requestId;
    const data = formData();
    results.classList.add('is-loading');
    if (message) {
      message.textContent = 'Bilgiler güncelleniyor...';
    }

    try {
      const response = await talyaAjax('gelir_gider_analizi', data);
      if (currentRequest !== requestId) {
        return;
      }
      const payload = response.veri || {};
      results.innerHTML = payload.html || '';
      viewInput.value = payload.gorunum || data.gorunum;
      updateButtons(viewInput.value);
      if (payload.baslangic && payload.bitis) {
        form.elements.baslangic.value = payload.baslangic;
        form.elements.bitis.value = payload.bitis;
        if (message) {
          message.textContent = `${formatDate(payload.baslangic)} - ${formatDate(payload.bitis)} aralığı gösteriliyor.`;
        }
      }
      updateUrl({
        gorunum: viewInput.value,
        baslangic: form.elements.baslangic.value,
        bitis: form.elements.bitis.value
      });
    } catch (error) {
      if (message) {
        message.textContent = error.message || 'Bilgiler güncellenemedi.';
      }
    } finally {
      if (currentRequest === requestId) {
        results.classList.remove('is-loading');
      }
    }
  }

  function scheduleLoad() {
    window.clearTimeout(timer);
    timer = window.setTimeout(load, 250);
  }

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    load();
  });

  form.querySelectorAll('input[type="date"]').forEach((input) => {
    input.addEventListener('change', scheduleLoad);
  });

  filter.querySelectorAll('[data-finance-view]').forEach((button) => {
    button.addEventListener('click', () => {
      viewInput.value = button.getAttribute('data-finance-view') || 'aylik';
      updateButtons(viewInput.value);
      load();
    });
  });
})();
