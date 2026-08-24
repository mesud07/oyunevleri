<section class="page-head">
    <div>
        <h1>Ogrenciler</h1>
        <p>Ogrenci kayitlari ve detaylari.</p>
    </div>
    <a class="btn btn-primary" href="/panel/ogrenciler/yeni">Yeni Ogrenci Kaydi</a>
</section>

<section class="panel-grid single-wide">
    <article class="panel-card">
        <div class="appointment-toolbar">
            <div>
                <h2>Ogrenci Listesi</h2>
                <p>Isim veya telefon numarasina gore arama yapabilirsiniz.</p>
            </div>
            <label class="table-search">
                <span>Arama</span>
                <input type="search" data-student-search placeholder="Isim veya telefon ara">
            </label>
        </div>
        <div id="ogrenci-tablosu" class="table-wrap" data-table="ogrenci_listele"></div>
    </article>
</section>
