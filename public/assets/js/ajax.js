async function talyaAjax(islem, veriler = {}) {
  const yanit = await fetch('/ajax.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': window.talyaCsrfToken || ''
    },
    body: JSON.stringify({ islem, ...veriler })
  });

  const sonuc = await yanit.json();
  if (!yanit.ok || !sonuc.basari) {
    const hata = new Error(sonuc.mesaj || 'Islem sirasinda bir hata olustu.');
    hata.hatalar = sonuc.hatalar || {};
    hata.veri = sonuc.veri || {};
    hata.status = yanit.status;
    throw hata;
  }
  return sonuc;
}
