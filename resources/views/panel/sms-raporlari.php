<section class="record-titlebar">
    <h1>SMS Raporlari</h1>
    <div class="breadcrumb">Ayarlar <span>›</span> SMS <span>›</span> Raporlar</div>
</section>

<section class="definition-card sms-report-page" data-sms-report-page>
    <div class="definition-head">
        <div>
            <h2>SMS Raporlari</h2>
            <p class="muted">Yonetici tarafindan son gonderimler, durumlar ve servis kontrolleri izlenir.</p>
        </div>
        <a class="btn btn-ghost" href="/panel/sms">SMS Yonetimi</a>
    </div>

    <div class="info-box">
        <strong>Bilgilendirme</strong>
        <p>Kontrol sutunu NetGSM servisinden gonderilen SMS'in son durum bilgisini sorgular. Servis durumunu saglikli almak icin gonderimden sonra birkac dakika bekleyin.</p>
        <p>Filtrelenmis kayitlar sayfali olarak gosterilir. Ogrenciye veya veliye ait detaylar ilgili profil ve SMS kayitlari uzerinden takip edilebilir.</p>
    </div>

    <div class="sms-report-summary" data-sms-report-summary></div>

    <form class="sms-report-toolbar" data-sms-report-filter>
        <label>
            <span>Arama</span>
            <input class="search-input" type="search" name="q" placeholder="Ad soyad, telefon veya mesaj">
        </label>
        <label>
            <span>Durum</span>
            <select name="durum">
                <option value="">Tum durumlar</option>
                <option value="bekliyor">Bekliyor</option>
                <option value="isleniyor">Isleniyor</option>
                <option value="gonderildi">Servise gonderildi</option>
                <option value="teslim_edildi">SMS iletildi</option>
                <option value="basarisiz">Basarisiz</option>
                <option value="tekrar_bekliyor">Tekrar bekliyor</option>
                <option value="iptal">Iptal</option>
            </select>
        </label>
        <label>
            <span>Olay</span>
            <select name="olay_tipi">
                <option value="">Tum olaylar</option>
                <option value="manuel_sms">Manuel SMS</option>
                <option value="randevu_olusturuldu">Randevu olusturuldu</option>
                <option value="randevu_guncellendi">Randevu guncellendi</option>
                <option value="randevu_hatirlatma">Randevu hatirlatma</option>
                <option value="odeme_alindi">Odeme alindi</option>
                <option value="borc_hatirlatma">Borc hatirlatma</option>
            </select>
        </label>
        <label>
            <span>Baslangic</span>
            <input type="date" name="baslangic">
        </label>
        <label>
            <span>Bitis</span>
            <input type="date" name="bitis">
        </label>
        <button class="btn btn-primary" type="submit">Uygula</button>
    </form>

    <div class="table-wrap definition-table sms-report-table" data-sms-report-table></div>
    <div class="sms-report-pagination" data-sms-report-pagination></div>
</section>
