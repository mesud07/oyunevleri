<section class="page-head">
    <div>
        <h1>Paketler</h1>
        <p>Ogrenciye atanacak paket tanimlari, ucretler ve hak bilgileri.</p>
    </div>
    <div class="head-actions">
        <button class="btn btn-sky" type="button" data-toggle-panel="#paket-form-panel">+ Paket Ekle</button>
        <a class="btn btn-primary" href="/panel/paketler/tanimla">Ogrenciye Paket Tanimla</a>
    </div>
</section>

<section class="panel-grid single-wide">
    <article class="panel-card">
        <h2>Paket Tanimlari</h2>
        <p class="section-helper">Bir ogrenciye paket atandiginda bu tanimin fiyat ve hak bilgileri ogrenci paketine kopyalanir.</p>
        <article id="paket-form-panel" class="inline-form-panel" hidden>
            <h3>Yeni Paket Tanimi</h3>
            <form class="form-grid" data-ajax-form="hizmet_ekle" data-refresh="paket_listele" data-target="#paket-tablosu">
                <label><span>Paket Adi</span><input name="hizmet_adi" placeholder="Oyun Grubu 4 Seans (24-36 Ay)" required></label>
                <label><span>Ucret</span><input type="number" step="0.01" name="ucret" required></label>
                <label><span>Haftalik Katilim</span><input type="number" name="haftalik_katilim_sayisi" min="1" value="1"></label>
                <label><span>Normal Ders</span><input type="number" name="toplam_normal_hak" min="1" value="4"></label>
                <label><span>Telafi Hakki</span><input type="number" name="toplam_telafi_hak" min="0" value="1"></label>
                <div class="form-actions full">
                    <button class="btn btn-primary" type="submit">Paket Kaydet</button>
                    <span data-form-message></span>
                </div>
            </form>
        </article>
        <div id="paket-tablosu" class="table-wrap" data-table="paket_listele"></div>
    </article>
</section>
