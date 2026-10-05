(() => {
  const page = document.querySelector('[data-personel-page]');
  if (!page) return;

  const personelDialog = document.querySelector('[data-personel-dialog]');
  const personelForm = document.querySelector('[data-personel-form]');
  const puantajDialog = document.querySelector('[data-puantaj-dialog]');
  const puantajForm = document.querySelector('[data-puantaj-form]');
  const topluPuantajDialog = document.querySelector('[data-puantaj-toplu-dialog]');
  const topluPuantajForm = document.querySelector('[data-puantaj-toplu-form]');
  const puantajGrid = document.querySelector('.attendance-grid');
  const puantajHucreleri = [...document.querySelectorAll('[data-puantaj-cell]')];
  const topluDurumButonlari = [...document.querySelectorAll('[data-puantaj-toplu-durum]')];
  const secimSayisi = document.querySelector('[data-puantaj-secim-sayisi]');
  const secimiTemizleButonu = document.querySelector('[data-puantaj-secimi-temizle]');
  const personelMapNode = document.querySelector('[data-personel-map]');
  let personelMap = {};
  try { personelMap = personelMapNode ? JSON.parse(personelMapNode.textContent || '{}') : {}; } catch (_) { personelMap = {}; }

  const formVerisi = (form) => Object.fromEntries(new FormData(form).entries());
  const dialogAc = (dialog) => {
    if (!dialog || dialog.open) return;
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', '');
  };
  const dialogKapat = (dialog) => {
    if (!dialog) return;
    if (typeof dialog.close === 'function') dialog.close();
    else dialog.removeAttribute('open');
  };
  const mesajYaz = (hedef, metin, hata = false) => {
    if (!hedef) return;
    hedef.textContent = metin || '';
    hedef.classList.toggle('is-error', hata);
  };
  const yenidenYukle = () => window.location.reload();
  const puantajKaydiniOku = (hucre) => {
    try { return JSON.parse(hucre?.getAttribute('data-puantaj-kayit') || '{}'); } catch (_) { return {}; }
  };
  const puantajDialoginiAc = (kayit) => {
    if (!puantajForm || !kayit || !kayit.personel_id || !kayit.tarih) return;
    puantajForm.reset();
    for (const [alan, deger] of Object.entries(kayit)) {
      if (puantajForm.elements[alan]) puantajForm.elements[alan].value = deger ?? '';
    }
    document.querySelector('[data-puantaj-kisi]').textContent = `${kayit.ad_soyad || ''} · ${kayit.tarih || ''}`;
    mesajYaz(document.querySelector('[data-puantaj-mesaj]'), '');
    dialogAc(puantajDialog);
  };

  const seciliHucreler = new Set();
  let surukleniyor = false;
  let secimBaslangici = null;
  let korunanSecim = new Set();

  const secimArayuzunuGuncelle = () => {
    puantajHucreleri.forEach((hucre) => hucre.setAttribute('aria-selected', seciliHucreler.has(hucre) ? 'true' : 'false'));
    const adet = seciliHucreler.size;
    if (secimSayisi) secimSayisi.textContent = `${adet} hücre seçildi`;
    topluDurumButonlari.forEach((buton) => { buton.disabled = adet === 0; });
    if (secimiTemizleButonu) secimiTemizleButonu.disabled = adet === 0;
  };

  const seciliKayitlariHazirla = () => [...seciliHucreler]
    .map(puantajKaydiniOku)
    .map((kayit) => ({
      personel_id: Number(kayit.personel_id || 0),
      tarih: String(kayit.tarih || '')
    }))
    .filter((kayit) => kayit.personel_id > 0 && kayit.tarih !== '');

  const topluPuantajDialoginiAc = () => {
    if (!topluPuantajForm || seciliHucreler.size < 2) return;
    topluPuantajForm.reset();
    const sayi = topluPuantajDialog?.querySelector('[data-puantaj-toplu-sayi]');
    if (sayi) sayi.textContent = `${seciliHucreler.size} hücre`;
    mesajYaz(topluPuantajDialog?.querySelector('[data-puantaj-toplu-mesaj]'), '');
    dialogAc(topluPuantajDialog);
    window.requestAnimationFrame(() => topluPuantajForm.elements.durum?.focus());
  };

  const topluPuantajiKaydet = async (durum, durumAdi, onayIste = true) => {
    const kayitlar = seciliKayitlariHazirla();
    if (!kayitlar.length || !durum) return false;
    if (onayIste && !window.confirm(`${kayitlar.length} puantaj hücresi “${durumAdi}” olarak işaretlensin mi?`)) return false;
    topluDurumButonlari.forEach((secenek) => { secenek.disabled = true; });
    if (secimSayisi) secimSayisi.textContent = `${kayitlar.length} hücre kaydediliyor...`;
    try {
      await talyaAjax('personel_puantaj_toplu_kaydet', { durum, kayitlar });
      yenidenYukle();
      return true;
    } catch (error) {
      const hataMesaji = error.message || 'Toplu puantaj kaydedilemedi.';
      if (topluPuantajDialog?.open) mesajYaz(topluPuantajDialog.querySelector('[data-puantaj-toplu-mesaj]'), hataMesaji, true);
      else window.alert(hataMesaji);
      secimArayuzunuGuncelle();
      return false;
    }
  };

  const dikdortgenSec = (bitis) => {
    if (!secimBaslangici || !bitis) return;
    const ilkSatir = Math.min(Number(secimBaslangici.dataset.gridRow), Number(bitis.dataset.gridRow));
    const sonSatir = Math.max(Number(secimBaslangici.dataset.gridRow), Number(bitis.dataset.gridRow));
    const ilkKolon = Math.min(Number(secimBaslangici.dataset.gridCol), Number(bitis.dataset.gridCol));
    const sonKolon = Math.max(Number(secimBaslangici.dataset.gridCol), Number(bitis.dataset.gridCol));
    seciliHucreler.clear();
    korunanSecim.forEach((hucre) => seciliHucreler.add(hucre));
    puantajHucreleri.forEach((hucre) => {
      const satir = Number(hucre.dataset.gridRow);
      const kolon = Number(hucre.dataset.gridCol);
      if (satir >= ilkSatir && satir <= sonSatir && kolon >= ilkKolon && kolon <= sonKolon) seciliHucreler.add(hucre);
    });
    secimArayuzunuGuncelle();
  };

  puantajGrid?.addEventListener('pointerdown', (event) => {
    const hucre = event.target.closest('[data-puantaj-cell]');
    if (!hucre || event.button !== 0) return;
    surukleniyor = true;
    secimBaslangici = hucre;
    korunanSecim = (event.ctrlKey || event.metaKey) ? new Set(seciliHucreler) : new Set();
    if ((event.ctrlKey || event.metaKey) && seciliHucreler.has(hucre)) {
      korunanSecim.delete(hucre);
      seciliHucreler.delete(hucre);
      secimBaslangici = null;
      secimArayuzunuGuncelle();
    } else {
      dikdortgenSec(hucre);
    }
    puantajGrid.classList.add('is-selecting');
  });

  puantajGrid?.addEventListener('pointerover', (event) => {
    if (!surukleniyor || !secimBaslangici) return;
    const hucre = event.target.closest('[data-puantaj-cell]');
    if (hucre) dikdortgenSec(hucre);
  });

  const suruklemeyiBitir = (event) => {
    if (!surukleniyor) return;
    const topluDuzenlemeAc = event.type === 'pointerup' && seciliHucreler.size > 1;
    surukleniyor = false;
    secimBaslangici = null;
    korunanSecim = new Set();
    puantajGrid?.classList.remove('is-selecting');
    if (topluDuzenlemeAc) window.requestAnimationFrame(topluPuantajDialoginiAc);
  };
  document.addEventListener('pointerup', suruklemeyiBitir);
  document.addEventListener('pointercancel', suruklemeyiBitir);

  puantajGrid?.addEventListener('dblclick', (event) => {
    const hucre = event.target.closest('[data-puantaj-cell]');
    if (!hucre) return;
    event.preventDefault();
    puantajDialoginiAc(puantajKaydiniOku(hucre));
  });

  secimiTemizleButonu?.addEventListener('click', () => {
    seciliHucreler.clear();
    secimArayuzunuGuncelle();
  });

  topluDurumButonlari.forEach((buton) => buton.addEventListener('click', async () => {
    const durum = buton.dataset.puantajTopluDurum || '';
    const durumAdi = buton.dataset.puantajDurumAdi || durum;
    await topluPuantajiKaydet(durum, durumAdi);
  }));

  secimArayuzunuGuncelle();

  document.addEventListener('click', async (event) => {
    const yeni = event.target.closest('[data-personel-yeni]');
    if (yeni && personelForm) {
      personelForm.reset();
      personelForm.elements.id.value = '';
      personelForm.elements.varsayilan_giris_saati.value = '09:00';
      personelForm.elements.varsayilan_cikis_saati.value = '18:00';
      document.querySelector('[data-personel-dialog-title]').textContent = 'Personel Ekle';
      mesajYaz(document.querySelector('[data-personel-mesaj]'), '');
      dialogAc(personelDialog);
      return;
    }

    const personelDuzenle = event.target.closest('[data-personel-duzenle]');
    if (personelDuzenle && personelForm) {
      const kayit = personelMap[personelDuzenle.dataset.personelDuzenle];
      if (!kayit) return;
      personelForm.reset();
      for (const [alan, deger] of Object.entries(kayit)) {
        if (!personelForm.elements[alan]) continue;
        const temizDeger = ['varsayilan_giris_saati', 'varsayilan_cikis_saati'].includes(alan) && deger
          ? String(deger).slice(0, 5) : (deger ?? '');
        personelForm.elements[alan].value = temizDeger;
      }
      document.querySelector('[data-personel-dialog-title]').textContent = 'Personeli Düzenle';
      mesajYaz(document.querySelector('[data-personel-mesaj]'), '');
      dialogAc(personelDialog);
      return;
    }

    const puantajDuzenle = event.target.closest('[data-puantaj-duzenle]');
    if (puantajDuzenle && puantajForm) {
      let kayit = {};
      try { kayit = JSON.parse(puantajDuzenle.getAttribute('data-puantaj-duzenle') || '{}'); } catch (_) { return; }
      puantajDialoginiAc(kayit);
      return;
    }

    const hareket = event.target.closest('[data-hareket]');
    if (hareket) {
      const tur = hareket.dataset.hareket;
      const etiket = tur === 'cikis' ? 'çıkış' : 'giriş';
      if (!window.confirm(`Personelin ${etiket} saati şimdi olarak kaydedilsin mi?`)) return;
      hareket.disabled = true;
      try {
        await talyaAjax('personel_hareket_kaydet', { personel_id: Number(hareket.dataset.personelId), tur });
        yenidenYukle();
      } catch (error) {
        window.alert(error.message || 'Hareket kaydedilemedi.');
        hareket.disabled = false;
      }
      return;
    }

    if (event.target.closest('[data-personel-kapat]')) dialogKapat(personelDialog);
    if (event.target.closest('[data-puantaj-kapat]')) dialogKapat(puantajDialog);
    if (event.target.closest('[data-puantaj-toplu-kapat]')) dialogKapat(topluPuantajDialog);
  });

  personelForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const mesaj = document.querySelector('[data-personel-mesaj]');
    const buton = personelForm.querySelector('button[type="submit"]');
    buton.disabled = true;
    mesajYaz(mesaj, 'Kaydediliyor...');
    try {
      await talyaAjax('personel_kaydet', formVerisi(personelForm));
      mesajYaz(mesaj, 'Personel kaydedildi.');
      yenidenYukle();
    } catch (error) {
      mesajYaz(mesaj, error.message || 'Personel kaydedilemedi.', true);
      buton.disabled = false;
    }
  });

  puantajForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const mesaj = document.querySelector('[data-puantaj-mesaj]');
    const buton = puantajForm.querySelector('button[type="submit"]');
    buton.disabled = true;
    mesajYaz(mesaj, 'Kaydediliyor...');
    try {
      await talyaAjax('personel_puantaj_kaydet', formVerisi(puantajForm));
      mesajYaz(mesaj, 'Puantaj kaydedildi.');
      yenidenYukle();
    } catch (error) {
      mesajYaz(mesaj, error.message || 'Puantaj kaydedilemedi.', true);
      buton.disabled = false;
    }
  });

  topluPuantajForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const durum = String(topluPuantajForm.elements.durum?.value || '');
    if (!durum) {
      mesajYaz(topluPuantajDialog?.querySelector('[data-puantaj-toplu-mesaj]'), 'Uygulanacak durumu seçin.', true);
      return;
    }
    const buton = topluPuantajForm.querySelector('button[type="submit"]');
    if (buton) buton.disabled = true;
    mesajYaz(topluPuantajDialog?.querySelector('[data-puantaj-toplu-mesaj]'), 'Kaydediliyor...');
    const secenek = topluPuantajForm.elements.durum.selectedOptions?.[0];
    const basarili = await topluPuantajiKaydet(durum, secenek?.textContent?.trim() || durum, false);
    if (!basarili && buton) buton.disabled = false;
  });
})();
